@extends('layouts.app')

@section('title', 'Asset Repairs')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Asset Repairs</h4>
        <p>Track employee repair requests, vendor work, market repairs, cost and quality checks.</p>
    </div>
    <a href="{{ route('admin.asset-repairs.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Create Repair Job
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card-gradient grad-blue"><div class="card-body"><div class="stat-label">Open Jobs</div><div class="stat-number">{{ $stats['open'] }}</div><div class="stat-sub">In repair workflow</div></div></div></div>
    <div class="col-md-3"><div class="stat-card-gradient grad-orange"><div class="card-body"><div class="stat-label">QC Pending</div><div class="stat-number">{{ $stats['qc_pending'] }}</div><div class="stat-sub">Waiting for inspection</div></div></div></div>
    <div class="col-md-3"><div class="stat-card-gradient grad-teal"><div class="card-body"><div class="stat-label">Ready</div><div class="stat-number">{{ $stats['ready_to_return'] }}</div><div class="stat-sub">Ready to return</div></div></div></div>
    <div class="col-md-3"><div class="stat-card-gradient grad-green"><div class="card-body"><div class="stat-label">Closed</div><div class="stat-number">{{ $stats['closed'] }}</div><div class="stat-sub">Completed jobs</div></div></div></div>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search repair no, asset, employee..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Open statuses</option>
                    @foreach(['request_raised','under_review','approved','assigned_to_vendor','sent_for_repair','repair_in_progress','qc_pending','ready_to_return','closed','rejected','not_repairable'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="repair_type" class="form-select form-select-sm">
                    <option value="">All repair types</option>
                    @foreach(['internal','amc','vendor','market','warranty'] as $type)
                        <option value="{{ $type }}" @selected(request('repair_type') === $type)>{{ ucwords($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.asset-repairs.index') }}" class="btn btn-light btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr>
                    <th>Repair</th>
                    <th>Asset</th>
                    <th>Requested By</th>
                    <th>Type</th>
                    <th>Vendor</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($repairs as $repair)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $repair->repair_number }}</div>
                            <small class="text-muted">{{ $repair->requested_date?->format('d-m-Y') }} · {{ ucfirst($repair->priority) }}</small>
                        </td>
                        <td>
                            <a href="{{ route('admin.assets.show', $repair->asset) }}" class="fw-bold text-decoration-none">{{ $repair->asset->name }}</a>
                            <div class="text-muted small">{{ $repair->asset->asset_tag }}</div>
                        </td>
                        <td>{{ $repair->requestedBy?->name ?? 'Admin/IT' }}</td>
                        <td>{{ $repair->repair_type_label }}</td>
                        <td>{{ $repair->vendor?->name ?? $repair->market_vendor_name ?? 'Internal' }}</td>
                        <td>{!! $repair->total_cost > 0 ? '&#8377;' . number_format((float) $repair->total_cost, 2) : '<span class="text-muted">Pending</span>' !!}</td>
                        <td><span class="badge bg-{{ $repair->status_badge }}">{{ $repair->status_label }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.asset-repairs.show', $repair) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-wrench-adjustable-circle fs-1 d-block mb-2 opacity-50"></i>
                            No repair jobs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($repairs->hasPages())
        <div class="card-footer bg-white border-top">{{ $repairs->links() }}</div>
    @endif
</div>
@endsection
