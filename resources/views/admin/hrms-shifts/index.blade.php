@extends('layouts.app')

@section('title', 'HRMS Shifts')

@php
    $days = [
        'mon' => 'Mon',
        'tue' => 'Tue',
        'wed' => 'Wed',
        'thu' => 'Thu',
        'fri' => 'Fri',
        'sat' => 'Sat',
        'sun' => 'Sun',
    ];
@endphp

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h4>Shifts</h4>
        <p>Define work timings, grace minutes, working days, and shift rules for employees.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createShiftModal">
        <i class="bi bi-plus-lg me-1"></i> Add Shift
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-gradient grad-blue p-3">
            <div class="stat-icon"><i class="bi bi-calendar3-week-fill"></i></div>
            <div class="stat-value">{{ $shifts->count() }}</div>
            <div class="stat-label">Total Shifts</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-green p-3">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-value">{{ $shifts->where('status', 'active')->count() }}</div>
            <div class="stat-label">Active Shifts</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-purple p-3">
            <div class="stat-icon"><i class="bi bi-moon-stars-fill"></i></div>
            <div class="stat-value">{{ $shifts->where('is_night_shift', true)->count() }}</div>
            <div class="stat-label">Night Shifts</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-orange p-3">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value">{{ $shifts->sum('employees_count') }}</div>
            <div class="stat-label">Assigned Employees</div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Shift</th>
                    <th>Timing</th>
                    <th>Rules</th>
                    <th>Working Days</th>
                    <th>Employees</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $shift->name }}</div>
                            <div class="text-muted small">{{ $shift->code }}</div>
                            @if($shift->description)
                                <div class="text-muted small mt-1">{{ $shift->description }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $shift->time_range }}</div>
                            @if($shift->is_night_shift)
                                <span class="badge bg-dark">Night Shift</span>
                            @endif
                        </td>
                        <td class="small">
                            Grace: {{ $shift->grace_minutes }} min<br>
                            Half day: {{ $shift->half_day_minutes }} min<br>
                            Full day: {{ $shift->full_day_minutes }} min
                        </td>
                        <td>{{ $shift->working_days_label ?: 'Not set' }}</td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ $shift->employees_count }}</span></td>
                        <td><span class="badge bg-{{ $shift->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($shift->status) }}</span></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editShift{{ $shift->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.hrms-shifts.destroy', $shift) }}" class="d-inline" onsubmit="return confirm('Delete this shift?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" @disabled($shift->employees_count > 0)>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No shifts configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="createShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.hrms-shifts.store') }}" class="modal-content border-0" style="border-radius:16px">
            @csrf
            @include('admin.hrms-shifts._form', ['shift' => null, 'days' => $days])
        </form>
    </div>
</div>

@foreach($shifts as $shift)
    <div class="modal fade" id="editShift{{ $shift->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('admin.hrms-shifts.update', $shift) }}" class="modal-content border-0" style="border-radius:16px">
                @csrf
                @method('PATCH')
                @include('admin.hrms-shifts._form', ['shift' => $shift, 'days' => $days])
            </form>
        </div>
    </div>
@endforeach
@endsection
