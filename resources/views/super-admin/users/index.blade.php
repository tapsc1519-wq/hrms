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
                <tr><th>User</th><th>Role</th><th>Organization</th><th>Status</th><th>Invite</th><th>Last Login</th><th>Actions</th></tr>
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
                    @php($inviteStatus = \App\Support\UserInvitationService::status($u))
                    <td>
                        <span class="badge bg-{{ $inviteStatus['badge'] }}">{{ $inviteStatus['label'] }}</span>
                        @if($u->invitation_sent_at)
                            <div class="small text-muted mt-1">{{ $u->invitation_sent_at->diffForHumans() }}</div>
                        @endif
                    </td>
                    <td><small>{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('super-admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            @if($u->id !== auth()->id() && $u->status === 'active')
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#inviteUserModal{{ $u->id }}" title="Prepare invite">
                                <i class="bi bi-envelope-paper"></i>
                            </button>
                            @endif
                            @if($u->id !== auth()->id())
                            <form action="{{ route('super-admin.users.destroy', $u) }}" method="POST" onsubmit="return confirm('Delete user?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                <div class="modal fade" id="inviteUserModal{{ $u->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('super-admin.users.invite', $u) }}" method="POST" class="modal-content">
                            @csrf
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title mb-0">Prepare Invite</h5>
                                    <small class="text-muted">{{ $u->name }} - {{ $u->email }}</small>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info small">
                                    This will reset the temporary password, require password change on next login, and generate a copyable invite message.
                                </div>
                                <label class="form-label">Temporary Password</label>
                                <input type="text" name="temporary_password" class="form-control" minlength="8" placeholder="Leave blank to auto-generate">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary btn-sm"><i class="bi bi-envelope-paper me-1"></i>Prepare Invite</button>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white">{{ $users->links() }}</div>
    @endif
</div>
@include('partials._invite_pack_modal')
@endsection
