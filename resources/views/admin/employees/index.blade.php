@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h4>Employee Profiles</h4>
            <p>HR master records connected with users, departments, facilities, IT assets, and software.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.employees.bulk-import.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-cloud-upload me-1"></i> Bulk Import
            </a>
            <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Employee
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card-gradient grad-blue">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Profiles</div>
                    <div class="stat-number">{{ $employees->total() }}</div>
                    <div class="stat-sub">HR records created</div>
                </div>
                <div class="stat-icon"><i class="bi bi-person-vcard"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-gradient grad-orange">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Pending</div>
                    <div class="stat-number">{{ $pendingProfiles }}</div>
                    <div class="stat-sub">Users without HR profile</div>
                </div>
                <div class="stat-icon"><i class="bi bi-person-plus"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-gradient grad-green">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Departments</div>
                    <div class="stat-number">{{ $departments->count() }}</div>
                    <div class="stat-sub">Available sections</div>
                </div>
                <div class="stat-icon"><i class="bi bi-diagram-3"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="table-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" value="{{ request('search') }}" placeholder="Name, email, or employee code">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All status</option>
                    @foreach(['active' => 'Active', 'probation' => 'Probation', 'notice' => 'Notice Period', 'resigned' => 'Resigned', 'terminated' => 'Terminated'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
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
            <div class="col-md-1">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4" style="font-size:.72rem;text-transform:uppercase;color:#64748b">Employee</th>
                    <th style="font-size:.72rem;text-transform:uppercase;color:#64748b">Department</th>
                    <th style="font-size:.72rem;text-transform:uppercase;color:#64748b">Manager</th>
                    <th style="font-size:.72rem;text-transform:uppercase;color:#64748b">Work Location</th>
                    <th style="font-size:.72rem;text-transform:uppercase;color:#64748b">Status</th>
                    <th style="font-size:.72rem;text-transform:uppercase;color:#64748b">Joining</th>
                    <th class="text-end pe-4" style="font-size:.72rem;text-transform:uppercase;color:#64748b">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="topbar-avatar">{{ strtoupper(substr($employee->user->name, 0, 1)) }}</div>
                                <div>
                                    <div class="fw-bold">{{ $employee->user->name }}</div>
                                    <div class="text-muted small">{{ $employee->user->email }}</div>
                                    <span class="badge bg-light text-dark border mt-1">{{ $employee->employee_code ?: 'No code' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>{{ $employee->user->department?->name ?? '—' }}</td>
                        <td>{{ $employee->manager?->name ?? '—' }}</td>
                        <td>
                            <div>{{ $employee->facility?->name ?? '—' }}</div>
                            <div class="text-muted small">{{ $employee->location?->name }}</div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $employee->employment_status_badge }}">{{ $employee->employment_status_label }}</span>
                            <div class="text-muted small mt-1">{{ $employee->employment_type_label }}</div>
                        </td>
                        <td>{{ $employee->joining_date?->format('d-m-Y') ?? '—' }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="icon-wrap icon-blue mx-auto mb-3" style="width:46px;height:46px"><i class="bi bi-person-vcard"></i></div>
                            <h6 class="fw-bold">No employee profiles yet</h6>
                            <p class="text-muted mb-3">Create profiles for staff and admins to start the HRMS module.</p>
                            <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-sm">Add Employee Profile</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
        <div class="p-3 border-top">{{ $employees->links() }}</div>
    @endif
</div>
@endsection
