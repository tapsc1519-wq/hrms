@extends('layouts.app')
@section('title', 'Software Requests')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4>Software Requests</h4>
        <p>Review employee software needs and control how license seats are allocated.</p>
    </div>
    <a href="{{ route('admin.software-licenses.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-key me-1"></i>View Licenses
    </a>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Pending Review', $stats['pending'], 'grad-orange', 'Waiting for a decision'],
        ['Approved', $stats['approved'], 'grad-blue', 'Waiting for allocation'],
        ['Urgent Open', $stats['urgent'], 'grad-red', 'High or critical priority'],
        ['Allocated', $stats['fulfilled'], 'grad-green', 'Completed requests'],
    ] as [$label, $value, $color, $sub])
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient {{ $color }}"><div class="card-body">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-number">{{ $value }}</div>
            <div class="stat-sub">{{ $sub }}</div>
        </div></div>
    </div>
    @endforeach
</div>

<div class="table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Employee, employee code, software or vendor">
            </div>
            <div class="col-sm-5 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'fulfilled' => 'Allocated', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-5 col-lg-2">
                <label class="form-label">Urgency</label>
                <select name="urgency" class="form-select">
                    <option value="">All priorities</option>
                    @foreach(['critical', 'high', 'normal', 'low'] as $value)
                        <option value="{{ $value }}" @selected(request('urgency') === $value)>{{ ucfirst($value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            @if(request()->hasAny(['search', 'status', 'urgency']))
                <div class="col-auto"><a href="{{ route('admin.software-requests.index') }}" class="btn btn-outline-secondary">Clear</a></div>
            @endif
        </form>
    </div>
</div>

<form method="GET" action="{{ route('admin.purchase-orders.create') }}">
<div class="d-flex justify-content-end mb-2">
    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-cart-plus me-1"></i>Create PO for Selected Approved Requests</button>
</div>
<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4" style="width:46px"><input type="checkbox" class="form-check-input" aria-label="Select all approved requests" onclick="document.querySelectorAll('.procurement-request').forEach(item => item.checked = this.checked)"></th>
                    <th>Employee</th>
                    <th>Software</th>
                    <th>Needed By</th>
                    <th>Urgency</th>
                    <th>Status</th>
                    <th>Age</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $softwareRequest)
                <tr>
                    <td class="ps-4">
                        @if($softwareRequest->status === 'approved' && !$softwareRequest->purchase_order_item_id)
                            <input type="checkbox" class="form-check-input procurement-request" name="software_request_ids[]" value="{{ $softwareRequest->id }}" aria-label="Select request {{ $softwareRequest->id }}">
                        @else
                            <input type="checkbox" class="form-check-input" disabled>
                        @endif
                    </td>
                    <td>
                        <div class="fw-bold">{{ $softwareRequest->requester->name }}</div>
                        <div class="text-muted small">
                            {{ $softwareRequest->requester->employee_id ?: 'No employee code' }}
                            @if($softwareRequest->requester->department) &middot; {{ $softwareRequest->requester->department->name }} @endif
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $softwareRequest->software->name }}</div>
                        <div class="text-muted small">{{ $softwareRequest->software->vendor ?: 'Vendor not specified' }}</div>
                    </td>
                    <td>{{ $softwareRequest->needed_by?->format('d M Y') ?? 'No fixed date' }}</td>
                    <td><span class="badge bg-{{ $softwareRequest->urgency_badge }}">{{ ucfirst($softwareRequest->urgency) }}</span></td>
                    <td><span class="badge bg-{{ $softwareRequest->status_badge }}">{{ $softwareRequest->status_label }}</span></td>
                    <td><div>{{ $softwareRequest->created_at->diffForHumans() }}</div><div class="text-muted small">#{{ $softwareRequest->id }}</div></td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.software-requests.show', $softwareRequest) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Review</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-person-check fs-1 d-block mb-2 opacity-25"></i>No software requests match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())<div class="p-3 border-top">{{ $requests->links() }}</div>@endif
</div>
</form>
@endsection
