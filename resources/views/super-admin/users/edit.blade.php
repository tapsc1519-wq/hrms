@extends('layouts.app')
@section('title', 'Edit User — ' . $user->name)

@section('content')
<div class="page-header">
    <a href="{{ route('super-admin.users.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Users</a>
    <h4>Edit User</h4>
    <p>Update account details for <strong>{{ $user->name }}</strong>.</p>
</div>

<form action="{{ route('super-admin.users.update', $user) }}" method="POST">
@csrf @method('PUT')
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
                               value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address <span class="req">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role <span class="req">*</span></label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="super_admin" {{ old('role', $user->role) == 'super_admin' ? 'selected':'' }}>Super Admin</option>
                            <option value="admin"       {{ old('role', $user->role) == 'admin'       ? 'selected':'' }}>Admin</option>
                            <option value="staff"       {{ old('role', $user->role) == 'staff'       ? 'selected':'' }}>Staff</option>
                            <option value="supplier"      {{ old('role', $user->role) == 'supplier'      ? 'selected':'' }}>Supplier</option>
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active"   {{ old('status', $user->status) == 'active'   ? 'selected':'' }}>Active</option>
                            <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected':'' }}>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Organisation</label>
                        <select name="organization_id" class="form-select @error('organization_id') is-invalid @enderror">
                            <option value="">— None (Platform-level account) —</option>
                            @foreach($organizations as $org)
                            <option value="{{ $org->id }}"
                                    {{ old('organization_id', $user->organization_id) == $org->id ? 'selected':'' }}>
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
                Change Password
                <span style="font-size:.7rem;font-weight:400;opacity:.7;margin-left:.4rem">Optional</span>
            </div>
            <div class="form-card-body">
                <div class="rounded-3 mb-3 px-3 py-2" style="background:#eff6ff;border:1px solid #bfdbfe;font-size:.82rem;color:#1d4ed8">
                    <i class="bi bi-info-circle me-1"></i>
                    Leave both fields blank to keep the current password unchanged.
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               placeholder="Min. 8 characters">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Repeat new password">
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
                               value="{{ old('phone', $user->phone) }}" placeholder="+63 9XX XXX XXXX">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror"
                               value="{{ old('employee_id', $user->employee_id) }}" placeholder="EMP-001">
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror"
                               value="{{ old('job_title', $user->job_title) }}" placeholder="e.g. IT Manager">
                        @error('job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Meta --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-slate"><i class="bi bi-clock-history"></i></span>
                Account Info
            </div>
            <div class="form-card-body">
                <div style="font-size:.82rem;color:#64748b;line-height:2">
                    <div><span style="font-weight:600;color:#334155">Joined:</span> {{ $user->created_at->format('d-m-Y') }}</div>
                    <div><span style="font-weight:600;color:#334155">Last Login:</span>
                        {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
                    </div>
                    <div><span style="font-weight:600;color:#334155">Role:</span>
                        <span class="badge bg-{{ $user->role === 'super_admin' ? 'danger' : ($user->role === 'admin' ? 'primary' : ($user->role === 'staff' ? 'success' : 'warning')) }} text-white">
                            {{ ucfirst(str_replace('_',' ',$user->role)) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('super-admin.users.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Save Changes
    </button>
</div>
</form>
@endsection
