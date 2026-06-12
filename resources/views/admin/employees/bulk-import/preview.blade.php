@extends('layouts.app')

@section('title', 'Preview Employee Import')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.employees.bulk-import.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Bulk Import</a>
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h4>Preview Employee Import</h4>
            <p>{{ $validCount }} valid row{{ $validCount !== 1 ? 's' : '' }}, {{ $invalidCount }} row{{ $invalidCount !== 1 ? 's' : '' }} need attention.</p>
        </div>
        @if($validCount)
            <form method="POST" action="{{ route('admin.employees.bulk-import.confirm') }}">
                @csrf
                <input type="hidden" name="temp_path" value="{{ $tempPath }}">
                <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Import Valid Rows</button>
            </form>
        @endif
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.85rem">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Row</th>
                    <th>Employee</th>
                    <th>Portal</th>
                    <th>Department</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Validation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($parsed as $item)
                    <tr>
                        <td class="ps-4">{{ $item['row'] }}</td>
                        <td>
                            <div class="fw-bold">{{ $item['data']['name'] ?: '—' }}</div>
                            <div class="text-muted small">{{ $item['data']['email'] ?: 'No email' }}</div>
                            <span class="badge bg-light text-dark border mt-1">{{ $item['data']['employee_code'] ?: 'No code' }}</span>
                        </td>
                        <td>{{ ucfirst($item['data']['portal_role']) }}</td>
                        <td>{{ $item['data']['_department_name'] ?? '—' }}</td>
                        <td>
                            <div>{{ $item['data']['_facility_name'] ?? '—' }}</div>
                            <div class="text-muted small">{{ $item['data']['_location_name'] ?? '' }}</div>
                        </td>
                        <td>{{ ucwords(str_replace('_', ' ', $item['data']['employment_status'])) }}</td>
                        <td>
                            @if($item['valid'])
                                <span class="badge bg-success">Ready</span>
                            @else
                                <div class="text-danger small">
                                    @foreach($item['errors'] as $error)
                                        <div><i class="bi bi-exclamation-circle me-1"></i>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
