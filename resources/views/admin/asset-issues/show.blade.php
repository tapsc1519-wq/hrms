@extends('layouts.app')
@section('title', 'Asset Issue Review')

@section('content')
<style>
    .asset-issue-detail-grid {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .asset-issue-detail-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        min-height: 74px;
        padding: .8rem .9rem;
    }
    .asset-issue-detail-item.wide {
        grid-column: span 2;
    }
    .asset-issue-detail-label {
        color: #64748b;
        font-size: var(--font-xs);
        font-weight: 750;
        letter-spacing: .45px;
        line-height: 1.2;
        margin-bottom: .35rem;
        text-transform: uppercase;
    }
    .asset-issue-detail-value {
        color: #0f172a;
        font-size: var(--font-sm);
        font-weight: 700;
        line-height: 1.3;
        min-width: 0;
        overflow-wrap: anywhere;
    }
    .asset-issue-detail-meta {
        color: #64748b;
        font-size: var(--font-xs);
        line-height: 1.3;
        margin-top: .15rem;
    }
    @media (max-width: 767.98px) {
        .asset-issue-detail-grid {
            grid-template-columns: 1fr;
        }
        .asset-issue-detail-item.wide {
            grid-column: span 1;
        }
    }
</style>

@php
    $asset = $assetIssue->asset;
    $canAct = in_array($assetIssue->status, ['open', 'under_review'], true);
@endphp

<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('admin.asset-issues.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to Asset Issues</a>
        <h4>Asset Issue Review</h4>
        <p>{{ $asset->name }} &middot; {{ $asset->asset_tag }}</p>
    </div>
    <span class="badge bg-{{ $assetIssue->status_badge }}">{{ ucwords(str_replace('_', ' ', $assetIssue->status)) }}</span>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="table-card mb-3">
            <div class="card-header">Employee Report</div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="text-muted small fw-bold text-uppercase">Issue Type</div>
                        <div class="fw-bold">{{ $assetIssue->issue_type_label }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small fw-bold text-uppercase">Severity</div>
                        <span class="badge bg-{{ $assetIssue->severity_badge }}">{{ ucfirst($assetIssue->severity) }}</span>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small fw-bold text-uppercase">Reported Date</div>
                        <div class="fw-bold">{{ $assetIssue->reported_date->format('d-m-Y') }}</div>
                    </div>
                </div>
                <div class="border rounded-3 p-3 small" style="background:#f8fafc">{{ $assetIssue->description }}</div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header">Asset Details</div>
            <div class="card-body">
                <div class="asset-issue-detail-grid">
                    <div class="asset-issue-detail-item wide">
                        <div class="asset-issue-detail-label">Asset</div>
                        <div class="asset-issue-detail-value">{{ $asset->name }}</div>
                        <div class="asset-issue-detail-meta">{{ $asset->category?->name ?? 'Uncategorised' }}</div>
                    </div>
                    <div class="asset-issue-detail-item">
                        <div class="asset-issue-detail-label">Asset Tag</div>
                        <div class="asset-issue-detail-value"><code>{{ $asset->asset_tag ?? '-' }}</code></div>
                    </div>
                    <div class="asset-issue-detail-item">
                        <div class="asset-issue-detail-label">Serial Number</div>
                        <div class="asset-issue-detail-value">{{ $asset->serial_number ?? '-' }}</div>
                    </div>
                    <div class="asset-issue-detail-item">
                        <div class="asset-issue-detail-label">Current Holder</div>
                        <div class="asset-issue-detail-value">{{ $asset->activeAssignment?->user?->name ?? 'Not assigned' }}</div>
                        <div class="asset-issue-detail-meta">{{ ucfirst($asset->status) }} &middot; {{ ucfirst($asset->condition ?? 'unknown') }}</div>
                    </div>
                    <div class="asset-issue-detail-item">
                        <div class="asset-issue-detail-label">Location</div>
                        <div class="asset-issue-detail-value">{{ $asset->location?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="table-card mb-3">
            <div class="card-header">Reporter</div>
            <div class="card-body">
                <div class="fw-bold">{{ $assetIssue->reportedBy->name }}</div>
                <div class="text-muted small">{{ $assetIssue->reportedBy->email }}</div>
                <div class="text-muted small">{{ $assetIssue->reportedBy->department?->name ?? 'No department' }}</div>
            </div>
        </div>

        @if($assetIssue->disposal)
        <div class="alert alert-info rounded-3">
            <div class="fw-bold mb-1"><i class="bi bi-send me-1"></i>Converted to disposal</div>
            <a href="{{ route('admin.disposals.show', $assetIssue->disposal) }}" class="alert-link">Open disposal request</a>
        </div>
        @endif

        @if($canAct)
        <div class="table-card mb-3">
            <div class="card-header">Review Action</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.asset-issues.review', $assetIssue) }}">
                    @csrf @method('PATCH')
                    <label class="form-label">Action</label>
                    <select name="status" class="form-select mb-3" required>
                        <option value="under_review" @selected($assetIssue->status === 'under_review')>Mark Under Review</option>
                        <option value="resolved">Resolved without Disposal</option>
                        <option value="rejected">Reject Report</option>
                    </select>
                    <label class="form-label">Review Notes</label>
                    <textarea name="review_notes" class="form-control mb-3" rows="3" placeholder="Optional for review/resolved, required when rejected.">{{ old('review_notes') }}</textarea>
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-check2-circle me-1"></i>Update Report
                    </button>
                </form>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header">Create Disposal Request</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.asset-issues.disposal', $assetIssue) }}">
                    @csrf
                    <label class="form-label">Disposal Method</label>
                    <select name="method" class="form-select mb-3" required>
                        @foreach(['scrap', 'sell', 'donate', 'recycle', 'return_to_supplier', 'destroy', 'lost', 'stolen'] as $method)
                            <option value="{{ $method }}" @selected(($assetIssue->issue_type === $method) || ($assetIssue->issue_type === 'damaged' && $method === 'scrap'))>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">Requested Date</label>
                    <input type="date" name="requested_date" class="form-control mb-3" value="{{ today()->format('Y-m-d') }}" required>
                    <label class="form-label">Expected Value</label>
                    <input type="number" step="0.01" min="0" name="expected_value" class="form-control mb-3" placeholder="0.00">
                    <label class="form-label">Recipient / Vendor</label>
                    <input type="text" name="recipient_name" class="form-control mb-3" placeholder="Optional">
                    <label class="form-label">Disposal Reason</label>
                    <textarea name="reason" class="form-control mb-3" rows="4" required>{{ old('reason', 'Created from employee issue report: '.$assetIssue->issue_type_label) }}</textarea>
                    <button class="btn btn-danger w-100">
                        <i class="bi bi-send me-1"></i>Create Disposal Request
                    </button>
                </form>
            </div>
        </div>
        @else
        <div class="table-card">
            <div class="card-header">Review Notes</div>
            <div class="card-body">
                <div class="text-muted small">{{ $assetIssue->review_notes ?: 'No review notes added.' }}</div>
                @if($assetIssue->reviewedBy)
                    <div class="mt-3 small">Reviewed by <strong>{{ $assetIssue->reviewedBy->name }}</strong> on {{ $assetIssue->reviewed_at?->format('d-m-Y H:i') }}</div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
