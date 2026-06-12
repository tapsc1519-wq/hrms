@extends('layouts.app')

@section('title', 'Disposal Details')

@section('content')
@php
    $context = request('context', 'history');
    $backRoute = match ($context) {
        'requests' => route('admin.disposals.requests'),
        'approvals' => route('admin.disposals.approvals'),
        default => route('admin.disposals.history'),
    };
    $isApprovalContext = $context === 'approvals';
    $isRequestContext = $context === 'requests';
@endphp
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ $backRoute }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to {{ $context === 'approvals' ? 'Approvals' : ($context === 'requests' ? 'Requests' : 'History') }}</a>
        <h4>{{ $disposal->asset?->name }} Disposal</h4>
        <p>{{ $disposal->asset?->asset_tag }} &middot; {{ $disposal->method_label }}</p>
    </div>
    <span class="badge bg-{{ $disposal->status_badge }} fs-6">{{ ucfirst($disposal->status) }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="form-card mb-4">
            <div class="form-card-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="icon-wrap icon-red"><i class="bi bi-recycle"></i></span>
                    <div>
                        <h5 class="mb-1">Disposal Summary</h5>
                        <p class="text-muted small mb-0">Lifecycle and financial details for this disposal.</p>
                    </div>
                </div>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="text-muted small text-uppercase fw-bold">Asset</div>
                            <div class="fw-bold">{{ $disposal->asset?->name }}</div>
                            <div class="small text-muted">{{ $disposal->asset?->asset_tag }} &middot; {{ ucfirst($disposal->asset?->status) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="text-muted small text-uppercase fw-bold">Method</div>
                            <div class="fw-bold">{{ $disposal->method_label }}</div>
                            <div class="small text-muted">{{ $disposal->recipient_name ?: 'No recipient recorded' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border">
                            <div class="text-muted small">Expected Value</div>
                            <div class="fw-bold">&#8377;{{ number_format((float) ($disposal->expected_value ?? 0), 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border">
                            <div class="text-muted small">Recovered Value</div>
                            <div class="fw-bold">&#8377;{{ number_format((float) ($disposal->recovered_value ?? 0), 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border">
                            <div class="text-muted small">Net Recovery</div>
                            <div class="fw-bold {{ $disposal->net_recovery < 0 ? 'text-danger' : 'text-success' }}">&#8377;{{ number_format($disposal->net_recovery, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Reason</label>
                        <div class="p-3 rounded-3 border bg-light" style="white-space:pre-line">{{ $disposal->reason }}</div>
                    </div>
                    @if($disposal->approval_notes)
                        <div class="col-12">
                            <label class="form-label">Approval / Rejection Notes</label>
                            <div class="p-3 rounded-3 border bg-light" style="white-space:pre-line">{{ $disposal->approval_notes }}</div>
                        </div>
                    @endif
                    @if($disposal->completion_notes)
                        <div class="col-12">
                            <label class="form-label">Completion Notes</label>
                            <div class="p-3 rounded-3 border bg-light" style="white-space:pre-line">{{ $disposal->completion_notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <h5 class="mb-0">Timeline</h5>
            </div>
            <div class="form-card-body">
                <div class="d-grid gap-3">
                    <div class="d-flex gap-3">
                        <span class="icon-wrap icon-blue"><i class="bi bi-send"></i></span>
                        <div>
                            <div class="fw-bold">Requested</div>
                            <div class="text-muted small">{{ $disposal->requested_date?->format('d-m-Y') }} by {{ $disposal->requestedBy?->name }}</div>
                        </div>
                    </div>
                    @if($disposal->approved_date)
                        <div class="d-flex gap-3">
                            <span class="icon-wrap {{ $disposal->status === 'rejected' ? 'icon-red' : 'icon-green' }}"><i class="bi {{ $disposal->status === 'rejected' ? 'bi-x-lg' : 'bi-check2' }}"></i></span>
                            <div>
                                <div class="fw-bold">{{ $disposal->status === 'rejected' ? 'Rejected' : 'Approved' }}</div>
                                <div class="text-muted small">{{ $disposal->approved_date?->format('d-m-Y') }} by {{ $disposal->approvedBy?->name }}</div>
                            </div>
                        </div>
                    @endif
                    @if($disposal->disposed_date)
                        <div class="d-flex gap-3">
                            <span class="icon-wrap icon-slate"><i class="bi bi-archive"></i></span>
                            <div>
                                <div class="fw-bold">Completed</div>
                                <div class="text-muted small">{{ $disposal->disposed_date?->format('d-m-Y') }} by {{ $disposal->completedBy?->name }}</div>
                                @if($disposal->certificate_number)
                                    <div class="text-muted small">Certificate: {{ $disposal->certificate_number }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if($isApprovalContext && $disposal->status === 'pending' && auth()->user()->hasPermission('assets.disposal.approve'))
            <div class="form-card mb-4">
                <div class="form-card-header"><h5 class="mb-0">Review Request</h5></div>
                <div class="form-card-body">
                    <form method="POST" action="{{ route('admin.disposals.approve', $disposal) }}" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <label class="form-label">Approval Notes</label>
                        <textarea name="approval_notes" rows="3" class="form-control mb-3"></textarea>
                        <button class="btn btn-success w-100"><i class="bi bi-check2 me-1"></i> Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.disposals.reject', $disposal) }}">
                        @csrf
                        @method('PATCH')
                        <label class="form-label">Rejection Reason <span class="req">*</span></label>
                        <textarea name="approval_notes" rows="3" class="form-control mb-3" required></textarea>
                        <button class="btn btn-outline-danger w-100"><i class="bi bi-x-lg me-1"></i> Reject</button>
                    </form>
                </div>
            </div>
        @endif

        @if($isApprovalContext && $disposal->status === 'approved' && auth()->user()->hasPermission('assets.disposal.complete'))
            <div class="form-card mb-4">
                <div class="form-card-header"><h5 class="mb-0">Complete Disposal</h5></div>
                <div class="form-card-body">
                    <form method="POST" action="{{ route('admin.disposals.complete', $disposal) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label">Disposed Date <span class="req">*</span></label>
                            <input type="date" name="disposed_date" value="{{ now()->toDateString() }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Recovered Value</label>
                            <input type="number" step="0.01" min="0" name="recovered_value" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Disposal Cost</label>
                            <input type="number" step="0.01" min="0" name="disposal_cost" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Recipient / Recycler / Buyer</label>
                            <input type="text" name="recipient_name" value="{{ $disposal->recipient_name }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Certificate Number</label>
                            <input type="text" name="certificate_number" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Completion Notes</label>
                            <textarea name="completion_notes" rows="3" class="form-control"></textarea>
                        </div>
                        <button class="btn btn-primary w-100"><i class="bi bi-archive me-1"></i> Mark as Disposed</button>
                    </form>
                </div>
            </div>
        @endif

        @if($isRequestContext && in_array($disposal->status, ['pending', 'approved'], true) && auth()->user()->hasPermission('assets.disposal.request'))
            <form method="POST" action="{{ route('admin.disposals.cancel', $disposal) }}" onsubmit="return confirm('Cancel this disposal request?')">
                @csrf
                @method('PATCH')
                <button class="btn btn-outline-secondary w-100">Cancel Request</button>
            </form>
        @endif

        @if(!$isApprovalContext && !($isRequestContext && in_array($disposal->status, ['pending', 'approved'], true)))
            <div class="form-card">
                <div class="form-card-body text-muted small">
                    This page is read-only in the current section. Use <strong>Disposal Approvals</strong> to approve, reject, or complete this disposal.
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
