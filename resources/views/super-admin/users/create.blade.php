@extends('layouts.app')
@section('title', 'Add User')

@section('content')
<div class="page-header">
    <a href="{{ route('super-admin.users.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Users</a>
    <h4>Add New User</h4>
    <p>Create a platform user and assign them to an organisation.</p>
</div>

<form action="{{ route('super-admin.users.store') }}" method="POST">
@csrf
@if(($selectedOrganizationId ?? null) && ($selectedRole ?? null) === 'admin')
    <input type="hidden" name="onboarding_redirect" value="1">
@endif
<div class="row g-4">

    {{-- LEFT --}}
    <div class="col-lg-8">

        {{-- Account Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-person-circle"></i></span>
                Account Information
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Full Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. John Dela Cruz" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address <span class="req">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" placeholder="user@company.com" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role <span class="req">*</span></label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            <option value="super_admin" {{ old('role', $selectedRole ?? '') == 'super_admin' ? 'selected':'' }}>Super Admin</option>
                            <option value="admin"       {{ old('role', $selectedRole ?? '') == 'admin'       ? 'selected':'' }}>Admin</option>
                            <option value="staff"       {{ old('role', $selectedRole ?? '') == 'staff'       ? 'selected':'' }}>Staff</option>
                            <option value="supplier"      {{ old('role', $selectedRole ?? '') == 'supplier'      ? 'selected':'' }}>Supplier</option>
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active"   {{ old('status','active') == 'active'   ? 'selected':'' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected':'' }}>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Organisation</label>
                        <select name="organization_id" class="form-select @error('organization_id') is-invalid @enderror">
                            <option value="">— None (Platform-level account) —</option>
                            @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ old('organization_id', $selectedOrganizationId ?? '') == $org->id ? 'selected':'' }}>
                                {{ $org->name }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Leave blank for Super Admin accounts.</div>
                        @error('organization_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Password --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-red"><i class="bi bi-shield-lock"></i></span>
                Password
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Password <span class="req">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               placeholder="Min. 8 characters" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password <span class="req">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Repeat password" required>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT --}}
    <div class="col-lg-4">

        {{-- Profile Details --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-purple"><i class="bi bi-person-vcard"></i></span>
                Profile Details
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone') }}" placeholder="+63 9XX XXX XXXX">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror"
                               value="{{ old('employee_id') }}" placeholder="EMP-001">
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror"
                               value="{{ old('job_title') }}" placeholder="e.g. IT Manager">
                        @error('job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('super-admin.users.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-person-plus"></i> Create User
    </button>
</div>
</form>
@endsection
