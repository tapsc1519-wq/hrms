@extends('layouts.auth')
@section('title', 'Sign In')

@php
    $siteTitle = \App\Models\Setting::get('site_title', 'ITAM Suite');
    $siteLogo = \App\Models\Setting::get('site_logo');
@endphp

@section('content')
<div class="form-header">
    <div class="form-header-icon">
        @if($siteLogo)
            <img src="{{ Storage::url($siteLogo) }}" alt="{{ $siteTitle }}">
        @else
            <i class="bi bi-grid-1x2-fill"></i>
        @endif
    </div>
    <h2>Sign in to your workspace</h2>
    <p>Access HRMS, IT assets, software licenses, payroll, support and supplier workflows from one secure account.</p>
</div>

@if($errors->any())
    <div class="auth-alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>{{ $errors->first() }}</div>
    </div>
@endif
@if(session('error'))
    <div class="auth-alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

<form method="POST" action="{{ route('login.post') }}" id="loginForm" novalidate>
    @csrf

    <div class="field-group">
        <label class="field-label" for="email">Email Address</label>
        <div class="field-wrap">
            <i class="bi bi-envelope field-icon"></i>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   class="field-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                   placeholder="you@company.com"
                   required autofocus autocomplete="email">
        </div>
    </div>

    <div class="field-group">
        <label class="field-label" for="password">Password</label>
        <div class="field-wrap">
            <i class="bi bi-lock field-icon"></i>
            <input type="password"
                   id="password"
                   name="password"
                   class="field-input"
                   placeholder="Enter your password"
                   required autocomplete="current-password"
                   style="padding-right:2.8rem">
            <button type="button" class="field-toggle" id="togglePwd" title="Show or hide password">
                <i class="bi bi-eye" id="pwdIcon"></i>
            </button>
        </div>
    </div>

    <div class="auth-row">
        <label class="custom-check">
            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            Remember me
        </label>
    </div>

    <button type="submit" class="btn-signin" id="signinBtn">
        <i class="bi bi-box-arrow-in-right"></i>
        <span id="signinLabel">Sign In</span>
    </button>
</form>

<div class="auth-divider">Demo Accounts</div>

<button class="demo-toggle" id="demoToggle" type="button">
    <i class="bi bi-chevron-down"></i>
    View test credentials
</button>

<div class="demo-box" id="demoBox">
    <div class="demo-row" onclick="fillDemo('superadmin@itam.com')">
        <div class="demo-role-dot" style="background:#6366f1"></div>
        <div class="demo-row-info">
            <div class="demo-row-role">Super Admin</div>
            <div class="demo-row-email">superadmin@itam.com</div>
        </div>
        <button class="demo-fill-btn" type="button">Use</button>
    </div>
    <div class="demo-row" onclick="fillDemo('admin@techcorp.com')">
        <div class="demo-role-dot" style="background:#2563eb"></div>
        <div class="demo-row-info">
            <div class="demo-row-role">Admin</div>
            <div class="demo-row-email">admin@techcorp.com</div>
        </div>
        <button class="demo-fill-btn" type="button">Use</button>
    </div>
    <div class="demo-row" onclick="fillDemo('staff@techcorp.com')">
        <div class="demo-role-dot" style="background:#f59e0b"></div>
        <div class="demo-row-info">
            <div class="demo-row-role">Employee</div>
            <div class="demo-row-email">staff@techcorp.com</div>
        </div>
        <button class="demo-fill-btn" type="button">Use</button>
    </div>
    <div class="demo-row" onclick="fillDemo('vendor@delltech.com')">
        <div class="demo-role-dot" style="background:#14b8a6"></div>
        <div class="demo-row-info">
            <div class="demo-row-role">Supplier</div>
            <div class="demo-row-email">vendor@delltech.com</div>
        </div>
        <button class="demo-fill-btn" type="button">Use</button>
    </div>
    <div class="demo-pass-note">
        <i class="bi bi-shield-lock me-1"></i>All demo passwords: <strong>password</strong>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('togglePwd').addEventListener('click', function () {
    var input = document.getElementById('password');
    var icon = document.getElementById('pwdIcon');
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

var demoToggle = document.getElementById('demoToggle');
var demoBox = document.getElementById('demoBox');
demoToggle.addEventListener('click', function () {
    var isOpen = demoBox.classList.toggle('show');
    demoToggle.classList.toggle('open', isOpen);
});

function fillDemo(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'password';
    demoBox.classList.remove('show');
    demoToggle.classList.remove('open');
}

document.getElementById('loginForm').addEventListener('submit', function () {
    var btn = document.getElementById('signinBtn');
    var label = document.getElementById('signinLabel');
    btn.classList.add('loading');
    btn.querySelector('i').className = 'bi bi-arrow-repeat spin';
    label.textContent = 'Signing in...';
});
</script>
@endpush
