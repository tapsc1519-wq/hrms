@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h4>Change Password</h4>
            <p>Update the password for your portal account.</p>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-xl-6 col-lg-7">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-shield-lock"></i></span>
                Account Security
            </div>
            <div class="form-card-body">
                <div class="alert alert-info border-0 rounded-3 small">
                    <i class="bi bi-info-circle me-2"></i>
                    Enter your current password first, then choose a new password with at least 8 characters.
                </div>

                <form method="POST" action="{{ route('account.password.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="req">*</span></label>
                        <input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required autofocus>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password <span class="req">*</span></label>
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm New Password <span class="req">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" minlength="8" required>
                    </div>

                    <div class="form-actions">
                        <a href="javascript:history.back()" class="btn-cancel">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-save">
                            <i class="bi bi-check2-circle"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
