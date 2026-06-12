@extends('layouts.app')

@section('title', 'Maintenance Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Maintenance Report</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf me-1"></i>Export PDF
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card border-0 text-center p-3">
            <h3 class="fw-700 text-primary">{{ $summary['total'] }}</h3>
            <small class="text-muted">Total Records</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 text-center p-3">
            <h3 class="fw-700 text-success">{{ $summary['completed'] }}</h3>
            <small class="text-muted">Completed</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 text-center p-3">
            <h3 class="fw-700 text-warning">{{ $summary['scheduled'] }}</h3>
            <small class="text-muted">Scheduled</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 text-center p-3">
            <h3 class="fw-700 text-danger" style="font-size:1.2rem">&#8377;{{ number_format($summary['total_cost'], 0) }}</h3>
            <small class="text-muted">Total Cost</small>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.8rem">
            <thead class="table-light">
                <tr><th>#</th><th>Asset</th><th>Type</th><th>Scheduled</th><th>Completed</th><th>Technician</th><th>Labor</th><th>Parts</th><th>Total</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($records as $i => $m)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="fw-500">{{ $m->asset->name }}</td>
                    <td><span class="badge bg-info">{{ ucfirst($m->type) }}</span></td>
                    <td>{{ $m->scheduled_date?->format('d-m-Y') ?? '—' }}</td>
                    <td>{{ $m->completed_date?->format('d-m-Y') ?? '—' }}</td>
                    <td>{{ $m->technician_name ?? '—' }}</td>
                    <td>{!! $m->labor_cost > 0 ? '&#8377;' . number_format($m->labor_cost, 2) : '—' !!}</td>
                    <td>{!! $m->parts_cost > 0 ? '&#8377;' . number_format($m->parts_cost, 2) : '—' !!}</td>
                    <td class="fw-500">{!! $m->total_cost > 0 ? '&#8377;' . number_format($m->total_cost, 2) : '—' !!}</td>
                    <td><span class="badge bg-{{ $m->status_badge }}">{{ ucwords(str_replace('_',' ',$m->status)) }}</span></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-600">
                <tr>
                    <td colspan="6">Total</td>
                    <td>&#8377;{{ number_format($records->sum('labor_cost'), 2) }}</td>
                    <td>&#8377;{{ number_format($records->sum('parts_cost'), 2) }}</td>
                    <td>&#8377;{{ number_format($records->sum('total_cost'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
