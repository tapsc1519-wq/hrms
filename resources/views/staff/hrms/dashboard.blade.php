@extends('layouts.app')

@section('title', 'My HRMS')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h4>My HRMS</h4>
        <p>Your attendance, leave balances, documents, and employee profile snapshot.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('staff.leaves.create') }}" class="btn btn-primary">
            <i class="bi bi-calendar-plus-fill me-1"></i> Apply Leave
        </a>
        <a href="{{ route('staff.profile.show') }}" class="btn btn-outline-secondary">
            <i class="bi bi-person-vcard me-1"></i> My Profile
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-gradient grad-blue p-3">
            <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
            <div class="stat-value">{{ $stats['present_this_month'] }}</div>
            <div class="stat-label">Present This Month</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-green p-3">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value">{{ floor($stats['work_minutes_this_month'] / 60) }}h</div>
            <div class="stat-label">Work Hours This Month</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-orange p-3">
            <div class="stat-icon"><i class="bi bi-calendar-heart-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['available_leave_days'], 1) }}</div>
            <div class="stat-label">Available Leave Days</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-purple p-3">
            <div class="stat-icon"><i class="bi bi-file-earmark-arrow-up-fill"></i></div>
            <div class="stat-value">{{ $stats['pending_documents'] }}</div>
            <div class="stat-label">Pending Documents</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="form-card h-100">
            <div class="form-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Today Attendance</h5>
                    <p class="text-muted mb-0 small">{{ now()->format('d-m-Y') }}</p>
                </div>
                <span class="icon-wrap icon-blue"><i class="bi bi-clock-history"></i></span>
            </div>
            <div class="form-card-body">
                @if(!$today)
                    <div class="alert alert-info border-0">
                        You have not signed in today.
                    </div>
                    <form method="POST" action="{{ route('staff.attendance.sign-in') }}">
                        @csrf
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </form>
                @elseif(!$today->sign_out_at)
                    <div class="alert alert-success border-0">
                        Signed in at <strong>{{ $today->sign_in_at?->format('h:i A') }}</strong>.
                    </div>
                    <form method="POST" action="{{ route('staff.attendance.sign-out') }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-warning w-100">
                            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                        </button>
                    </form>
                @else
                    <div class="alert alert-secondary border-0 mb-0">
                        Completed today: <strong>{{ $today->work_duration }}</strong>
                        <div class="small text-muted mt-1">
                            {{ $today->sign_in_at?->format('h:i A') }} to {{ $today->sign_out_at?->format('h:i A') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="form-card h-100">
            <div class="form-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Employee Snapshot</h5>
                    <p class="text-muted mb-0 small">Your HR profile basics.</p>
                </div>
                <span class="icon-wrap icon-green"><i class="bi bi-person-badge-fill"></i></span>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Employee Code</div>
                        <div class="fw-bold">{{ $employee->employee_code ?? 'Not set' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Department</div>
                        <div class="fw-bold">{{ $employee->user?->department?->name ?? 'Not set' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Joining Date</div>
                        <div class="fw-bold">{{ $employee->joining_date?->format('d-m-Y') ?? 'Not set' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Reporting Manager</div>
                        <div class="fw-bold">{{ $employee->manager?->name ?? 'Not set' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Shift</div>
                        <div class="fw-bold">{{ $employee->shift?->name ?? 'Not set' }}{{ $employee->shift ? ' ('.$employee->shift->time_range.')' : '' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Facility</div>
                        <div class="fw-bold">{{ $employee->facility?->name ?? 'Not set' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Work Location</div>
                        <div class="fw-bold">{{ $employee->location?->name ?? 'Not set' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="table-card h-100">
            <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Leave Balances</h5>
                    <div class="text-muted small">Current year entitlement</div>
                </div>
                <a href="{{ route('staff.leaves.index') }}" class="btn btn-sm btn-outline-primary">View Leaves</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($leaveBalances as $balance)
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold">{{ $balance->leaveType?->name }}</span>
                            <span class="badge {{ $balance->available > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                {{ number_format($balance->available, 1) }} left
                            </span>
                        </div>
                        <div class="progress" style="height:7px">
                            @php
                                $total = max(1, (float) $balance->opening_balance + (float) $balance->credited);
                                $usedPercent = min(100, round(((float) $balance->used / $total) * 100));
                            @endphp
                            <div class="progress-bar bg-warning" style="width:{{ $usedPercent }}%"></div>
                        </div>
                        <div class="text-muted small mt-1">{{ number_format($balance->used, 1) }} used of {{ number_format($total, 1) }}</div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No leave balances configured.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="table-card h-100">
            <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Pending Documents</h5>
                    <div class="text-muted small">Upload requested HR documents</div>
                </div>
                <a href="{{ route('staff.profile.show') }}" class="btn btn-sm btn-outline-primary">Upload</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($pendingDocuments as $request)
                    <div class="list-group-item px-4 py-3">
                        <div class="fw-bold">{{ $request->title }}</div>
                        <div class="text-muted small">
                            {{ $request->document_type_label }} · Due {{ $request->due_date?->format('d-m-Y') ?? 'not set' }}
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No pending document uploads.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="table-card h-100">
            <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Recent Leaves</h5>
                    <div class="text-muted small">Latest leave requests</div>
                </div>
                <a href="{{ route('staff.leaves.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentLeaves as $leave)
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <div class="fw-bold">{{ $leave->leave_type_label }}</div>
                                <div class="text-muted small">{{ $leave->from_date->format('d-m-Y') }} to {{ $leave->to_date->format('d-m-Y') }}</div>
                            </div>
                            <span class="badge bg-{{ $leave->status_badge }} align-self-start">{{ ucfirst($leave->status) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No leave requests yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
