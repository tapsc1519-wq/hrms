@extends('layouts.app')

@section('title', 'Leave Balances')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h4>Leave Balances</h4>
        <p>Adjust opening, credited, and used leave days for each employee and leave type.</p>
    </div>
    <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-calendar-check me-1"></i> Leave Requests
    </a>
</div>

<div class="table-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Year</label>
                <input type="number" min="2020" max="2100" name="year" class="form-control" value="{{ $year }}">
            </div>
            <div class="col-md-3">
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
                <label class="form-label">Leave Type</label>
                <select name="leave_type_id" class="form-select">
                    <option value="">All types</option>
                    @foreach($leaveTypes as $leaveType)
                        <option value="{{ $leaveType->id }}" @selected((string) request('leave_type_id') === (string) $leaveType->id)>
                            {{ $leaveType->name }}
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

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-gradient grad-blue p-3">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value">{{ $balances->total() }}</div>
            <div class="stat-label">Balance Rows</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-green p-3">
            <div class="stat-icon"><i class="bi bi-plus-circle-fill"></i></div>
            <div class="stat-value">{{ number_format($balances->sum('credited'), 1) }}</div>
            <div class="stat-label">Credited On Page</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-orange p-3">
            <div class="stat-icon"><i class="bi bi-calendar-minus-fill"></i></div>
            <div class="stat-value">{{ number_format($balances->sum('used'), 1) }}</div>
            <div class="stat-label">Used On Page</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-purple p-3">
            <div class="stat-icon"><i class="bi bi-calendar-heart-fill"></i></div>
            <div class="stat-value">{{ number_format($balances->sum(fn($b) => $b->available), 1) }}</div>
            <div class="stat-label">Available On Page</div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Employee</th>
                    <th>Leave Type</th>
                    <th style="width:130px">Opening</th>
                    <th style="width:130px">Credited</th>
                    <th style="width:130px">Used</th>
                    <th class="text-center">Available</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $balance)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $balance->employee?->user?->name }}</div>
                            <div class="text-muted small">
                                {{ $balance->employee?->employee_code ?? 'No employee code' }}
                                @if($balance->employee?->user?->department)
                                    · {{ $balance->employee->user->department->name }}
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary">{{ $balance->leaveType?->name }}</span>
                        </td>
                        <td>
                            <input form="balance-form-{{ $balance->id }}" type="number" name="opening_balance" min="0" step="0.5" class="form-control form-control-sm" value="{{ old('opening_balance', $balance->opening_balance) }}">
                        </td>
                        <td>
                            <input form="balance-form-{{ $balance->id }}" type="number" name="credited" min="0" step="0.5" class="form-control form-control-sm" value="{{ old('credited', $balance->credited) }}">
                        </td>
                        <td>
                            <input form="balance-form-{{ $balance->id }}" type="number" name="used" min="0" step="0.5" class="form-control form-control-sm" value="{{ old('used', $balance->used) }}">
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $balance->available > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                {{ number_format($balance->available, 1) }} days
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <form id="balance-form-{{ $balance->id }}" method="POST" action="{{ route('admin.leave-balances.update', $balance) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-primary">
                                    <i class="bi bi-check2 me-1"></i> Save
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-calendar-range display-6 d-block mb-2"></i>
                            No leave balances found. Add active employees and leave types first.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($balances->hasPages())
        <div class="p-3 border-top">{{ $balances->links() }}</div>
    @endif
</div>
@endsection
