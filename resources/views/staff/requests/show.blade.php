@extends('layouts.app')
@section('title', 'Request — ' . $assetRequest->request_number)

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('staff.requests.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> My Requests</a>
        <h4>{{ $assetRequest->request_number }}</h4>
        <p>Submitted on {{ $assetRequest->request_date->format('d-m-Y') }}</p>
    </div>
    <span class="badge fs-6 mt-1 bg-{{ $assetRequest->status_badge }}">
        {{ ucfirst($assetRequest->status) }}
    </span>
</div>

<div class="row g-4">

    {{-- LEFT: Details --}}
    <div class="col-lg-7">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-clipboard-check"></i></span>
                Request Details
            </div>
            <div class="form-card-body">
                <dl class="row mb-0" style="font-size:.875rem;row-gap:.6rem">
                    <dt class="col-4" style="color:#64748b;font-weight:600">Asset Category</dt>
                    <dd class="col-8 mb-0 fw-semibold">
                        {{ $assetRequest->category?->name ?? 'Any available' }}
                    </dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Request Type</dt>
                    <dd class="col-8 mb-0">{{ $assetRequest->request_type_label }}</dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Quantity</dt>
                    <dd class="col-8 mb-0">{{ $assetRequest->quantity ?? 1 }}</dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Priority</dt>
                    <dd class="col-8 mb-0">
                        <span class="badge bg-{{ $assetRequest->priority_badge }}">{{ ucfirst($assetRequest->priority) }}</span>
                    </dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Request Date</dt>
                    <dd class="col-8 mb-0">{{ $assetRequest->request_date->format('d-m-Y') }}</dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Required By</dt>
                    <dd class="col-8 mb-0">{{ $assetRequest->required_date?->format('d-m-Y') ?? '— Flexible —' }}</dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Status</dt>
                    <dd class="col-8 mb-0">
                        <span class="badge bg-{{ $assetRequest->status_badge }}">{{ ucfirst($assetRequest->status) }}</span>
                    </dd>

                    <dt class="col-4" style="color:#64748b;font-weight:600">Reason</dt>
                    <dd class="col-8 mb-0" style="white-space:pre-line">{{ $assetRequest->reason }}</dd>
                </dl>
            </div>
        </div>

        {{-- Cancel action --}}
        @if($assetRequest->status === 'pending')
        <div class="form-card" style="border-color:#fecaca">
            <div class="form-card-header" style="background:linear-gradient(135deg,#fef2f2,#fee2e2)">
                <span class="icon-wrap" style="background:#fee2e2;color:#991b1b"><i class="bi bi-x-circle"></i></span>
                <span style="color:#991b1b">Cancel Request</span>
            </div>
            <div class="form-card-body">
                <p style="font-size:.85rem;color:#64748b;margin-bottom:1rem">
                    You can cancel this request while it is still pending review.
                </p>
                <form action="{{ route('staff.requests.cancel', $assetRequest) }}" method="POST"
                      onsubmit="return confirm('Are you sure you want to cancel this request?')">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Cancel Request
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>

    {{-- RIGHT: Status & Review info --}}
    <div class="col-lg-5">

        {{-- Status Timeline --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-clock-history"></i></span>
                Status Timeline
            </div>
            <div class="form-card-body">
                <div style="font-size:.83rem">

                    {{-- Submitted --}}
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0" style="width:28px;height:28px;border-radius:50%;background:#dbeafe;color:#1d4ed8;display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-send" style="font-size:.75rem"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;color:#334155">Submitted</div>
                            <div class="text-muted">{{ $assetRequest->request_date->format('d-m-Y') }}</div>
                        </div>
                    </div>

                    {{-- Reviewed --}}
                    @if($assetRequest->reviewedBy)
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0" style="width:28px;height:28px;border-radius:50%;background:{{ $assetRequest->status === 'approved' ? '#dcfce7' : '#fee2e2' }};color:{{ $assetRequest->status === 'approved' ? '#166534' : '#991b1b' }};display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-{{ $assetRequest->status === 'approved' ? 'check' : 'x' }}" style="font-size:.75rem"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;color:#334155">
                                {{ ucfirst($assetRequest->status) }} by {{ $assetRequest->reviewedBy->name }}
                            </div>
                            <div class="text-muted">{{ $assetRequest->reviewed_at?->format('d-m-Y H:i') }}</div>
                            @if($assetRequest->review_notes)
                            <div class="mt-1 p-2 rounded-2" style="background:#f8fafc;border:1px solid #e2e8f0;color:#475569">
                                {{ $assetRequest->review_notes }}
                            </div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0" style="width:28px;height:28px;border-radius:50%;background:#f1f5f9;color:#94a3b8;display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-hourglass-split" style="font-size:.75rem"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;color:#94a3b8">Awaiting Review</div>
                            <div class="text-muted">Pending admin decision</div>
                        </div>
                    </div>
                    @endif

                    {{-- Fulfilled --}}
                    @if($assetRequest->fulfilledAsset)
                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0" style="width:28px;height:28px;border-radius:50%;background:#dcfce7;color:#166534;display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-box-seam" style="font-size:.75rem"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;color:#334155">Fulfilled</div>
                            <div class="text-muted">{{ $assetRequest->fulfilledAsset->name }} assigned</div>
                            <div class="text-muted">{{ $assetRequest->fulfilled_at?->format('d-m-Y H:i') }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Fulfilled Asset Info --}}
        @if($assetRequest->fulfilledAsset)
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-box-seam"></i></span>
                Assigned Asset
            </div>
            <div class="form-card-body">
                <dl class="mb-0" style="font-size:.875rem;row-gap:.5rem" class="row">
                    <dt class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Asset Name</dt>
                    <dd class="fw-semibold mb-2">{{ $assetRequest->fulfilledAsset->name }}</dd>
                    @if($assetRequest->fulfilledAsset->asset_tag)
                    <dt class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Asset Tag</dt>
                    <dd class="mb-2"><code class="px-2 py-1 rounded" style="background:#f1f5f9;color:#475569">{{ $assetRequest->fulfilledAsset->asset_tag }}</code></dd>
                    @endif
                    <dt class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Fulfilled</dt>
                    <dd class="mb-0">{{ $assetRequest->fulfilled_at?->format('d-m-Y') }}</dd>
                </dl>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
