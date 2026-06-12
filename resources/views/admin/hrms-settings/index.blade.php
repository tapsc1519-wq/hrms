@extends('layouts.app')

@section('title', 'HRMS Settings')

@section('content')
<div class="page-header">
    <h4>HRMS Settings</h4>
    <p>Configure attendance rules, leave types, and holiday calendar for your organization.</p>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <form method="POST" action="{{ route('admin.hrms-settings.rules.update') }}" class="form-card">
            @csrf
            @method('PUT')
            <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-clock-history"></i></span> Attendance Rules</div>
            <div class="form-card-body">
                <label class="form-label">Working Days</label>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach(['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'] as $value => $label)
                        <label class="btn btn-sm btn-outline-primary">
                            <input type="checkbox" name="working_days[]" value="{{ $value }}" class="me-1" @checked(in_array($value, $setting->working_days ?? []))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Office Start</label>
                        <input type="time" name="office_start_time" class="form-control" value="{{ substr($setting->office_start_time, 0, 5) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Office End</label>
                        <input type="time" name="office_end_time" class="form-control" value="{{ substr($setting->office_end_time, 0, 5) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Grace Minutes</label>
                        <input type="number" name="grace_minutes" class="form-control" value="{{ $setting->grace_minutes }}" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Half Day Minutes</label>
                        <input type="number" name="half_day_minutes" class="form-control" value="{{ $setting->half_day_minutes }}" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Full Day Minutes</label>
                        <input type="number" name="full_day_minutes" class="form-control" value="{{ $setting->full_day_minutes }}" min="1" required>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" name="allow_weekend_attendance" value="1" class="form-check-input" @checked($setting->allow_weekend_attendance)>
                            <span class="form-check-label">Allow weekend attendance</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary btn-save"><i class="bi bi-check2-circle"></i> Save Rules</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.hrms-settings.leave-types.store') }}" class="form-card">
            @csrf
            <div class="form-card-header"><span class="icon-wrap icon-green"><i class="bi bi-calendar-plus"></i></span> Add Leave Type</div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Casual Leave" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" placeholder="CL" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quota</label>
                        <input type="number" name="annual_quota" class="form-control" value="0" step="0.5" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-check mt-2">
                            <input type="checkbox" name="is_paid" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Paid leave</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="form-check mt-2">
                            <input type="checkbox" name="requires_approval" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Requires approval</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary btn-save"><i class="bi bi-plus-lg"></i> Add Leave Type</button>
            </div>
        </form>
    </div>

    <div class="col-lg-7">
        <div class="table-card mb-4">
            <div class="card-header">Leave Types</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc">
                        <tr><th class="ps-4">Type</th><th>Quota</th><th>Rules</th><th>Status</th><th class="text-end pe-4">Delete</th></tr>
                    </thead>
                    <tbody>
                        @forelse($leaveTypes as $type)
                            <tr>
                                <td class="ps-4"><div class="fw-bold">{{ $type->name }}</div><div class="text-muted small">{{ $type->code }}</div></td>
                                <td>{{ rtrim(rtrim($type->annual_quota, '0'), '.') }} days</td>
                                <td>
                                    <span class="badge bg-{{ $type->is_paid ? 'success' : 'secondary' }}">{{ $type->is_paid ? 'Paid' : 'Unpaid' }}</span>
                                    <span class="badge bg-{{ $type->requires_approval ? 'warning text-dark' : 'info' }}">{{ $type->requires_approval ? 'Approval' : 'Auto' }}</span>
                                </td>
                                <td><span class="badge bg-{{ $type->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($type->status) }}</span></td>
                                <td class="text-end pe-4">
                                    <form method="POST" action="{{ route('admin.hrms-settings.leave-types.destroy', $type) }}" onsubmit="return confirm('Delete this leave type?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No leave types configured yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-amber"><i class="bi bi-calendar-event"></i></span> Holiday Calendar</div>
            <form method="POST" action="{{ route('admin.hrms-settings.holidays.store') }}">
                @csrf
                <div class="form-card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Holiday Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Diwali" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="holiday_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="public">Public</option>
                                <option value="company">Company</option>
                                <option value="optional">Optional</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                </div>
            </form>
            <div class="table-responsive border-top">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc"><tr><th class="ps-4">Holiday</th><th>Date</th><th>Type</th><th class="text-end pe-4">Delete</th></tr></thead>
                    <tbody>
                        @forelse($holidays as $holiday)
                            <tr>
                                <td class="ps-4 fw-bold">{{ $holiday->name }}</td>
                                <td>{{ $holiday->holiday_date->format('d-m-Y') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ ucfirst($holiday->type) }}</span></td>
                                <td class="text-end pe-4">
                                    <form method="POST" action="{{ route('admin.hrms-settings.holidays.destroy', $holiday) }}" onsubmit="return confirm('Delete this holiday?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No holidays configured yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
