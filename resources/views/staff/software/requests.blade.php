@extends('layouts.app')
@section('title', 'My Software Requests')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4>My Software Requests</h4>
        <p>Track software you have asked your organization to provide.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('staff.my-software.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-display me-1"></i>My Software
        </a>
        <a href="{{ route('staff.software-requests.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>New Request
        </a>
    </div>
</div>

<div class="table-card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['pending' => 'Pending Review', 'approved' => 'Approved', 'fulfilled' => 'Allocated', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            @if(request('status'))
            <div class="col-auto">
                <a href="{{ route('staff.software-requests.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Software</th>
                    <th>Requested</th>
                    <th>Needed By</th>
                    <th>Urgency</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $softwareRequest)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">{{ $softwareRequest->software->name }}</div>
                        <div class="text-muted small">{{ $softwareRequest->software->vendor ?: 'Vendor not specified' }}</div>
                    </td>
                    <td>
                        <div>{{ $softwareRequest->created_at->format('d M Y') }}</div>
                        <div class="text-muted small">Request #{{ $softwareRequest->id }}</div>
                    </td>
                    <td>{{ $softwareRequest->needed_by?->format('d M Y') ?? 'No fixed date' }}</td>
                    <td><span class="badge bg-{{ $softwareRequest->urgency_badge }}">{{ ucfirst($softwareRequest->urgency) }}</span></td>
                    <td>
                        <span class="badge bg-{{ $softwareRequest->status_badge }}">{{ $softwareRequest->status_label }}</span>
                        @if($softwareRequest->review_notes)
                            <div class="text-muted small mt-1" style="max-width:280px">{{ $softwareRequest->review_notes }}</div>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        @if($softwareRequest->status === 'pending')
                        <form method="POST" action="{{ route('staff.software-requests.cancel', $softwareRequest) }}" onsubmit="return confirm('Cancel this software request?')">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancel</button>
                        </form>
                        @elseif($softwareRequest->status === 'fulfilled')
                        <a href="{{ route('staff.my-software.index') }}" class="btn btn-sm btn-outline-success">View Allocation</a>
                        @else
                        <span class="text-muted small">No action needed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-person-check fs-1 d-block mb-2 opacity-25"></i>
                        You have not requested any software yet.
                        <div class="mt-3"><a href="{{ route('staff.software-requests.create') }}" class="btn btn-primary btn-sm">Request Software</a></div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
        <div class="p-3 border-top">{{ $requests->links() }}</div>
    @endif
</div>
@endsection
