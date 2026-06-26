@extends('layouts.app')
@section('title', 'Software Compliance')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4>Software Compliance</h4>
        <p>Compare normalized discovery records with license purchases and active allocations.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.software-normalization.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-diagram-3 me-1"></i>Normalize
        </a>
        <a href="{{ route('admin.software-licenses.index') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-key me-1"></i>Manage Licenses
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['High Risk', $stats['high_risk'], 'grad-red', 'Needs urgent review'],
        ['Under Licensed', $stats['under_licensed'], 'grad-orange', 'Short on purchased seats'],
        ['Unauthorized', $stats['unauthorized'], 'grad-purple', 'Installs without valid license'],
        ['Mismatch', $stats['allocation_mismatch'], 'grad-blue', 'Discovered users not allocated'],
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
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Software, vendor or edition">
            </div>
            <div class="col-md-3">
                <label class="form-label">Compliance Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach($statuses as $key => $meta)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            @if(request()->hasAny(['search','status']))
            <div class="col-md-2">
                <a href="{{ route('admin.software-compliance.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg me-1"></i>Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

@if($stats['unknown_discovery'] > 0)
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <strong>{{ $stats['unknown_discovery'] }} discovery records still need normalization.</strong>
        Compliance becomes more accurate after mapping unknown software.
        <a href="{{ route('admin.software-normalization.index') }}" class="alert-link">Open workbench</a>.
    </div>
</div>
@endif

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Software</th>
                    <th>Usage Basis</th>
                    <th>Licenses</th>
                    <th>Status</th>
                    <th>Risk</th>
                    <th>Exposure</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">{{ $row['software']->name }}</div>
                        <div class="text-muted small">
                            {{ $row['software']->vendor ?: 'Unknown vendor' }}
                            @if($row['software']->edition) &middot; {{ $row['software']->edition }} @endif
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $row['software']->license_metric_label }}</div>
                        <div class="text-muted small">
                            {{ $row['installed_count'] }} installs &middot;
                            {{ $row['discovered_users'] }} users &middot;
                            {{ $row['discovered_devices'] }} devices
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $row['required_seats'] }} required / {{ $row['purchased_seats'] }} purchased</div>
                        <div class="text-muted small">
                            {{ $row['allocated_count'] }} allocated
                            @if($row['missing_seats'] > 0) &middot; {{ $row['missing_seats'] }} missing @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-{{ $row['status_meta']['badge'] }}">{{ $row['status_meta']['label'] }}</span>
                        @if($row['allocation_mismatch_count'] > 0)
                            <div class="text-muted small mt-1">{{ $row['allocation_mismatch_count'] }} allocation mismatch</div>
                        @endif
                    </td>
                    <td>
                        @php
                            $riskBadge = $row['risk_level'] === 'high' ? 'danger' : ($row['risk_level'] === 'medium' ? 'warning' : 'success');
                        @endphp
                        <span class="badge bg-{{ $riskBadge }}">{{ ucfirst($row['risk_level']) }}</span>
                        <div class="text-muted small">{{ $row['risk_score'] }}/100 score</div>
                    </td>
                    <td>
                        <div class="fw-bold">Rs {{ number_format($row['financial_exposure'], 2) }}</div>
                        <div class="text-muted small">estimated shortage cost</div>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.software-compliance.show', $row['software']) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Review
                            </a>
                            <a href="{{ route('admin.software-licenses.index', ['search' => $row['software']->name]) }}" class="btn btn-sm btn-outline-secondary">
                                Licenses
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-shield-check fs-1 d-block mb-2 opacity-25"></i>
                        No software compliance records found yet. Import discovery data and add licenses to begin.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())
        <div class="p-3 border-top">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
