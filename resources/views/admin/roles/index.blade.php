@extends('layouts.app')
@section('title', 'Roles & Permissions')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Roles & Permissions</h4>
        <p>Create organization roles and decide exactly what each role can access.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#suggestedRolesModal">
            <i class="bi bi-magic me-1"></i>Add Suggested Roles
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
            <i class="bi bi-shield-plus me-1"></i>Add Role
        </button>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger rounded-3">{{ $errors->first() }}</div>
@endif
@if(session('success'))
<div class="alert alert-success rounded-3">{{ session('success') }}</div>
@endif

<div class="row g-3">
    @forelse($roles as $role)
    <div class="col-lg-6">
        <div class="table-card h-100" style="padding:0;overflow:hidden">
            <div style="height:4px;background:linear-gradient(90deg,#3b82f6,#6366f1)"></div>
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1">{{ $role->name }}</h5>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge bg-info">{{ ucfirst($role->portal_role) }} Portal</span>
                            <span class="badge bg-{{ $role->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($role->status) }}</span>
                            <span class="text-muted small">{{ $role->users_count }} user{{ $role->users_count !== 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editRoleModal{{ $role->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if($role->users_count === 0)
                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete this role?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </div>
                </div>

                @if($role->description)
                <p class="text-muted small mb-3">{{ $role->description }}</p>
                @endif

                <div class="d-flex flex-wrap gap-1">
                    @forelse($role->permissions ?? [] as $permission)
                    <span class="badge bg-light text-dark border">{{ $permission }}</span>
                    @empty
                    <span class="text-muted small">No permissions assigned.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @include('admin.roles._form_modal', ['role' => $role, 'permissionGroups' => $permissionGroups, 'modalId' => 'editRoleModal'.$role->id, 'mode' => 'edit'])
    @empty
    <div class="col-12">
        <div class="table-card text-center py-5">
            <i class="bi bi-shield-lock fs-1 d-block mb-3 opacity-25"></i>
            <h5>No custom roles yet</h5>
            <p class="text-muted">Create suggested starter roles or add a custom role manually.</p>
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#suggestedRolesModal">
                <i class="bi bi-magic me-1"></i>Add Suggested Roles
            </button>
        </div>
    </div>
    @endforelse
</div>

<div class="modal fade" id="suggestedRolesModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form action="{{ route('admin.roles.suggested.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="icon-wrap icon-blue"><i class="bi bi-magic"></i></span>
                        <div>
                            <h5 class="modal-title mb-0">Add Suggested Roles</h5>
                            <div class="text-muted small">Create starter permission sets. Existing role names will be skipped.</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i>
                        Suggested roles are conservative starter templates. Review and edit permissions after creation to match the organization policy.
                    </div>
                    <div class="row g-2">
                        @foreach($suggestedRoles as $role)
                            <div class="col-lg-6">
                                <label class="border rounded-3 p-3 d-flex gap-3 h-100" style="cursor:pointer;background:#f8fafc">
                                    <input class="form-check-input mt-1" type="checkbox" name="roles[]" value="{{ $role['name'] }}" checked>
                                    <span class="flex-grow-1">
                                        <span class="d-flex align-items-center justify-content-between gap-2">
                                            <span class="fw-bold">{{ $role['name'] }}</span>
                                            <span class="badge bg-info">{{ ucfirst($role['portal_role']) }} Portal</span>
                                        </span>
                                        <span class="text-muted small d-block mt-1">{{ $role['description'] }}</span>
                                        <span class="badge bg-light text-dark border mt-2">{{ count($role['permissions']) }} permissions</span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-shield-plus me-1"></i>Create Selected Roles
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@include('admin.roles._form_modal', ['role' => null, 'permissionGroups' => $permissionGroups, 'modalId' => 'addRoleModal', 'mode' => 'create'])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (new URLSearchParams(window.location.search).get('suggested') !== '1') return;
    var modalElement = document.getElementById('suggestedRolesModal');
    if (modalElement && window.bootstrap) {
        new bootstrap.Modal(modalElement).show();
    }
});
</script>
@endpush
