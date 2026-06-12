@extends('layouts.app')

@section('title', 'Asset Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Asset Report</h4>
        <p class="page-subtitle mb-0">{{ $assets->count() }} assets</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf me-1"></i>Export PDF
        </a>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-excel me-1"></i>Export Excel
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card border-0 text-center p-3">
            <h3 class="fw-700 text-primary mb-0">{{ $summary['total'] }}</h3>
            <small class="text-muted">Total Assets</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 text-center p-3">
            <h3 class="fw-700 text-success mb-0">{{ $summary['available'] }}</h3>
            <small class="text-muted">Available</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 text-center p-3">
            <h3 class="fw-700 text-info mb-0">{{ $summary['assigned'] }}</h3>
            <small class="text-muted">Assigned</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 text-center p-3">
            <h3 class="fw-700 mb-0">&#8377;{{ number_format($summary['total_value'], 0) }}</h3>
            <small class="text-muted">Total Value</small>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach(['available','assigned','maintenance','repair','retired','disposed','lost'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('admin.reports.assets') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.8rem">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Asset Tag</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Assigned To</th>
                    <th>Purchase Price</th>
                    <th>Current Value</th>
                    <th>Status</th>
                    <th>Warranty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assets as $i => $asset)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><code>{{ $asset->asset_tag }}</code></td>
                    <td class="fw-500">{{ $asset->name }}</td>
                    <td>{{ $asset->category?->name ?? '—' }}</td>
                    <td>{{ $asset->supplier?->name ?? '—' }}</td>
                    <td>{{ $asset->activeAssignment?->user?->name ?? '—' }}</td>
                    <td>{!! $asset->purchase_price ? '&#8377;' . number_format($asset->purchase_price, 2) : '—' !!}</td>
                    <td>{!! $asset->depreciationRecord ? '&#8377;' . number_format($asset->current_value, 2) : '—' !!}</td>
                    <td><span class="badge bg-{{ $asset->status_badge }}">{{ ucfirst($asset->status) }}</span></td>
                    <td>
                        @if($asset->warranty_expiry_date)
                            <span class="{{ $asset->warranty_expiry_date->isPast() ? 'text-danger' : 'text-success' }}">
                                {{ $asset->warranty_expiry_date->format('d-m-Y') }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-600">
                <tr>
                    <td colspan="6">Total</td>
                    <td>&#8377;{{ number_format($assets->sum('purchase_price'), 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
