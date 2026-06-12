@extends('layouts.app')
@section('title', 'Review Request — ' . $assetRequest->request_number)

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('admin.requests.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Requests</a>
        <h4>{{ $assetRequest->request_number }}</h4>
        <p>Submitted by <strong>{{ $assetRequest->requester->name }}</strong> on {{ $assetRequest->request_date->format('d-m-Y') }}</p>
    </div>
    <span class="badge fs-6 bg-{{ $assetRequest->status_badge }} mt-1">
        {{ ucfirst($assetRequest->status) }}
    </span>
</div>

<div class="row g-4">

    {{-- LEFT: Request Details --}}
    <div class="col-lg-7">

        {{-- Request Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-clipboard-check"></i></span>
                Request Details
            </div>
            <div class="form-card-body">
                <dl class="row mb-0" style="font-size:.875rem;row-gap:.6rem">
                    <dt class="col-4" style="color:#64748b;font-weight:600">Requester</dt>
                    <dd class="col-8 mb-0 fw-semibold">
                        {{ $assetRequest->requester->name }}
                        @if($assetRequest->requester->job_title)
                        <small class="text-muted fw-normal"> — {{ $assetRequest->requester->job_title }}</small>
                        @endif
                    </dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Request Date</dt>
                    <dd class="col-8 mb-0">{{ $assetRequest->request_date->format('d-m-Y') }}</dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Required By</dt>
                    <dd class="col-8 mb-0">{{ $assetRequest->required_date?->format('d-m-Y') ?? '— Flexible —' }}</dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Priority</dt>
                    <dd class="col-8 mb-0">
                        <span class="badge bg-{{ $assetRequest->priority_badge }}">{{ ucfirst($assetRequest->priority) }}</span>
                    </dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Asset Category</dt>
                    <dd class="col-8 mb-0 fw-semibold">
                        {{ $assetRequest->category?->name ?? 'Any available' }}
                    </dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Request Type</dt>
                    <dd class="col-8 mb-0">{{ $assetRequest->request_type_label }}</dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Quantity</dt>
                    <dd class="col-8 mb-0">{{ $assetRequest->quantity ?? 1 }}</dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Reason</dt>
                    <dd class="col-8 mb-0">{{ $assetRequest->reason }}</dd>
                </dl>
            </div>
        </div>

        {{-- Approve / Reject actions --}}
        @if($assetRequest->status === 'pending')
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card" style="border-color:#bbf7d0">
                    <div class="form-card-header" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7)">
                        <span class="icon-wrap" style="background:#d1fae5;color:#065f46"><i class="bi bi-check-circle"></i></span>
                        <span style="color:#166534">Approve</span>
                    </div>
                    <div class="form-card-body">
                        <form action="{{ route('admin.requests.approve', $assetRequest) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label">Review Notes</label>
                                <textarea name="review_notes" class="form-control" rows="3"
                                          placeholder="Optional approval notes…"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-1"></i>Approve Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card" style="border-color:#fecaca">
                    <div class="form-card-header" style="background:linear-gradient(135deg,#fef2f2,#fee2e2)">
                        <span class="icon-wrap" style="background:#fee2e2;color:#991b1b"><i class="bi bi-x-circle"></i></span>
                        <span style="color:#991b1b">Reject</span>
                    </div>
                    <div class="form-card-body">
                        <form action="{{ route('admin.requests.reject', $assetRequest) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label">Rejection Reason <span class="req">*</span></label>
                                <textarea name="review_notes" class="form-control" rows="3"
                                          placeholder="Why is this request being rejected?" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-x-circle me-1"></i>Reject Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Fulfill --}}
        @if($assetRequest->status === 'approved')
        <div class="form-card" style="border-color:#bfdbfe">
            <div class="form-card-header" style="background:linear-gradient(135deg,#eff6ff,#dbeafe)">
                <span class="icon-wrap" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-box-seam"></i></span>
                <span style="color:#1e40af">Fulfill Request</span>
            </div>
            <div class="form-card-body">
                <form action="{{ route('admin.requests.fulfill', $assetRequest) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Select Asset{{ ($assetRequest->quantity ?? 1) > 1 ? 's' : '' }} to Assign <span class="req">*</span></label>
                            <select name="fulfilled_asset_ids[]" class="form-select" required multiple size="8">
                                @foreach($availableAssets as $a)
                                <option value="{{ $a->id }}">
                                    {{ $a->name }}
                                    @if($a->asset_tag) ({{ $a->asset_tag }}) @endif
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">Select at least {{ $assetRequest->quantity ?? 1 }} available asset{{ ($assetRequest->quantity ?? 1) > 1 ? 's' : '' }}. Hold Ctrl to select multiple.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Assigned Date <span class="req">*</span></label>
                            <input type="date" name="assigned_date" class="form-control"
                                   value="{{ today()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Fulfill & Assign Asset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>

    {{-- RIGHT: Review Info --}}
    <div class="col-lg-5">

        @if($assetRequest->reviewedBy)
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-purple"><i class="bi bi-person-check"></i></span>
                Review Information
            </div>
            <div class="form-card-body">
                <dl class="mb-0" style="font-size:.875rem">
                    <dt class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Reviewed By</dt>
                    <dd class="mb-3 fw-semibold">{{ $assetRequest->reviewedBy->name }}</dd>
                    <dt class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Reviewed At</dt>
                    <dd class="mb-3">{{ $assetRequest->reviewed_at?->format('d-m-Y H:i') ?? '—' }}</dd>
                    @if($assetRequest->review_notes)
                    <dt class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Notes</dt>
                    <dd class="mb-0 p-2 rounded-2" style="background:#f8fafc;border:1px solid #e2e8f0">{{ $assetRequest->review_notes }}</dd>
                    @endif
                </dl>
            </div>
        </div>
        @endif

        @if($assetRequest->fulfilledAsset)
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-box-seam"></i></span>
                Fulfilled Asset
            </div>
            <div class="form-card-body">
                <dl class="mb-0" style="font-size:.875rem">
                    <dt class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Asset</dt>
                    <dd class="mb-3 fw-semibold">{{ $assetRequest->fulfilledAsset->name }}</dd>
                    <dt class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Asset Tag</dt>
                    <dd class="mb-3"><code class="px-2 py-1 rounded" style="background:#f1f5f9;color:#475569">{{ $assetRequest->fulfilledAsset->asset_tag }}</code></dd>
                    <dt class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Fulfilled At</dt>
                    <dd class="mb-0">{{ $assetRequest->fulfilled_at?->format('d-m-Y H:i') ?? '—' }}</dd>
                </dl>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
