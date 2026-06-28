<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SoftwareAssignment;
use App\Models\Software;
use App\Models\SoftwareRequest;
use App\Models\SoftwareUsageReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoftwareController extends Controller
{
    public function index(Request $request)
    {
        $query = SoftwareAssignment::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['license.software', 'license.supplier'])
            ->latest('assigned_date');

        if ($request->filled('search')) {
            $query->whereHas('license.software', fn($q) =>
                $q->where('name', 'like', '%'.$request->search.'%')
            );
        }

        $assignments = $query->paginate(20)->withQueryString();

        $usageReviews = SoftwareUsageReview::where('organization_id', $this->orgId())
            ->where('status', 'pending_user')
            ->whereHas('assignment', fn ($assignment) => $assignment->where('user_id', auth()->id()))
            ->with(['assignment.license.software'])
            ->orderBy('due_date')
            ->get();

        return view('staff.software.index', compact('assignments', 'usageReviews'));
    }

    public function requests(Request $request)
    {
        $query = SoftwareRequest::where('organization_id', $this->orgId())
            ->where('requester_id', auth()->id())
            ->with(['software', 'reviewer'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('staff.software.requests', [
            'requests' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function createRequest()
    {
        $software = Software::where('organization_id', $this->orgId())
            ->orderBy('name')
            ->get(['id', 'name', 'vendor', 'category', 'software_type']);

        return view('staff.software.request-create', compact('software'));
    }

    public function storeRequest(Request $request)
    {
        $validated = $request->validate([
            'software_id' => 'required|integer|exists:software,id',
            'urgency' => 'required|in:low,normal,high,critical',
            'needed_by' => 'nullable|date|after_or_equal:today',
            'business_justification' => 'required|string|min:20|max:2000',
        ]);

        $software = Software::where('organization_id', $this->orgId())
            ->findOrFail($validated['software_id']);

        $alreadyAssigned = SoftwareAssignment::where('user_id', auth()->id())
            ->where('status', 'active')
            ->whereHas('license', fn ($query) => $query->where('software_id', $software->id))
            ->exists();

        if ($alreadyAssigned) {
            return back()->withInput()->withErrors(['software_id' => 'This software is already allocated to you.']);
        }

        $openRequest = SoftwareRequest::where('organization_id', $this->orgId())
            ->where('requester_id', auth()->id())
            ->where('software_id', $software->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($openRequest) {
            return back()->withInput()->withErrors(['software_id' => 'You already have an open request for this software.']);
        }

        SoftwareRequest::create(array_merge($validated, [
            'organization_id' => $this->orgId(),
            'requester_id' => auth()->id(),
            'status' => 'pending',
        ]));

        return redirect()->route('staff.software-requests.index')->with('success', 'Software request submitted for review.');
    }

    public function cancelRequest(SoftwareRequest $softwareRequest)
    {
        abort_if($softwareRequest->organization_id !== $this->orgId() || $softwareRequest->requester_id !== auth()->id(), 403);
        abort_unless($softwareRequest->status === 'pending', 422, 'Only pending requests can be cancelled.');

        $softwareRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Software request cancelled.');
    }

    public function retainUsage(Request $request, SoftwareUsageReview $review)
    {
        $this->authorizeUsageReview($review);
        abort_unless($review->status === 'pending_user', 422, 'This review is already closed.');
        $validated = $request->validate(['decision_notes' => 'required|string|min:5|max:1000']);

        $review->update([
            'status' => 'retained',
            'decision_notes' => $validated['decision_notes'],
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        return back()->with('success', 'Your response was recorded and the license remains assigned to you.');
    }

    public function releaseUsage(Request $request, SoftwareUsageReview $review)
    {
        $this->authorizeUsageReview($review);
        abort_unless($review->status === 'pending_user', 422, 'This review is already closed.');
        $validated = $request->validate(['decision_notes' => 'required|string|min:5|max:1000']);

        DB::transaction(function () use ($review, $validated) {
            $assignment = SoftwareAssignment::whereKey($review->software_assignment_id)->lockForUpdate()->firstOrFail();
            abort_if($assignment->user_id !== auth()->id(), 403);
            if ($assignment->status === 'active') {
                $assignment->update(['status' => 'returned', 'returned_date' => today()]);
            }
            $review->update([
                'status' => 'reclaimed',
                'decision_notes' => $validated['decision_notes'],
                'decided_by' => auth()->id(),
                'decided_at' => now(),
            ]);
        });

        return back()->with('success', 'The license was released and returned to the available pool.');
    }

    private function authorizeUsageReview(SoftwareUsageReview $review): void
    {
        abort_if($review->organization_id !== $this->orgId(), 403);
        abort_unless($review->assignment()->where('user_id', auth()->id())->exists(), 403);
    }
}
