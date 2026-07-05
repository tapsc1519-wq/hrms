<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use App\Models\AssetHandoverRequest;
use App\Models\AssetIssueReport;
use App\Models\AssetRepair;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    public function index()
    {
        $assignments = AssetAssignment::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['asset.category', 'asset.location', 'asset.supplier'])
            ->latest('assigned_date')
            ->paginate(15);

        $staffUsers = User::where('organization_id', auth()->user()->organization_id)
            ->where('role', 'staff')
            ->where('status', 'active')
            ->where('id', '!=', auth()->id())
            ->with('department')
            ->orderBy('name')
            ->get();

        $incomingHandovers = AssetHandoverRequest::where('to_user_id', auth()->id())
            ->where('status', 'pending')
            ->whereHas('assignment', fn($q) => $q->where('status', 'active'))
            ->with(['asset.category', 'fromUser.department'])
            ->latest()
            ->get();

        $outgoingHandovers = AssetHandoverRequest::where('from_user_id', auth()->id())
            ->where('status', 'pending')
            ->whereHas('assignment', fn($q) => $q->where('status', 'active'))
            ->with(['asset', 'toUser.department'])
            ->latest()
            ->get();

        $openIssues = AssetIssueReport::where('reported_by', auth()->id())
            ->whereIn('status', AssetIssueReport::ACTIVE_STATUSES)
            ->with('asset')
            ->latest()
            ->get()
            ->keyBy('asset_assignment_id');

        $openRepairs = AssetRepair::where('requested_by', auth()->id())
            ->whereIn('status', AssetRepair::OPEN_STATUSES)
            ->with('asset')
            ->latest()
            ->get()
            ->keyBy('asset_assignment_id');

        $recentRepairs = AssetRepair::where('requested_by', auth()->id())
            ->with(['asset.category'])
            ->latest('requested_date')
            ->latest()
            ->limit(5)
            ->get();

        return view('staff.assets.index', compact('assignments', 'staffUsers', 'incomingHandovers', 'outgoingHandovers', 'openIssues', 'openRepairs', 'recentRepairs'));
    }

    public function repairs()
    {
        $repairs = AssetRepair::where('requested_by', auth()->id())
            ->with(['asset.category', 'vendor', 'amcContract', 'attachments' => fn($query) => $query->where('visibility', 'employee')->latest()])
            ->latest('requested_date')
            ->latest()
            ->paginate(15);

        return view('staff.assets.repairs', compact('repairs'));
    }

    public function handover(Request $request, AssetAssignment $assignment)
    {
        abort_if($assignment->user_id !== auth()->id(), 403);
        abort_if($assignment->status !== 'active', 422, 'Only active assignments can be handed over.');

        $validated = $request->validate([
            'handover_type' => 'required|in:staff,it_admin',
            'new_user_id'   => 'required_if:handover_type,staff|nullable|exists:users,id',
            'handover_date' => 'required|date',
            'condition_in'  => 'required|in:excellent,good,fair,poor',
            'notes'         => 'nullable|string',
        ]);

        if ($validated['handover_type'] === 'staff') {
            $newUser = User::where('organization_id', auth()->user()->organization_id)
                ->where('role', 'staff')
                ->where('status', 'active')
                ->where('id', '!=', auth()->id())
                ->findOrFail($validated['new_user_id']);

            $pendingExists = AssetHandoverRequest::where('asset_assignment_id', $assignment->id)
                ->where('status', 'pending')
                ->exists();

            if ($pendingExists) {
                return back()->withErrors(['new_user_id' => 'A pending handover request already exists for this asset.']);
            }

            AssetHandoverRequest::create([
                'asset_assignment_id' => $assignment->id,
                'asset_id'            => $assignment->asset_id,
                'from_user_id'        => auth()->id(),
                'to_user_id'          => $newUser->id,
                'handover_date'       => $validated['handover_date'],
                'condition_in'        => $validated['condition_in'],
                'notes'               => $validated['notes'],
                'status'              => 'pending',
            ]);

            return back()->with('success', 'Handover request sent to ' . $newUser->name . '. The asset will transfer after they accept it.');
        }

        DB::transaction(function () use ($assignment, $validated) {
            $assignment->update([
                'status'             => 'returned',
                'actual_return_date' => $validated['handover_date'],
                'condition_in'       => $validated['condition_in'],
                'notes'              => $validated['notes'],
            ]);

            $assignment->asset->update(['status' => 'available']);

            AssetHandoverRequest::where('asset_assignment_id', $assignment->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'responded_at' => now()]);
        });

        return back()->with('success', 'Asset handed over to IT Support/Admin successfully.');
    }

    public function reportIssue(Request $request, AssetAssignment $assignment)
    {
        abort_if($assignment->user_id !== auth()->id(), 403);
        abort_if($assignment->status !== 'active', 422, 'Only active assignments can be reported.');

        $data = $request->validate([
            'issue_type' => ['required', 'in:damaged,lost,stolen,not_working,obsolete,other'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'reported_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $openIssue = AssetIssueReport::where('asset_assignment_id', $assignment->id)
            ->whereIn('status', AssetIssueReport::ACTIVE_STATUSES)
            ->exists();

        if ($openIssue) {
            return back()->with('error', 'An open issue report already exists for this asset.');
        }

        AssetIssueReport::create([
            ...$data,
            'organization_id' => auth()->user()->organization_id,
            'asset_id' => $assignment->asset_id,
            'asset_assignment_id' => $assignment->id,
            'reported_by' => auth()->id(),
            'status' => 'open',
        ]);

        return back()->with('success', 'Asset issue reported to Admin/IT for review.');
    }

    public function requestRepair(Request $request, AssetAssignment $assignment)
    {
        abort_if($assignment->user_id !== auth()->id(), 403);
        abort_if($assignment->status !== 'active', 422, 'Only active assignments can be sent for repair request.');

        $data = $request->validate([
            'priority' => ['required', 'in:low,medium,high,critical'],
            'requested_date' => ['required', 'date'],
            'issue_summary' => ['required', 'string', 'max:2000'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,txt,zip'],
        ]);

        $openRepair = AssetRepair::where('asset_assignment_id', $assignment->id)
            ->whereIn('status', AssetRepair::OPEN_STATUSES)
            ->exists();

        if ($openRepair) {
            return back()->with('error', 'A repair request is already open for this asset.');
        }

        unset($data['attachments']);

        $repair = AssetRepair::create([
            ...$data,
            'organization_id' => auth()->user()->organization_id,
            'asset_id' => $assignment->asset_id,
            'asset_assignment_id' => $assignment->id,
            'requested_by' => auth()->id(),
            'repair_number' => 'REP-' . now()->format('YmdHis') . '-' . $assignment->id,
            'source' => 'employee',
            'repair_type' => 'internal',
            'status' => 'request_raised',
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $repair->attachments()->create([
                'organization_id' => auth()->user()->organization_id,
                'uploaded_by' => auth()->id(),
                'type' => 'repair_photo',
                'visibility' => 'employee',
                'file_path' => $file->store('asset-repairs/' . $repair->id, 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        return back()->with('success', 'Repair request submitted to Admin/IT Support.');
    }

    public function acceptHandover(Request $request, AssetHandoverRequest $handover)
    {
        abort_if($handover->to_user_id !== auth()->id(), 403);
        abort_if($handover->status !== 'pending', 422, 'This handover request is no longer pending.');
        abort_if($handover->assignment->status !== 'active', 422, 'The original assignment is no longer active.');

        $validated = $request->validate([
            'response_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($handover, $validated) {
            $handover->assignment->update([
                'status'             => 'transferred',
                'actual_return_date' => $handover->handover_date,
                'condition_in'       => $handover->condition_in,
                'notes'              => $handover->notes,
            ]);

            AssetAssignment::create([
                'asset_id'        => $handover->asset_id,
                'user_id'         => auth()->id(),
                'department_id'   => auth()->user()->department_id,
                'assigned_by'     => $handover->from_user_id,
                'assigned_date'   => $handover->handover_date,
                'condition_out'   => $handover->condition_in,
                'purpose'         => 'Accepted handover from ' . $handover->fromUser->name,
                'notes'           => $validated['response_notes'] ?? null,
                'status'          => 'active',
            ]);

            $handover->update([
                'status'         => 'accepted',
                'response_notes' => $validated['response_notes'] ?? null,
                'responded_at'   => now(),
            ]);

            $handover->asset->update(['status' => 'assigned']);
        });

        return back()->with('success', 'Asset handover accepted. The asset is now assigned to you.');
    }

    public function rejectHandover(Request $request, AssetHandoverRequest $handover)
    {
        abort_if($handover->to_user_id !== auth()->id(), 403);
        abort_if($handover->status !== 'pending', 422, 'This handover request is no longer pending.');

        $validated = $request->validate([
            'response_notes' => 'nullable|string',
        ]);

        $handover->update([
            'status'         => 'rejected',
            'response_notes' => $validated['response_notes'] ?? null,
            'responded_at'   => now(),
        ]);

        return back()->with('success', 'Asset handover rejected. The asset remains with the current employee.');
    }
}
