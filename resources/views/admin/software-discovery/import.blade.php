@extends('layouts.app')
@section('title', 'Import Software Discovery')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.software-discovery.index') }}" class="back-link">
        <i class="bi bi-arrow-left"></i> Discovery Inventory
    </a>
    <h4>Import Software Discovery</h4>
    <p>Upload endpoint or agent-exported software inventory for normalization and compliance matching.</p>
</div>

<form method="POST" action="{{ route('admin.software-discovery.import.store') }}" enctype="multipart/form-data">
@csrf
<div class="row g-3">
    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-blue"><i class="bi bi-upload"></i></div>
                Discovery CSV
            </div>
            <div class="form-card-body">
                <label class="form-label">CSV File <span class="req">*</span></label>
                <input type="file" name="discovery_file" class="form-control @error('discovery_file') is-invalid @enderror" accept=".csv,.txt" required>
                @error('discovery_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Required column: raw_name. Optional columns can identify device, user, publisher, version and usage.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-amber"><i class="bi bi-info-circle-fill"></i></div>
                CSV Columns
            </div>
            <div class="form-card-body">
                <div class="small text-muted mb-3">Use this format for manual discovery until the device agent starts posting inventory automatically.</div>
                <a href="{{ route('admin.software-discovery.template') }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-download me-1"></i>Download Template
                </a>
                <div class="mt-3 small">
                    <div><strong>Device:</strong> device_asset_tag</div>
                    <div><strong>User:</strong> employee_email</div>
                    <div><strong>Software:</strong> raw_name, raw_publisher, raw_version</div>
                    <div><strong>Usage:</strong> last_used_date, usage_count, total_runtime_minutes</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-actions">
    <a href="{{ route('admin.software-discovery.index') }}" class="btn-cancel">Cancel</a>
    <button class="btn btn-primary btn-save">
        <i class="bi bi-upload me-1"></i>Import Discovery
    </button>
</div>
</form>
@endsection
