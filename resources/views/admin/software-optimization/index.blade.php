@extends('layouts.app')
@section('title', 'Software Usage Optimization')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4>Software Usage Optimization</h4>
        <p>Review inactive paid allocations and recover seats employees no longer need.</p>
    </div>
    <a href="{{ route('admin.software-discovery.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-hdd-network me-1"></i>Discovery Inventory</a>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Reclaim Candidates', $stats['candidates'], 'grad-orange', 'Inactive beyond threshold', 'candidates'],
        ['Recently Used', $stats['recent'], 'grad-green', 'Active within threshold', 'recent'],
        ['No Usage Data', $stats['no_data'], 'grad-purple', 'Telemetry needs attention', 'no_data'],
        ['Open Reviews', $stats['open_reviews'], 'grad-blue', 'Waiting for employee', null],
    ] as [$label, $value, $color, $sub, $target])
    <div class="col-sm-6 col-xl-3">
        @if($target)<a href="{{ route('admin.software-optimization.index', ['view' => $target, 'days' => $threshold]) }}" class="text-decoration-none">@endif
        <div class="stat-card-gradient {{ $color }}"><div class="card-body"><div class="stat-label">{{ $label }}</div><div class="stat-number">{{ $value }}</div><div class="stat-sub">{{ $sub }}</div></div></div>
        @if($target)</a>@endif
    </div>
    @endforeach
</div>

<div class="table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4"><label class="form-label">Search</label><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Employee, employee code, software or vendor"></div>
            <div class="col-sm-6 col-lg-3"><label class="form-label">Usage View</label><select name="view" class="form-select"><option value="candidates" @selected($view === 'candidates')>Reclaim candidates</option><option value="recent" @selected($view === 'recent')>Recently used</option><option value="no_data" @selected($view === 'no_data')>No usage data</option></select></div>
            <div class="col-sm-6 col-lg-2"><label class="form-label">Inactive For</label><select name="days" class="form-select">@foreach([30,60,90,120,180] as $days)<option value="{{ $days }}" @selected($threshold === $days)>{{ $days }} days</option>@endforeach</select></div>
            <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button></div>
            @if(request()->hasAny(['search','view','days']))<div class="col-auto"><a href="{{ route('admin.software-optimization.index') }}" class="btn btn-outline-secondary">Reset</a></div>@endif
        </form>
    </div>
</div>

@if($view === 'no_data')
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>These allocations have no matching mapped usage telemetry. Confirm the device agent or discovery import before deciding that a license is unused.</div>
@endif

<div class="table-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">{{ $view === 'candidates' ? 'Reclaim Candidates' : ($view === 'recent' ? 'Recently Used Allocations' : 'Allocations Without Usage Data') }}</span><span class="badge bg-light text-dark">{{ $assignments->total() }}</span></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Employee</th><th>Software</th><th>Assigned</th><th>Last Used</th><th>{{ $view === 'no_data' ? 'Allocation Age' : 'Inactivity' }}</th><th>Est. Annual Cost</th><th class="text-end pe-4">Action</th></tr></thead>
            <tbody>
            @forelse($assignments as $assignment)
                @php $hasOpenReview = $openReviewAssignmentIds->contains($assignment->id); @endphp
                <tr>
                    <td class="ps-4"><div class="fw-bold">{{ $assignment->user?->name ?? 'Unknown employee' }}</div><div class="text-muted small">{{ $assignment->user?->employee_id ?: ($assignment->user?->department?->name ?? 'No employee code') }}</div></td>
                    <td><div class="fw-bold">{{ $assignment->license?->software?->name ?? 'Unknown software' }}</div><div class="text-muted small">{{ $assignment->license?->software?->vendor ?: $assignment->license?->license_type_label }}</div></td>
                    <td>{{ $assignment->assigned_date->format('d M Y') }}</td>
                    <td><div class="fw-semibold">{{ $assignment->last_used_date ? \Illuminate\Support\Carbon::parse($assignment->last_used_date)->format('d M Y') : ($view === 'no_data' ? 'No telemetry' : 'Never recorded') }}</div></td>
                    <td><span class="badge {{ $view === 'recent' ? 'bg-success' : ($view === 'no_data' ? 'bg-secondary' : 'bg-warning text-dark') }}">{{ number_format($assignment->inactivity_days) }} days</span></td>
                    <td><div class="fw-bold">Rs {{ number_format($assignment->estimated_annual_savings, 2) }}</div><div class="text-muted small">recoverable recurring cost</div></td>
                    <td class="text-end pe-4">
                        @if($hasOpenReview)
                            <span class="badge bg-info">Review Open</span>
                        @elseif($view === 'candidates' && auth()->user()->hasPermission('software.optimization.manage'))
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#startReview{{ $assignment->id }}"><i class="bi bi-send me-1"></i>Ask Employee</button>
                        @elseif($view === 'no_data')
                            <a href="{{ route('admin.software-discovery.index', ['search' => $assignment->user?->email]) }}" class="btn btn-sm btn-outline-secondary">Check Discovery</a>
                        @else
                            <span class="text-muted small">No action needed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-graph-down-arrow fs-1 d-block mb-2 opacity-25"></i>No allocations match this usage view.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($assignments->hasPages())<div class="p-3 border-top">{{ $assignments->links() }}</div>@endif
</div>

<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Usage Review History</span><span class="badge bg-light text-dark">{{ $reviews->total() }}</span></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th class="ps-4">Employee</th><th>Software</th><th>Evidence</th><th>Status</th><th>Owner / Due</th><th class="text-end pe-4">Decision</th></tr></thead>
            <tbody>
            @forelse($reviews as $review)
                <tr>
                    <td class="ps-4"><div class="fw-bold">{{ $review->assignment?->user?->name ?? 'Unknown employee' }}</div><div class="text-muted small">Review #{{ $review->id }}</div></td>
                    <td><div class="fw-bold">{{ $review->assignment?->license?->software?->name ?? 'Unknown software' }}</div><div class="text-muted small">Rs {{ number_format((float)$review->estimated_annual_savings, 2) }} estimated yearly</div></td>
                    <td><div>{{ $review->last_used_date?->format('d M Y') ?? 'Never recorded' }}</div><div class="text-muted small">{{ $review->inactivity_days ?? 0 }} inactive days</div></td>
                    <td><span class="badge bg-{{ $review->status_badge }}">{{ $review->status_label }}</span>@if($review->decision_notes)<div class="text-muted small mt-1" style="max-width:260px">{{ $review->decision_notes }}</div>@endif</td>
                    <td><div>{{ $review->owner?->name ?? 'Unassigned' }}</div><div class="text-muted small">{{ $review->due_date?->format('d M Y') ?? 'No due date' }}</div></td>
                    <td class="text-end pe-4">
                        @if($review->status === 'pending_user' && auth()->user()->hasPermission('software.optimization.manage'))
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#retainReview{{ $review->id }}">Retain</button>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reclaimReview{{ $review->id }}">Reclaim</button>
                        @else<span class="text-muted small">{{ $review->decidedBy?->name ?? 'Awaiting response' }}</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No usage reviews have been created yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($reviews->hasPages())<div class="p-3 border-top">{{ $reviews->links() }}</div>@endif
</div>

@foreach($assignments as $assignment)
@if($view === 'candidates' && !$openReviewAssignmentIds->contains($assignment->id))
<div class="modal fade" id="startReview{{ $assignment->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('admin.software-optimization.reviews.store', $assignment) }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Ask {{ $assignment->user?->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted small">Ask whether {{ $assignment->license?->software?->name }} is still needed before reclaiming the seat.</p><div class="mb-3"><label class="form-label">Response Due Date</label><input type="date" name="due_date" class="form-control" value="{{ today()->addDays(7)->toDateString() }}" min="{{ today()->toDateString() }}"></div><div class="mb-3"><label class="form-label">Review Owner</label><select name="owner_id" class="form-select"><option value="">Current administrator</option>@foreach($owners as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>@endforeach</select></div><div><label class="form-label">Message to Employee</label><textarea name="notes" class="form-control" rows="3">Please confirm whether this software is still required for your work.</textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="bi bi-send me-1"></i>Send Review</button></div></form></div></div>
@endif
@endforeach

@foreach($reviews->where('status', 'pending_user') as $review)
<div class="modal fade" id="retainReview{{ $review->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('admin.software-optimization.reviews.retain', $review) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Retain License</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Reason for Keeping This License</label><textarea name="decision_notes" class="form-control" rows="4" required minlength="5" placeholder="Record the business reason or employee confirmation"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Retain Allocation</button></div></form></div></div>
<div class="modal fade" id="reclaimReview{{ $review->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('admin.software-optimization.reviews.reclaim', $review) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Reclaim License</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-warning small">This returns the employee's active allocation and makes the seat available immediately.</div><label class="form-label">Reclamation Reason</label><textarea name="decision_notes" class="form-control" rows="4" required minlength="5" placeholder="Record the evidence and decision"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Reclaim License</button></div></form></div></div>
@endforeach
@endsection
