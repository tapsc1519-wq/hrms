@extends('layouts.app')

@section('title', 'Access & Permissions')

@push('styles')
<style>
.user-filter-card { padding: .65rem .85rem; }
.user-filter-card .row { margin-bottom: 0; }
.user-action-wrap { display:flex; gap:.35rem; align-items:center; flex-wrap:nowrap; }
.user-action-wrap form { margin:0; display:inline-flex; }
.user-action-btn {
    width:32px;
    height:32px;
    padding:0;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    line-height:1;
    flex-shrink:0;
}
</style>
@endpush

@section('content')
@php($canManageEmployees = auth()->user()->hasPermission('employees.manage'))
@php($canManageSuppliers = auth()->user()->hasPermission('suppliers.manage'))
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Access & Permissions</h4>
        <p class="page-subtitle mb-0">Manage login access, roles, and linked portal profiles</p>
    </div>
    <div class="d-flex gap-2">
        @if($canManageEmployees)
        <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
            <i class="bi bi-person-vcard me-1"></i>Add Employee
        </a>
        @else
        <button type="button" class="btn btn-primary" disabled title="Employees permission required">
            <i class="bi bi-person-vcard me-1"></i>Add Employee
        </button>
        @endif
        @if($canManageSuppliers)
        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-outline-primary">
            <i class="bi bi-building-add me-1"></i>Add Supplier
        </a>
        @else
        <button type="button" class="btn btn-outline-primary" disabled title="Suppliers permission required">
            <i class="bi bi-building-add me-1"></i>Add Supplier
        </button>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="alert alert-info mb-0">
            <div class="d-flex gap-3">
                <i class="bi bi-info-circle fs-5"></i>
                <div>
                    <div class="fw-semibold">Use the right onboarding path</div>
                    <div class="small">Create employees, suppliers, vendors, auditors, and disposal buyers from their own sections. Use this page only to review existing login access, reset passwords, change roles and permissions, and activate or deactivate access.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="alert {{ $unlinkedInternalUsers ? 'alert-warning' : 'alert-success' }} mb-0 h-100">
            <div class="fw-semibold">{{ $unlinkedInternalUsers }} internal {{ Str::plural('account', $unlinkedInternalUsers) }} without employee profile</div>
            <div class="small">{{ $unlinkedInternalUsers ? 'Link these accounts before enabling HRMS, attendance, assets, or agent enrollment.' : 'Internal accounts are linked correctly.' }}</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="table-card mb-3">
    <div class="user-filter-card">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Access Types</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="supplier" {{ request('role') == 'supplier' ? 'selected' : '' }}>Supplier</option>
                    <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr><th>Account</th><th>Access Type</th><th>Linked Identity</th><th>Roles & Permissions</th><th>Department</th><th>Employee ID</th><th>Last Login</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0 fw-600" style="width:34px;height:34px;font-size:.85rem">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <div>
                                <strong>{{ $u->name }}</strong>
                                <br><small class="text-muted">{{ $u->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $u->role)) }}</span></td>
                    <td>
                        @if(in_array($u->role, ['admin', 'staff'], true))
                            @if($u->employeeProfile)
                                @if($canManageEmployees)
                                <a href="{{ route('admin.employees.show', $u->employeeProfile) }}" class="badge bg-success text-decoration-none">Employee linked</a>
                                @else
                                <span class="badge bg-success">Employee linked</span>
                                @endif
                            @else
                                <span class="badge bg-warning text-dark">Needs employee profile</span>
                                <div class="small text-muted mt-1">Create from Employees</div>
                            @endif
                        @elseif($u->role === 'supplier')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Supplier portal</span>
                        @else
                            <span class="badge bg-light text-dark border">Profile optional</span>
                        @endif
                    </td>
                    <td><small>{{ $u->customRole?->name ?? 'Default permissions' }}</small></td>
                    <td><small>{{ $u->department?->name ?? 'â€”' }}</small></td>
                    <td><small class="text-muted">{{ $u->employee_id ?? 'â€”' }}</small></td>
                    <td><small>{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</small></td>
                    <td><span class="badge bg-{{ $u->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($u->status) }}</span></td>
                    <td>
                        <div class="user-action-wrap">
                            <button class="btn btn-sm btn-outline-primary user-action-btn" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($u->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $u) }}" method="POST"
                                  onsubmit="return confirm('Delete access account for {{ $u->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger user-action-btn"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>

                <!-- Edit Access Modal -->
                <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <form action="{{ route('admin.users.update', $u) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Access: {{ $u->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-500">Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-500">Email</label>
                                            <input type="email" name="email" class="form-control" value="{{ $u->email }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-500">New Password <small class="text-muted">(leave blank to keep)</small></label>
                                            <input type="password" name="password" class="form-control" minlength="8">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-500">Access Type</label>
                                            <input type="hidden" name="role" value="{{ $u->role }}">
                                            <div class="form-control bg-light text-muted">{{ ucwords(str_replace('_', ' ', $u->role)) }}</div>
                                            <div class="form-text">Change identity type from the related section.</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-500">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="active" {{ $u->status === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ $u->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-500">Roles & Permissions</label>
                                            <select name="custom_role_id" class="form-select">
                                                <option value="">Default permissions</option>
                                                @foreach($customRoles as $role)
                                                <option value="{{ $role->id }}" {{ $u->custom_role_id == $role->id ? 'selected' : '' }} @disabled($role->portal_role !== $u->role)>
                                                    {{ $role->name }} ({{ ucfirst($role->portal_role) }})
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <div class="alert alert-light border small mb-0">
                                                Change employee, supplier, vendor, auditor, or buyer identity details from their own section. This page only controls login access.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">No access accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white">{{ $users->links() }}</div>
    @endif
</div>

@endsection

