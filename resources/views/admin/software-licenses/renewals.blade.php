@extends('layouts.app')
@section('title', 'License Renewals')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.software-licenses.index') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Licenses
        </a>
        <h4>License Renewals</h4>
        <p>Review upcoming renewals, expired licenses, unused seats, and reduction opportunities.</p>
    </div>
    <a href="{{ route('admin.software-licenses.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add License
    </a>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Expired', $summary['expired'], 'grad-red', 'Needs immediate action'],
        ['Expiring Soon', $summary['expiring_30'], 'grad-orange', 'Within 30 days'],
        ['Unused', $summary['unused'], 'grad-purple', 'No active allocation'],
        ['Reduce', $summary['reduce'], 'grad-blue', 'Possible cost saving'],
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
            <div class="col-md-3">
                <label class="form-label">Renewal Window</label>
                <select name="window" class="form-select">
                    <option value="">All active licenses</option>
                    <option value="30" @selected(request('window') === '30')>Next 30 days</option>
                    <option value="60" @selected(request('window') === '60')>Next 60 days</option>
                    <option value="90" @selected(request('window') === '90')>Next 90 days</option>
                    <option value="180" @selected(request('window') === '180')>Next 180 days</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Recommendation</label>
                <select name="recommendation" class="form-select">
                    <option value="">All recommendations</option>
                    <option value="renew" @selected(request('recommendation') === 'renew')>Renew</option>
                    <option value="reduce" @selected(request('recommendation') === 'reduce')>Reduce</option>
                    <option value="cancel_review" @selected(request('recommendation') === 'cancel_review')>Cancel Review</option>
                    <option value="manual_review" @selected(request('recommendation') === 'manual_review')>Manual Review</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            @if(request()->hasAny(['window','recommendation']))
            <div class="col-md-2">
                <a href="{{ route('admin.software-licenses.renewals') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-lg me-1"></i>Clear
                </a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Software</th>
                    <th>Renewal / Expiry</th>
                    <th>Usage</th>
                    <th>Cost</th>
                    <th>Recommendation</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($licenses as $license)
                @php
                    $renewalDate = $license->renewal_date ?? $license->expiry_date;
                    $utilization = $license->seats > 0 ? min(100, round($license->used_seats / $license->seats * 100)) : 0;
                    $recommendationBadge = match($license->renewal_recommendation) {
                        'renew' => 'success',
                        'reduce' => 'info',
                        'cancel_review' => 'warning',
                        default => 'secondary',
                    };
                @endphp
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">{{ $license->software?->name ?? 'Unknown software' }}</div>
                        <div class="text-muted small">
                            {{ $license->software?->vendor ?: 'Unknown vendor' }}
                            @if($license->purchase_batch) &middot; {{ $license->purchase_batch }} @endif
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold {{ $license->is_expired ? 'text-danger' : ($license->is_expiring_soon ? 'text-warning' : '') }}">
                            {{ $renewalDate?->format('d-m-Y') ?? 'No renewal date' }}
                        </div>
                        <div class="text-muted small">
                            {{ $license->is_expired ? 'Expired' : ($license->is_expiring_soon ? 'Expiring soon' : 'Scheduled') }}
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $license->used_seats }} / {{ $license->seats }} seats</div>
                        <div class="progress mt-1" style="height:5px;width:90px;border-radius:99px">
                            <div class="progress-bar {{ $utilization >= 90 ? 'bg-success' : ($utilization >= 30 ? 'bg-warning' : 'bg-danger') }}" style="width:{{ $utilization }}%"></div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold">Rs {{ number_format($license->total_cost, 2) }}</div>
                        <div class="text-muted small">{{ $license->unit_cost ? 'Rs '.number_format((float) $license->unit_cost, 2).' / seat' : 'Total cost' }}</div>
                    </td>
                    <td><span class="badge bg-{{ $recommendationBadge }}">{{ $license->renewal_recommendation_label }}</span></td>
                    <td><span class="badge bg-{{ $license->status_badge }}">{{ $license->status_label }}</span></td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.software-licenses.show', $license) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Review
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-calendar2-check fs-1 d-block mb-2 opacity-25"></i>
                        No renewal records found for the selected filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($licenses->hasPages())
        <div class="p-3 border-top">{{ $licenses->links() }}</div>
    @endif
</div>
@endsection
