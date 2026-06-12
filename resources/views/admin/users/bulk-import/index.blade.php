@extends('layouts.app')
@section('title', 'Bulk Import Users')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('admin.users.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Users</a>
        <h4>Bulk Import Users</h4>
        <p>Upload a CSV file to onboard employees quickly for a new organization.</p>
    </div>
    <a href="{{ route('admin.users.bulk-import.template') }}" class="btn btn-success">
        <i class="bi bi-download me-1"></i>Download Template
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-cloud-upload"></i></span>
                Upload CSV
            </div>
            <div class="form-card-body">
                <form action="{{ route('admin.users.bulk-import.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">CSV File <span class="req">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">Use the downloaded template. Maximum file size: 5 MB.</div>
                    </div>
                    <button class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Preview & Validate
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-info-circle"></i></span>
                CSV Columns
            </div>
            <div class="form-card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach($headers as $header)
                    <code class="px-2 py-1 rounded" style="background:#f1f5f9;color:#334155">{{ $header }}</code>
                    @endforeach
                </div>
                <ul class="small text-muted mb-0" style="line-height:1.9">
                    <li><strong>name</strong> and <strong>email</strong> are mandatory.</li>
                    <li><strong>role</strong>: admin, staff, or supplier.</li>
                    <li><strong>permission_role</strong> must match a role in Roles & Permissions.</li>
                    <li><strong>department</strong> must match an existing department name.</li>
                    <li>Blank password uses default: <strong>{{ $defaultPassword }}</strong>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
