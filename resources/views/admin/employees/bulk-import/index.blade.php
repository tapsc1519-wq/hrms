@extends('layouts.app')

@section('title', 'Bulk Import Employees')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.employees.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Employees</a>
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h4>Bulk Import Employees</h4>
            <p>Upload employees by CSV. Each valid row creates an Employee Profile and a Staff/Admin login user.</p>
        </div>
        <a href="{{ route('admin.employees.bulk-import.template') }}" class="btn btn-outline-primary">
            <i class="bi bi-download me-1"></i> Download Template
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-cloud-upload"></i></span>
                Upload CSV
            </div>
            <form method="POST" action="{{ route('admin.employees.bulk-import.preview') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-card-body">
                    <label class="form-label">Employee CSV <span class="req">*</span></label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required>
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">CSV only. Max 5 MB. Dates can be DD-MM-YYYY or YYYY-MM-DD.</div>
                </div>
                <div class="form-actions">
                    <a href="{{ route('admin.employees.index') }}" class="btn-cancel">Cancel</a>
                    <button class="btn btn-primary btn-save"><i class="bi bi-eye me-1"></i> Preview Import</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-table"></i></span>
                CSV Columns
            </div>
            <div class="form-card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($headers as $header)
                        <span class="badge bg-light text-dark border">{{ $header }}</span>
                    @endforeach
                </div>
                <hr>
                <div class="small text-muted">
                    Empty passwords use <strong>{{ $defaultPassword }}</strong>. Portal role must be <strong>staff</strong> or <strong>admin</strong>.
                    Department, facility, work location, reporting manager, and permission role must already exist if provided.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
