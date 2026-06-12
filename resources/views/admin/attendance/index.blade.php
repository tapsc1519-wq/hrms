@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
<div class="page-header">
    <h4>Attendance</h4>
    <p>Review employee sign-in and sign-out records.</p>
</div>

<div class="table-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Employee name or email">
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string)request('department_id') === (string)$department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Employee</th>
                    <th>Date</th>
                    <th>Day Type</th>
                    <th>Sign In</th>
                    <th>Sign Out</th>
                    <th>Duration</th>
                    <th>Shift Metrics</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $record->user?->name }}</div>
                            <div class="text-muted small">{{ $record->user?->department?->name ?? 'No department' }}</div>
                        </td>
                        <td>{{ $record->attendance_date->format('d-m-Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $record->day_type === 'holiday' ? 'info' : ($record->day_type === 'weekly_off' ? 'secondary' : 'light text-dark border') }}">
                                {{ $record->holiday?->name ?? $record->day_type_label }}
                            </span>
                        </td>
                        <td>{{ $record->sign_in_at?->format('h:i A') ?? '—' }}</td>
                        <td>{{ $record->sign_out_at?->format('h:i A') ?? '—' }}</td>
                        <td>{{ $record->work_duration }}</td>
                        <td class="small">
                            <div>{{ $record->shift?->name ?? $record->employee?->shift?->name ?? 'No shift' }}</div>
                            @if($record->late_minutes || $record->early_leave_minutes || $record->overtime_minutes)
                                <div class="text-muted">
                                    Late {{ $record->late_minutes }}m · Early {{ $record->early_leave_minutes }}m · OT {{ $record->overtime_minutes }}m
                                </div>
                            @else
                                <div class="text-muted">On schedule</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $record->status === 'present' ? 'success' : ($record->status === 'half_day' ? 'warning' : 'danger') }}">
                                {{ ucwords(str_replace('_', ' ', $record->status)) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No attendance records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="p-3 border-top">{{ $records->links() }}</div>
    @endif
</div>
@endsection
