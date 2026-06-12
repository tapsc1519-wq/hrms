<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{


    public function index(Request $request)
    {
        $query = AssetAssignment::whereHas('asset', fn($q) => $q->where('organization_id', $this->orgId()))
            ->with(['asset', 'user', 'department', 'assignedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('asset', fn($q) => $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('asset_tag', 'like', '%' . $request->search . '%'))
                ->orWhereHas('user', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
        }

        $assignments = $query->latest()->paginate(20)->withQueryString();
        $staffUsers = User::where('organization_id', $this->orgId())
            ->with('department')
            ->where('role', 'staff')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.assignments.index', compact('assignments', 'staffUsers'));
    }

    public function create()
    {
        $assets      = Asset::where('organization_id', $this->orgId())->where('status', 'available')->orderBy('name')->get();
        $users       = User::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        $departments = Department::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();

        return view('admin.assignments.create', compact('assets', 'users', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id'             => 'required|exists:assets,id',
            'user_id'              => 'nullable|exists:users,id',
            'department_id'        => 'nullable|exists:departments,id',
            'assigned_date'        => 'required|date',
            'expected_return_date' => 'nullable|date|after:assigned_date',
            'condition_out'        => 'required|in:excellent,good,fair,poor',
            'purpose'              => 'nullable|string',
            'notes'                => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        abort_if($asset->organization_id !== $this->orgId(), 403);

        $validated['assigned_by'] = auth()->id();
        $validated['status']      = 'active';

        AssetAssignment::create($validated);
        $asset->update(['status' => 'assigned']);

        return redirect()->route('admin.assignments.index')
            ->with('success', 'Asset assigned successfully.');
    }

    // ─── BULK ASSIGN ──────────────────────────────────────────────────────────

    public function bulk(Request $request)
    {
        $query = Asset::where('organization_id', $this->orgId())
            ->where('status', 'available')
            ->with(['category', 'assetBrand']);

        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('asset_tag', 'like', '%'.$request->search.'%')
                ->orWhere('serial_number', 'like', '%'.$request->search.'%')
            );
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $assets      = $query->orderBy('name')->paginate(50)->withQueryString();
        $users        = User::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        $departments  = \App\Models\Department::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        $categories   = \App\Models\AssetCategory::where('organization_id', $this->orgId())->orderBy('name')->get();

        return view('admin.assignments.bulk', compact('assets', 'users', 'departments', 'categories'));
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'asset_ids'            => 'required|array|min:1',
            'asset_ids.*'          => 'exists:assets,id',
            'user_id'              => 'nullable|exists:users,id',
            'department_id'        => 'nullable|exists:departments,id',
            'assigned_date'        => 'required|date',
            'expected_return_date' => 'nullable|date',
            'condition_out'        => 'required|in:excellent,good,fair,poor',
            'purpose'              => 'nullable|string',
            'notes'                => 'nullable|string',
        ]);

        if (!$request->user_id && !$request->department_id) {
            return back()->withErrors(['user_id' => 'Assign to either a user or a department.']);
        }

        $count = 0;
        foreach ($request->asset_ids as $assetId) {
            $asset = Asset::find($assetId);
            if (!$asset || $asset->organization_id !== $this->orgId() || $asset->status !== 'available') continue;

            AssetAssignment::create([
                'asset_id'             => $asset->id,
                'user_id'              => $request->user_id,
                'department_id'        => $request->department_id,
                'assigned_by'          => auth()->id(),
                'assigned_date'        => $request->assigned_date,
                'expected_return_date' => $request->expected_return_date,
                'condition_out'        => $request->condition_out,
                'purpose'              => $request->purpose,
                'notes'                => $request->notes,
                'status'               => 'active',
            ]);
            $asset->update(['status' => 'assigned']);
            $count++;
        }

        return redirect()->route('admin.assignments.index')
            ->with('success', "{$count} asset" . ($count !== 1 ? 's' : '') . ' assigned successfully.');
    }

    // ─── RETURN ───────────────────────────────────────────────────────────────

    public function returnAsset(Request $request, AssetAssignment $assignment)
    {
        abort_if($assignment->asset->organization_id !== $this->orgId(), 403);

        $request->validate([
            'actual_return_date' => 'required|date',
            'condition_in'       => 'required|in:excellent,good,fair,poor',
            'notes'              => 'nullable|string',
        ]);

        $assignment->update([
            'status'             => 'returned',
            'actual_return_date' => $request->actual_return_date,
            'condition_in'       => $request->condition_in,
            'notes'              => $request->notes,
        ]);

        $assignment->asset->update(['status' => 'available']);

        return back()->with('success', 'Asset returned successfully.');
    }

    public function handover(Request $request, AssetAssignment $assignment)
    {
        abort_if($assignment->asset->organization_id !== $this->orgId(), 403);
        abort_if($assignment->status !== 'active', 422, 'Only active assignments can be handed over.');

        $validated = $request->validate([
            'handover_type' => 'required|in:staff,it_admin',
            'new_user_id'   => 'required_if:handover_type,staff|nullable|exists:users,id',
            'handover_date' => 'required|date',
            'condition_in'  => 'required|in:excellent,good,fair,poor',
            'notes'         => 'nullable|string',
        ]);

        $newUser = null;
        if ($validated['handover_type'] === 'staff') {
            $newUser = User::where('organization_id', $this->orgId())
                ->where('role', 'staff')
                ->where('status', 'active')
                ->findOrFail($validated['new_user_id']);

            if ($assignment->user_id && $assignment->user_id === $newUser->id) {
                return back()->withErrors(['new_user_id' => 'Please select a different staff member for handover.']);
            }
        }

        DB::transaction(function () use ($assignment, $validated, $newUser) {
            $assignment->update([
                'status'             => $validated['handover_type'] === 'staff' ? 'transferred' : 'returned',
                'actual_return_date' => $validated['handover_date'],
                'condition_in'       => $validated['condition_in'],
                'notes'              => $validated['notes'],
            ]);

            if ($newUser) {
                AssetAssignment::create([
                    'asset_id'        => $assignment->asset_id,
                    'user_id'         => $newUser->id,
                    'department_id'   => $newUser->department_id,
                    'assigned_by'     => auth()->id(),
                    'assigned_date'   => $validated['handover_date'],
                    'condition_out'   => $validated['condition_in'],
                    'purpose'         => 'Handover from ' . ($assignment->user?->name ?? 'previous assignee'),
                    'notes'           => $validated['notes'],
                    'status'          => 'active',
                ]);

                $assignment->asset->update(['status' => 'assigned']);
            } else {
                $assignment->asset->update(['status' => 'available']);
            }
        });

        return back()->with('success', $newUser
            ? 'Asset handed over to ' . $newUser->name . ' successfully.'
            : 'Asset handed over to IT/Admin successfully.');
    }
}
