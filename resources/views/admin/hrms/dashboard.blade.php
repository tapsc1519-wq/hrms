@extends('layouts.app')

@section('title', 'HRMS Dashboard')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h4>HRMS Dashboard</h4>
        <p>Employee, attendance, leave, and document activity at a glance.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Add Employee
        </a>
        <a href="{{ route('admin.attendance.summary') }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar2-week me-1"></i> Monthly Summary
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-gradient grad-blue p-3">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value">{{ $stats['employees'] }}</div>
            <div class="stat-label">Total Employees</div>
            <div class="small opacity-75">{{ $stats['active_employees'] }} active employees</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-green p-3">
            <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
            <div class="stat-value">{{ $stats['today_present'] }}</div>
            <div class="stat-label">Present Today</div>
            <div class="small opacity-75">{{ $stats['currently_signed_in'] }} currently signed in</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-orange p-3">
            <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
            <div class="stat-value">{{ $stats['pending_leaves'] }}</div>
            <div class="stat-label">Pending Leaves</div>
            <div class="small opacity-75">{{ $stats['low_leave_balances'] }} low balance records</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-purple p-3">
            <div class="stat-icon"><i class="bi bi-file-earmark-arrow-up-fill"></i></div>
            <div class="stat-value">{{ $stats['pending_documents'] }}</div>
            <div class="stat-label">Pending Documents</div>
            <div class="small opacity-75">Awaiting employee upload</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="form-card h-100">
            <div class="form-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Attendance Trend</h5>
                    <p class="text-muted mb-0 small">Present employee count over the last 7 days.</p>
                </div>
                <span class="icon-wrap icon-blue"><i class="bi bi-activity"></i></span>
            </div>
            <div class="form-card-body">
                <div class="d-flex align-items-end gap-3" style="height:220px">
                    @php
                        $maxAttendance = max(1, $attendanceTrend->max('count'));
                    @endphp
                    @foreach($attendanceTrend as $point)
                        @php $height = max(12, round(($point['count'] / $maxAttendance) * 170)); @endphp
                        <div class="flex-fill text-center">
                            <div class="d-flex align-items-end justify-content-center mb-2" style="height:175px">
                                <div class="rounded-top-3 w-75" style="height:{{ $height }}px;background:linear-gradient(180deg,#3b82f6,#1d4ed8);box-shadow:0 8px 18px rgba(59,130,246,.25)"></div>
                            </div>
                            <div class="fw-bold">{{ $point['count'] }}</div>
                            <div class="text-muted small">{{ $point['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-card h-100">
            <div class="form-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Department Headcount</h5>
                    <p class="text-muted mb-0 small">Employees grouped by department.</p>
                </div>
                <span class="icon-wrap icon-green"><i class="bi bi-diagram-3-fill"></i></span>
            </div>
            <div class="form-card-body">
                @forelse($departmentHeadcount as $department)
                    @php
                        $percent = $stats['employees'] > 0 ? round(($department['count'] / $stats['employees']) * 100) : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold">{{ $department['name'] }}</span>
                            <span class="text-muted small">{{ $department['count'] }}</span>
                        </div>
                        <div class="progress" style="height:8px">
                            <div class="progress-bar" style="width:{{ $percent }}%;background:linear-gradient(90deg,#10b981,#059669)"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No employee departments found.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="table-card h-100">
            <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Pending Leaves</h5>
                    <div class="text-muted small">Requests waiting for review</div>
                </div>
                <a href="{{ route('admin.leaves.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($pendingLeaves as $leave)
                    <div class="list-group-item px-4 py-3">
                        <div class="fw-bold">{{ $leave->user?->name }}</div>
                        <div class="text-muted small">{{ $leave->leave_type_label }} · {{ $leave->from_date->format('d-m-Y') }} to {{ $leave->to_date->format('d-m-Y') }}</div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No pending leave requests.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="table-card h-100">
            <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Pending Documents</h5>
                    <div class="text-muted small">Employee uploads still pending</div>
                </div>
                <a href="{{ route('admin.employees.index') }}" class="btn btn-sm btn-outline-primary">Employees</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($pendingDocuments as $request)
                    <div class="list-group-item px-4 py-3">
                        <div class="fw-bold">{{ $request->employee?->user?->name }}</div>
                        <div class="text-muted small">{{ $request->title }} · Due {{ $request->due_date?->format('d-m-Y') ?? 'not set' }}</div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No pending document requests.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="table-card h-100">
            <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Recent Joiners</h5>
                    <div class="text-muted small">Latest employees onboarded</div>
                </div>
                <a href="{{ route('admin.employees.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentJoiners as $employee)
                    <div class="list-group-item px-4 py-3">
                        <div class="fw-bold">{{ $employee->user?->name }}</div>
                        <div class="text-muted small">
                            {{ $employee->employee_code ?? 'No code' }} · Joined {{ $employee->joining_date?->format('d-m-Y') ?? 'not set' }}
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No employees found.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
