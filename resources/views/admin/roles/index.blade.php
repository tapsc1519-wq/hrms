@extends('layouts.app')
@section('title', 'Roles & Permissions')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Roles & Permissions</h4>
        <p>Create organization roles and decide exactly what each role can access.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
        <i class="bi bi-shield-plus me-1"></i>Add Role
    </button>
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
            <p class="text-muted">Create your first role to control access by permission.</p>
        </div>
    </div>
    @endforelse
</div>

@include('admin.roles._form_modal', ['role' => null, 'permissionGroups' => $permissionGroups, 'modalId' => 'addRoleModal', 'mode' => 'create'])
@endsection
