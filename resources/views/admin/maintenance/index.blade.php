@extends('layouts.app')

@section('title', 'Maintenance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Maintenance Records</h4>
        <p class="page-subtitle mb-0">{{ $records->total() }} records</p>
    </div>
    <a href="{{ route('admin.maintenance.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Log Maintenance
    </a>
</div>

<!-- Filters -->
<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search asset..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach(['preventive','corrective','predictive','emergency'] as $t)
                        <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach(['scheduled','in_progress','completed','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr>
                    <th>Asset</th>
                    <th>Type</th>
                    <th>Scheduled</th>
                    <th>Completed</th>
                    <th>Technician</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $m)
                <tr>
                    <td>
                        <a href="{{ route('admin.assets.show', $m->asset) }}" class="text-decoration-none fw-500">
                            {{ $m->asset->name }}
                        </a>
                        <br><small class="text-muted">{{ $m->asset->asset_tag }}</small>
                    </td>
                    <td><span class="badge bg-info">{{ ucfirst($m->type) }}</span></td>
                    <td>{{ $m->scheduled_date?->format('d-m-Y') ?? '—' }}</td>
                    <td>{{ $m->completed_date?->format('d-m-Y') ?? '—' }}</td>
                    <td>{{ $m->technician_name ?? '—' }}</td>
                    <td>{!! $m->total_cost > 0 ? '&#8377;' . number_format($m->total_cost, 2) : '—' !!}</td>
                    <td><span class="badge bg-{{ $m->status_badge }}">{{ ucwords(str_replace('_', ' ', $m->status)) }}</span></td>
                    <td>
                        <a href="{{ route('admin.maintenance.show', $m) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('admin.maintenance.edit', $m) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-tools fs-1 d-block mb-2"></i>
                        No maintenance records. <a href="{{ route('admin.maintenance.create') }}">Log one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
    <div class="card-footer bg-white border-top-0">{{ $records->links() }}</div>
    @endif
</div>
@endsection
