@extends('layouts.auth')
@section('title', 'Sign In')

@php
    $siteTitle = \App\Models\Setting::get('site_title', 'ITAM Suite');
    $siteLogo = \App\Models\Setting::get('site_logo');
    $activeAuthTab = old('organization') ? 'sso' : 'password';
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
    <h2>Welcome back</h2>
    <p>Choose how you want to access your workspace.</p>
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

<div class="auth-tabs" role="tablist" aria-label="Sign in method">
    <button type="button" class="auth-tab {{ $activeAuthTab === 'password' ? 'active' : '' }}" data-auth-tab="password">
        <i class="bi bi-lock"></i> Password
    </button>
    <button type="button" class="auth-tab {{ $activeAuthTab === 'sso' ? 'active' : '' }}" data-auth-tab="sso">
        <i class="bi bi-shield-check"></i> SSO
    </button>
</div>

<div class="auth-pane {{ $activeAuthTab === 'password' ? 'active' : '' }}" id="passwordPane">
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
</div>

<div class="auth-pane {{ $activeAuthTab === 'sso' ? 'active' : '' }}" id="ssoPane">
    <form method="POST" action="{{ route('sso.redirect') }}" class="sso-form">
        @csrf
        <div class="field-group">
            <label class="field-label" for="organization">Organization Name or Domain</label>
            <div class="field-wrap">
                <i class="bi bi-building field-icon"></i>
                <input type="text"
                       id="organization"
                       name="organization"
                       value="{{ old('organization') }}"
                       class="field-input"
                       placeholder="TechCorp or techcorp.com"
                       autocomplete="organization"
                       required>
            </div>
        </div>

        <div class="sso-grid">
            <button type="submit"
                    name="provider"
                    value="google"
                    data-configured="{{ ($ssoProviders['google'] ?? false) ? '1' : '0' }}"
                    class="sso-btn google {{ (($ssoProviders['google'] ?? false) && filled(old('organization'))) ? '' : 'disabled' }}"
                    title="{{ ($ssoProviders['google'] ?? false) ? 'Continue with Google' : 'Google SSO is not configured for any organization yet' }}"
                    @disabled(!($ssoProviders['google'] ?? false) || !filled(old('organization')))>
                <i class="bi bi-google"></i>Google
            </button>

            <button type="submit"
                    name="provider"
                    value="microsoft"
                    data-configured="{{ ($ssoProviders['microsoft'] ?? false) ? '1' : '0' }}"
                    class="sso-btn microsoft {{ (($ssoProviders['microsoft'] ?? false) && filled(old('organization'))) ? '' : 'disabled' }}"
                    title="{{ ($ssoProviders['microsoft'] ?? false) ? 'Continue with Microsoft' : 'Microsoft SSO is not configured for any organization yet' }}"
                    @disabled(!($ssoProviders['microsoft'] ?? false) || !filled(old('organization')))>
                <i class="bi bi-microsoft"></i>Microsoft
            </button>
        </div>
    </form>

    @if(!($ssoProviders['google'] ?? false) || !($ssoProviders['microsoft'] ?? false))
    <div class="sso-note">SSO is available after your organization configures Microsoft or Google sign-in.</div>
    @endif
</div>

<div class="auth-switch-link">
    New organization? <a href="{{ route('register') }}">Start a free trial</a>
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

document.getElementById('loginForm').addEventListener('submit', function () {
    var btn = document.getElementById('signinBtn');
    var label = document.getElementById('signinLabel');
    btn.classList.add('loading');
    btn.querySelector('i').className = 'bi bi-arrow-repeat spin';
    label.textContent = 'Signing in...';
});

var organizationInput = document.getElementById('organization');
var ssoButtons = document.querySelectorAll('.sso-btn[data-configured]');
var authTabs = document.querySelectorAll('.auth-tab');
var passwordPane = document.getElementById('passwordPane');
var ssoPane = document.getElementById('ssoPane');

authTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
        var target = tab.dataset.authTab;
        authTabs.forEach(function (item) {
            item.classList.toggle('active', item === tab);
        });
        passwordPane.classList.toggle('active', target === 'password');
        ssoPane.classList.toggle('active', target === 'sso');

        if (target === 'sso') {
            organizationInput.focus();
        }
    });
});

function syncSsoButtons() {
    var hasOrganization = organizationInput.value.trim().length > 0;
    ssoButtons.forEach(function (button) {
        var enabled = button.dataset.configured === '1' && hasOrganization;
        button.disabled = !enabled;
        button.classList.toggle('disabled', !enabled);
    });
}

organizationInput.addEventListener('input', syncSsoButtons);
syncSsoButtons();
</script>
@endpush
