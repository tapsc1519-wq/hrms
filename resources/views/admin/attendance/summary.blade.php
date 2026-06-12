@extends('layouts.app')

@section('title', 'Monthly Attendance Summary')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h4>Monthly Attendance Summary</h4>
        <p>Review present days, approved leave days, work hours, and payable days by employee.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($attendanceLock)
            <form method="POST" action="{{ route('admin.attendance.unlock') }}" onsubmit="return confirm('Unlock this attendance month?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="month" value="{{ $month }}">
                <button class="btn btn-outline-danger">
                    <i class="bi bi-unlock-fill me-1"></i> Unlock Month
                </button>
            </form>
        @else
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lockMonthModal">
                <i class="bi bi-lock-fill me-1"></i> Lock Month
            </button>
        @endif
        <a href="{{ route('admin.attendance.summary.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> Daily Records
        </a>
    </div>
</div>

@if($attendanceLock)
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start gap-3">
        <i class="bi bi-lock-fill fs-4"></i>
        <div>
            <div class="fw-bold">Attendance locked for {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</div>
            <div class="small">
                Locked by {{ $attendanceLock->locker?->name ?? 'Admin' }} on {{ $attendanceLock->locked_at->format('d-m-Y h:i A') }}.
                @if($attendanceLock->notes)
                    <span class="d-block mt-1">{{ $attendanceLock->notes }}</span>
                @endif
            </div>
        </div>
    </div>
@endif

<div class="table-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}">
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
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $totalPresent = $rows->sum('present_days');
    $totalLeaves = $rows->sum('leave_days');
    $totalHolidays = $rows->sum('holiday_days');
    $totalWeeklyOffs = $rows->sum('weekly_off_days');
    $totalLateMinutes = $rows->sum('late_minutes');
    $totalEarlyLeaveMinutes = $rows->sum('early_leave_minutes');
    $totalOvertimeMinutes = $rows->sum('overtime_minutes');
    $totalMinutes = $rows->sum('work_minutes');
    $totalHours = floor($totalMinutes / 60);
    $remainingMinutes = $totalMinutes % 60;
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-gradient grad-blue p-3">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value">{{ $rows->count() }}</div>
            <div class="stat-label">Employees</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-green p-3">
            <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
            <div class="stat-value">{{ number_format($totalPresent, 0) }}</div>
            <div class="stat-label">Present Days</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-orange p-3">
            <div class="stat-icon"><i class="bi bi-calendar-heart-fill"></i></div>
            <div class="stat-value">{{ number_format($totalLeaves, 1) }}</div>
            <div class="stat-label">Approved Leave Days</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-purple p-3">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value">{{ $totalHours }}h {{ $remainingMinutes }}m</div>
            <div class="stat-label">Total Work Hours</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="form-card mb-0">
            <div class="form-card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Holidays in selected month</div>
                    <div class="fw-bold fs-4">{{ number_format($totalHolidays, 0) }}</div>
                </div>
                <span class="icon-wrap icon-amber"><i class="bi bi-calendar-event-fill"></i></span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-card mb-0">
            <div class="form-card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Weekly offs in selected month</div>
                    <div class="fw-bold fs-4">{{ number_format($totalWeeklyOffs, 0) }}</div>
                </div>
                <span class="icon-wrap icon-purple"><i class="bi bi-calendar-week-fill"></i></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="form-card mb-0">
            <div class="form-card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Late arrival minutes</div>
                    <div class="fw-bold fs-4">{{ number_format($totalLateMinutes, 0) }}</div>
                </div>
                <span class="icon-wrap icon-red"><i class="bi bi-alarm-fill"></i></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-card mb-0">
            <div class="form-card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Early leave minutes</div>
                    <div class="fw-bold fs-4">{{ number_format($totalEarlyLeaveMinutes, 0) }}</div>
                </div>
                <span class="icon-wrap icon-amber"><i class="bi bi-box-arrow-right"></i></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-card mb-0">
            <div class="form-card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Overtime minutes</div>
                    <div class="fw-bold fs-4">{{ number_format($totalOvertimeMinutes, 0) }}</div>
                </div>
                <span class="icon-wrap icon-green"><i class="bi bi-clock-fill"></i></span>
            </div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Employee</th>
                    <th>Department</th>
                    <th class="text-center">Present Days</th>
                    <th class="text-center">Leave Days</th>
                    <th class="text-center">Holidays</th>
                    <th class="text-center">Weekly Offs</th>
                    <th class="text-center">Payable Days</th>
                    <th class="text-center">Late/Early/OT</th>
                    <th class="text-end pe-4">Work Hours</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $employee = $row['employee'];
                        $minutes = (int) $row['work_minutes'];
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $employee->user?->name }}</div>
                            <div class="text-muted small">{{ $employee->employee_code ?? 'No employee code' }}</div>
                        </td>
                        <td>{{ $employee->user?->department?->name ?? 'No department' }}</td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success">{{ $row['present_days'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning-subtle text-warning">{{ number_format($row['leave_days'], 1) }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info-subtle text-info">{{ $row['holiday_days'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary">{{ $row['weekly_off_days'] }}</span>
                        </td>
                        <td class="text-center fw-bold">{{ number_format($row['present_days'] + $row['leave_days'] + $row['holiday_days'] + $row['weekly_off_days'], 1) }}</td>
                        <td class="text-center small text-muted">
                            {{ $row['late_minutes'] }} / {{ $row['early_leave_minutes'] }} / {{ $row['overtime_minutes'] }} min
                        </td>
                        <td class="text-end pe-4">{{ floor($minutes / 60) }}h {{ $minutes % 60 }}m</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-calendar2-x display-6 d-block mb-2"></i>
                            No employee attendance summary found for this month.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="lockMonthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.attendance.lock') }}" class="modal-content border-0" style="border-radius:16px">
            @csrf
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Lock Attendance Month</h5>
                    <div class="text-muted small">Locked months cannot accept employee regularization or regularization approval.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="month" value="{{ $month }}">
                <label class="form-label">Lock Notes</label>
                <textarea name="notes" class="form-control" rows="4" placeholder="Optional payroll or review note"></textarea>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="bi bi-lock-fill me-1"></i> Lock Month</button>
            </div>
        </form>
    </div>
</div>
@endsection
