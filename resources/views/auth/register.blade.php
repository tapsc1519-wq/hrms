@extends('layouts.auth')
@section('title', 'Start Free Trial')

@php
    $siteTitle = \App\Models\Setting::get('site_title', 'Operations Suite');
    $siteLogo = \App\Models\Setting::get('site_logo');
@endphp

@section('content')
<div class="form-header">
    <div class="form-header-icon">
        @if($siteLogo)
            <img src="{{ Storage::url($siteLogo) }}" alt="{{ $siteTitle }}">
        @else
            <i class="bi bi-building-add"></i>
        @endif
    </div>
    <h2>Start your free trial</h2>
    <p>Create your organization workspace. The organization email becomes the first admin login.</p>
</div>

@if($errors->any())
    <div class="auth-alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>{{ $errors->first() }}</div>
    </div>
@endif

<form method="POST" action="{{ route('register.post') }}" id="registerForm" novalidate>
    @csrf

    <div class="field-group">
        <label class="field-label" for="organization_name">Organization Name</label>
        <div class="field-wrap">
            <i class="bi bi-building field-icon"></i>
            <input type="text" id="organization_name" name="organization_name" value="{{ old('organization_name') }}" class="field-input" placeholder="Acme Technologies" required autofocus>
        </div>
    </div>

    <div class="field-group">
        <label class="field-label" for="organization_email">Organization Email</label>
        <div class="field-wrap">
            <i class="bi bi-envelope field-icon"></i>
            <input type="email" id="organization_email" name="organization_email" value="{{ old('organization_email') }}" class="field-input" placeholder="admin@company.com" required autocomplete="email">
        </div>
        <div class="field-help">This email will be used for the admin account.</div>
    </div>

    <div class="field-group">
        <label class="field-label" for="organization_phone">Phone Number</label>
        <div class="field-wrap">
            <i class="bi bi-telephone field-icon"></i>
            <input type="text" id="organization_phone" name="organization_phone" value="{{ old('organization_phone') }}" class="field-input" placeholder="Business phone number" required autocomplete="tel">
        </div>
    </div>

    <div class="field-group">
        <label class="field-label" for="password">Password</label>
        <div class="field-wrap">
            <i class="bi bi-lock field-icon"></i>
            <input type="password" id="password" name="password" class="field-input" placeholder="Minimum 8 characters" required autocomplete="new-password" style="padding-right:2.8rem">
            <button type="button" class="field-toggle" id="toggleRegisterPwd" title="Show or hide password">
                <i class="bi bi-eye" id="registerPwdIcon"></i>
            </button>
        </div>
        <div class="field-help">Use at least 8 characters. You can add SSO later from Organization SSO settings.</div>
    </div>

    <button type="submit" class="btn-signin" id="registerBtn">
        <i class="bi bi-rocket-takeoff"></i>
        <span id="registerLabel">Create Trial Account</span>
    </button>
</form>

<div class="auth-switch-link">
    Already have an account? <a href="{{ route('login') }}">Sign in</a>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('toggleRegisterPwd').addEventListener('click', function () {
    var input = document.getElementById('password');
    var icon = document.getElementById('registerPwdIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
        this.title = 'Hide password';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
        this.title = 'Show password';
    }
});

document.getElementById('registerForm').addEventListener('submit', function () {
    var btn = document.getElementById('registerBtn');
    var label = document.getElementById('registerLabel');
    btn.classList.add('loading');
    btn.querySelector('i').className = 'bi bi-arrow-repeat spin';
    label.textContent = 'Creating workspace...';
});
</script>
@endpush
