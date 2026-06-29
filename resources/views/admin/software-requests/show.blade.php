@extends('layouts.app')
@section('title', 'Review Software Request')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="mb-0">Software Request #{{ $softwareRequest->id }}</h4>
            <span class="badge bg-{{ $softwareRequest->status_badge }}">{{ $softwareRequest->status_label }}</span>
            <span class="badge bg-{{ $softwareRequest->sla_badge }}">{{ $softwareRequest->sla_label }}</span>
        </div>
        <p>Review the employee's need, make a decision, and allocate the correct license.</p>
    </div>
    <a href="{{ route('admin.software-requests.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Requests</a>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="table-card mb-3">
            <div class="card-body p-4">
                <h6 class="mb-3"><i class="bi bi-person me-2 text-primary"></i>Employee and Request</h6>
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">Employee</div><div class="fw-bold">{{ $softwareRequest->requester->name }}</div><div class="text-muted small">{{ $softwareRequest->requester->employee_id ?: 'No employee code' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Department</div><div class="fw-bold">{{ $softwareRequest->requester->department?->name ?? 'Not assigned' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Software</div><div class="fw-bold">{{ $softwareRequest->software->name }}</div><div class="text-muted small">{{ $softwareRequest->software->vendor ?: 'Vendor not specified' }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Urgency</div><span class="badge bg-{{ $softwareRequest->urgency_badge }}">{{ ucfirst($softwareRequest->urgency) }}</span></div>
                    <div class="col-md-3"><div class="text-muted small">Needed By</div><div class="fw-bold">{{ $softwareRequest->needed_by?->format('d M Y') ?? 'No fixed date' }}</div></div>
                    <div class="col-12"><div class="text-muted small mb-1">Business Reason</div><div class="p-3 bg-light border rounded">{{ $softwareRequest->business_justification }}</div></div>
                </div>
            </div>
        </div>

        @if($softwareRequest->status === 'approved')
        <div class="table-card mb-3">
            <div class="card-body p-4">
                <h6 class="mb-1"><i class="bi bi-key me-2 text-primary"></i>Allocate a License</h6>
                <p class="text-muted small mb-3">Choose one valid license. The available seat count is checked again when you allocate it.</p>
                @if($licenses->isEmpty())
                    @if($softwareRequest->purchaseOrderItem)
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-cart-check me-1"></i>
                        This request is linked to
                        <a href="{{ route('admin.purchase-orders.show', $softwareRequest->purchaseOrderItem->purchaseOrder) }}" class="alert-link">{{ $softwareRequest->purchaseOrderItem->purchaseOrder->po_number }}</a>.
                        It will be allocated automatically when the software seats are received.
                    </div>
                    @else
                    <div class="alert alert-warning mb-0 d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div><i class="bi bi-exclamation-triangle me-1"></i>No valid seat is available. Create a purchase order for the required license.</div>
                        <a href="{{ route('admin.purchase-orders.create', ['software_request_ids' => [$softwareRequest->id]]) }}" class="btn btn-warning btn-sm"><i class="bi bi-cart-plus me-1"></i>Create Purchase Order</a>
                    </div>
                    @endif
                @else
                <form method="POST" action="{{ route('admin.software-requests.fulfill', $softwareRequest) }}">
                    @csrf
                    <div class="table-responsive border rounded mb-3">
                        <table class="table align-middle mb-0">
                            <thead><tr><th style="width:48px"></th><th>License</th><th>Available</th><th>Expiry</th><th>Cost</th></tr></thead>
                            <tbody>
                            @foreach($licenses as $license)
                                <tr>
                                    <td class="text-center"><input class="form-check-input" type="radio" name="software_license_id" value="{{ $license->id }}" required></td>
                                    <td><div class="fw-bold">{{ $license->license_type_label }}</div><div class="text-muted small">{{ $license->purchase_batch ?: 'License #'.$license->id }}</div></td>
                                    <td><strong>{{ $license->seats - $license->active_assignments_count }}</strong> of {{ $license->seats }}</td>
                                    <td>{{ $license->expiry_date?->format('d M Y') ?? 'No expiry' }}</td>
                                    <td>Rs {{ number_format($license->unit_cost ?: ($license->seats ? $license->total_cost / $license->seats : 0), 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('software_license_id')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <div class="mb-3"><label class="form-label">Allocation Note</label><textarea name="allocation_notes" class="form-control" rows="2" maxlength="500" placeholder="Optional installation or access instructions">{{ old('allocation_notes') }}</textarea></div>
                    @if(auth()->user()->hasPermission('software.requests.fulfill'))
                        <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Allocate License and Complete</button>
                    @endif
                </form>
                @endif
            </div>
        </div>
        @endif

        @if($softwareRequest->status === 'fulfilled')
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-1"></i>
            License #{{ $softwareRequest->software_license_id }} was allocated by {{ $softwareRequest->fulfiller?->name ?? 'an administrator' }} on {{ $softwareRequest->fulfilled_at?->format('d M Y, h:i A') }}.
        </div>
        @endif
    </div>

    <div class="col-xl-4">
        @if($softwareRequest->status === 'pending' && auth()->user()->hasPermission('software.requests.review'))
        <div class="table-card mb-3">
            <div class="card-body p-4">
                <h6 class="mb-3">Review Decision</h6>
                <form method="POST" action="{{ route('admin.software-requests.approve', $softwareRequest) }}" class="mb-3">
                    @csrf @method('PATCH')
                    <label class="form-label">Approval Note</label>
                    <textarea name="review_notes" class="form-control mb-2" rows="3" maxlength="1000" placeholder="Optional note for the employee"></textarea>
                    <button class="btn btn-success w-100"><i class="bi bi-check-lg me-1"></i>Approve Request</button>
                </form>
                <hr>
                <form method="POST" action="{{ route('admin.software-requests.reject', $softwareRequest) }}">
                    @csrf @method('PATCH')
                    <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea name="review_notes" class="form-control mb-2 @error('review_notes') is-invalid @enderror" rows="3" required minlength="5" maxlength="1000" placeholder="Explain why this request cannot be approved"></textarea>
                    @error('review_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button class="btn btn-outline-danger w-100"><i class="bi bi-x-lg me-1"></i>Reject Request</button>
                </form>
            </div>
        </div>
        @endif

        <div class="table-card">
            <div class="card-body p-4">
                <h6 class="mb-3">Request Timeline</h6>
                <div class="mb-3"><div class="text-muted small">Submitted</div><div class="fw-bold">{{ $softwareRequest->created_at->format('d M Y, h:i A') }}</div></div>
                @if($softwareRequest->reviewed_at)
                    <div class="mb-3"><div class="text-muted small">Reviewed by</div><div class="fw-bold">{{ $softwareRequest->reviewer?->name ?? 'Unknown user' }}</div><div class="text-muted small">{{ $softwareRequest->reviewed_at->format('d M Y, h:i A') }}</div></div>
                @endif
                @if($softwareRequest->review_notes)
                    <div><div class="text-muted small">Review Note</div><div>{{ $softwareRequest->review_notes }}</div></div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
