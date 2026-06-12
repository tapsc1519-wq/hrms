@extends('layouts.app')

@section('title', 'Maintenance Record')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Maintenance Record</h4>
        <p class="page-subtitle mb-0"><a href="{{ route('admin.maintenance.index') }}" class="text-decoration-none">Maintenance</a> / View</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.maintenance.edit', $maintenanceRecord) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="table-card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span class="fw-600">Maintenance Details</span>
                <span class="badge bg-{{ $maintenanceRecord->status_badge }} fs-6">{{ ucwords(str_replace('_', ' ', $maintenanceRecord->status)) }}</span>
            </div>
            <div class="card-body">
                <dl class="row" style="font-size:.875rem">
                    <dt class="col-4 text-muted">Asset</dt>
                    <dd class="col-8"><a href="{{ route('admin.assets.show', $maintenanceRecord->asset) }}" class="text-decoration-none fw-500">{{ $maintenanceRecord->asset->name }}</a></dd>
                    <dt class="col-4 text-muted">Type</dt>
                    <dd class="col-8"><span class="badge bg-info">{{ ucfirst($maintenanceRecord->type) }}</span></dd>
                    <dt class="col-4 text-muted">Scheduled Date</dt>
                    <dd class="col-8">{{ $maintenanceRecord->scheduled_date?->format('d-m-Y') ?? '—' }}</dd>
                    <dt class="col-4 text-muted">Completed Date</dt>
                    <dd class="col-8">{{ $maintenanceRecord->completed_date?->format('d-m-Y') ?? '—' }}</dd>
                    <dt class="col-4 text-muted">Next Maintenance</dt>
                    <dd class="col-8">{{ $maintenanceRecord->next_maintenance_date?->format('d-m-Y') ?? '—' }}</dd>
                    <dt class="col-4 text-muted">Service Provider</dt>
                    <dd class="col-8">{{ $maintenanceRecord->supplier?->name ?? 'In-house' }}</dd>
                    <dt class="col-4 text-muted">Technician</dt>
                    <dd class="col-8">{{ $maintenanceRecord->technician_name ?? '—' }}</dd>
                    <dt class="col-4 text-muted">Description</dt>
                    <dd class="col-8">{{ $maintenanceRecord->description }}</dd>
                    @if($maintenanceRecord->diagnosis)
                    <dt class="col-4 text-muted">Diagnosis</dt>
                    <dd class="col-8">{{ $maintenanceRecord->diagnosis }}</dd>
                    @endif
                    @if($maintenanceRecord->work_performed)
                    <dt class="col-4 text-muted">Work Performed</dt>
                    <dd class="col-8">{{ $maintenanceRecord->work_performed }}</dd>
                    @endif
                    @if($maintenanceRecord->parts_replaced)
                    <dt class="col-4 text-muted">Parts Replaced</dt>
                    <dd class="col-8">{{ $maintenanceRecord->parts_replaced }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="table-card">
            <div class="card-header"><span class="fw-600">Cost Summary</span></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Labor Cost</span>
                    <strong>&#8377;{{ number_format($maintenanceRecord->labor_cost, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Parts Cost</span>
                    <strong>&#8377;{{ number_format($maintenanceRecord->parts_cost, 2) }}</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="fw-600">Total Cost</span>
                    <strong class="text-danger fs-5">&#8377;{{ number_format($maintenanceRecord->total_cost, 2) }}</strong>
                </div>
                @if($maintenanceRecord->invoice_number)
                <hr>
                <div class="text-muted small">Invoice: {{ $maintenanceRecord->invoice_number }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
