@extends('layouts.app')
@section('title', 'Bulk Assign CSV')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="page-title mb-0">Bulk Assign CSV</h4>
        <p class="page-subtitle mb-0">Upload assignments for multiple employees in one CSV file.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.assignments.bulk.template') }}" class="btn btn-outline-primary">
            <i class="bi bi-download me-1"></i>Template
        </a>
        <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Assignments
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="table-card">
            <div class="card-header">
                <div>Upload CSV</div>
                <div class="small text-muted fw-normal mt-1">Each row assigns one available asset to one active employee.</div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.assignments.bulk.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-600">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                        <div class="form-text">Use comma-separated values with the header row shown on this page.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i>Upload and Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="table-card mb-3">
            <div class="card-header">Required Columns</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <tr><td class="fw-700">asset_tag</td><td class="text-muted">Asset tag only. Required.</td></tr>
                            <tr><td class="fw-700">employee_code</td><td class="text-muted">Active employee code. Required.</td></tr>
                            <tr><td class="fw-700">assigned_date</td><td class="text-muted">YYYY-MM-DD. Required.</td></tr>
                            <tr><td class="fw-700">expected_return_date</td><td class="text-muted">YYYY-MM-DD. Optional.</td></tr>
                            <tr><td class="fw-700">condition_out</td><td class="text-muted">excellent, good, fair, or poor. Optional.</td></tr>
                            <tr><td class="fw-700">purpose</td><td class="text-muted">Optional.</td></tr>
                            <tr><td class="fw-700">notes</td><td class="text-muted">Optional.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-info rounded-3 mb-0">
            <div class="fw-700 mb-1"><i class="bi bi-info-circle me-1"></i>Import rules</div>
            <div class="small">Only available assets are assigned. The employee must belong to your organization and be active. If any row has an error, no assignments are created.</div>
        </div>
    </div>
</div>
@endsection