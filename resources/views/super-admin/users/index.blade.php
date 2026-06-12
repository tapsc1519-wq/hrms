@extends('layouts.app')

@section('title', 'All Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">All Users</h4>
        <p class="page-subtitle mb-0">{{ $users->total() }} users across all organizations</p>
    </div>
    <a href="{{ route('super-admin.users.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i>Add User
    </a>
</div>

<!-- Filters -->
<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    <option value="super_admin" {{ request('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="vendor" {{ request('role') == 'supplier' ? 'selected' : '' }}>Supplier</option>
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
                <tr><th>User</th><th>Role</th><th>Organization</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-600 flex-shrink-0" style="width:34px;height:34px;font-size:.85rem">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <div>
                                <strong>{{ $u->name }}</strong>
                                <br><small class="text-muted">{{ $u->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-{{ $u->role === 'super_admin' ? 'danger' : ($u->role === 'admin' ? 'primary' : 'info') }}">{{ ucwords(str_replace('_', ' ', $u->role)) }}</span></td>
                    <td>
                        <small>
                            @if($u->organization)
                                {{ $u->organization->name }}
                            @else
                                <span class="text-muted fst-italic">Platform</span>
                            @endif
                        </small>
                    </td>
                    <td><span class="badge bg-{{ $u->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($u->status) }}</span></td>
                    <td><small>{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('super-admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            @if($u->id !== auth()->id())
                            <form action="{{ route('super-admin.users.destroy', $u) }}" method="POST" onsubmit="return confirm('Delete user?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white">{{ $users->links() }}</div>
    @endif
</div>
@endsection
