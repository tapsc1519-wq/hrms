@extends('layouts.app')
@section('title', 'User Import Preview')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('admin.users.bulk-import.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Upload CSV</a>
        <h4>User Import Preview</h4>
        <p>Review validation results before creating users.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card-gradient grad-green"><div class="card-body"><div class="stat-number">{{ $validCount }}</div><div class="stat-label">Ready to Import</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-gradient grad-red"><div class="card-body"><div class="stat-number">{{ $invalidCount }}</div><div class="stat-label">Rows With Errors</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-gradient grad-blue"><div class="card-body"><div class="stat-number">{{ count($parsed) }}</div><div class="stat-label">Total Rows</div></div></div>
    </div>
</div>

@if($validCount > 0)
<form action="{{ route('admin.users.bulk-import.confirm') }}" method="POST" class="mb-3">
    @csrf
    <input type="hidden" name="temp_path" value="{{ $tempPath }}">
    <button class="btn btn-primary">
        <i class="bi bi-cloud-upload-fill me-1"></i>Import {{ $validCount }} Valid User{{ $validCount !== 1 ? 's' : '' }}
    </button>
    <a href="{{ route('admin.users.bulk-import.index') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
@endif

<div class="table-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.84rem">
            <thead class="table-light">
                <tr>
                    <th>Row</th>
                    <th>Status</th>
                    <th>Name / Email</th>
                    <th>Role</th>
                    <th>Permission Role</th>
                    <th>Department</th>
                    <th>Errors</th>
                </tr>
            </thead>
            <tbody>
                @foreach($parsed as $item)
                <tr class="{{ $item['valid'] ? '' : 'table-danger' }}">
                    <td><code>{{ $item['row'] }}</code></td>
                    <td>
                        @if($item['valid'])
                            <span class="badge bg-success">Valid</span>
                        @else
                            <span class="badge bg-danger">Error</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $item['data']['name'] ?: '—' }}</strong><br>
                        <small class="text-muted">{{ $item['data']['email'] ?: '—' }}</small>
                    </td>
                    <td>{{ ucfirst($item['data']['role']) }}</td>
                    <td>{{ $item['data']['_permission_role_name'] ?? 'Default access' }}</td>
                    <td>{{ $item['data']['_department_name'] ?? '—' }}</td>
                    <td>
                        @if($item['valid'])
                            <span class="text-success small">Ready</span>
                        @else
                            @foreach($item['errors'] as $error)
                            <div class="small text-danger">{{ $error }}</div>
                            @endforeach
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
