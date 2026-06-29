<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareLicense;
use App\Models\SoftwareRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoftwareRequestController extends Controller
{
    public function index(Request $request)
    {
        $base = SoftwareRequest::where('organization_id', $this->orgId());
        $query = (clone $base)->with(['requester.department', 'software'])->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('requester', fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%"))
                    ->orWhereHas('software', fn ($software) => $software
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('vendor', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        if ($request->sla === 'overdue') {
            $query->whereIn('status', ['pending', 'approved'])->whereNotNull('needed_by')->whereDate('needed_by', '<', today());
        } elseif ($request->sla === 'due_soon') {
            $query->whereIn('status', ['pending', 'approved'])->whereBetween('needed_by', [today(), today()->addDays(7)]);
        } elseif ($request->sla === 'aging') {
            $query->whereIn('status', ['pending', 'approved'])->where('created_at', '<', now()->subDays(7));
        }

        $requests = $query->paginate(25)->withQueryString();
        $stats = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'fulfilled' => (clone $base)->where('status', 'fulfilled')->count(),
            'urgent' => (clone $base)->whereIn('status', ['pending', 'approved'])
                ->whereIn('urgency', ['high', 'critical'])->count(),
            'overdue' => (clone $base)->whereIn('status', ['pending', 'approved'])
                ->whereNotNull('needed_by')->whereDate('needed_by', '<', today())->count(),
            'aging' => (clone $base)->whereIn('status', ['pending', 'approved'])
                ->where('created_at', '<', now()->subDays(7))->count(),
        ];

        return view('admin.software-requests.index', compact('requests', 'stats'));
    }

    public function show(SoftwareRequest $softwareRequest)
    {
        $this->authorizeRequest($softwareRequest);
        $softwareRequest->load([
            'requester.department', 'software', 'reviewer', 'license',
            'assignment', 'fulfiller', 'purchaseOrderItem.purchaseOrder',
        ]);

        $licenses = SoftwareLicense::where('organization_id', $this->orgId())
            ->where('software_id', $softwareRequest->software_id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today());
            })
            ->withCount(['activeAssignments'])
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->get()
            ->filter(fn ($license) => $license->seats > $license->active_assignments_count);

        return view('admin.software-requests.show', compact('softwareRequest', 'licenses'));
    }

    public function approve(Request $request, SoftwareRequest $softwareRequest)
    {
        $this->authorizeRequest($softwareRequest);
        abort_unless($softwareRequest->status === 'pending', 422, 'Only pending requests can be approved.');

        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $softwareRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return back()->with('success', 'Request approved. Allocate an available license to complete it.');
    }

    public function reject(Request $request, SoftwareRequest $softwareRequest)
    {
        $this->authorizeRequest($softwareRequest);
        abort_unless(in_array($softwareRequest->status, ['pending', 'approved'], true), 422, 'This request can no longer be rejected.');

        $validated = $request->validate([
            'review_notes' => 'required|string|min:5|max:1000',
        ]);

        $softwareRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        return redirect()->route('admin.software-requests.index')->with('success', 'Software request rejected.');
    }

    public function fulfill(Request $request, SoftwareRequest $softwareRequest)
    {
        $this->authorizeRequest($softwareRequest);
        abort_unless($softwareRequest->status === 'approved', 422, 'Approve this request before allocating a license.');

        $validated = $request->validate([
            'software_license_id' => 'required|integer|exists:software_licenses,id',
            'allocation_notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($softwareRequest, $validated) {
            $lockedRequest = SoftwareRequest::whereKey($softwareRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedRequest->status === 'approved', 422, 'This request has already been processed.');

            $license = SoftwareLicense::whereKey($validated['software_license_id'])->lockForUpdate()->firstOrFail();
            abort_if($license->organization_id !== $this->orgId() || $license->software_id !== $lockedRequest->software_id, 403);
            abort_unless($license->status === 'active' && (! $license->expiry_date || ! $license->expiry_date->isPast()), 422, 'Choose a valid active license.');
            abort_if($license->activeAssignments()->count() >= $license->seats, 422, 'That license has no seats available. Refresh and choose another license.');

            $existing = SoftwareAssignment::where('user_id', $lockedRequest->requester_id)
                ->where('status', 'active')
                ->whereHas('license', fn ($query) => $query->where('software_id', $lockedRequest->software_id))
                ->exists();
            abort_if($existing, 422, 'This employee already has an active allocation for this software.');

            $assignment = SoftwareAssignment::create([
                'software_license_id' => $license->id,
                'user_id' => $lockedRequest->requester_id,
                'assigned_by' => auth()->id(),
                'assigned_date' => today(),
                'notes' => $validated['allocation_notes'] ?? 'Allocated from software request #'.$lockedRequest->id,
                'status' => 'active',
            ]);

            $lockedRequest->update([
                'status' => 'fulfilled',
                'software_license_id' => $license->id,
                'software_assignment_id' => $assignment->id,
                'fulfilled_by' => auth()->id(),
                'fulfilled_at' => now(),
            ]);
        });

        return redirect()->route('admin.software-requests.show', $softwareRequest)
            ->with('success', 'License allocated. The software now appears in the employee\'s My Software page.');
    }

    private function authorizeRequest(SoftwareRequest $softwareRequest): void
    {
        abort_if($softwareRequest->organization_id !== $this->orgId(), 403);
    }
}
