@extends('layouts.app')

@section('title', 'Organization SSO')

@section('content')
<div class="page-header">
    <h4>Organization SSO</h4>
    <p>Configure Microsoft or Google sign-in for your organization.</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="form-card">
    <div class="form-card-header">
        <span class="icon-wrap icon-blue"><i class="bi bi-link-45deg"></i></span>
        Shared Callback URL
    </div>
    <div class="form-card-body">
        <label class="form-label">Use this redirect/callback URL in Microsoft Entra or Google Console</label>
        <div class="input-group">
            <input type="text" class="form-control" value="{{ $callbackUrl }}" readonly>
            <button type="button" class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('{{ $callbackUrl }}')">
                <i class="bi bi-copy"></i> Copy
            </button>
        </div>
        <div class="form-text">Every organization can use this same callback URL. The application remembers the organization during the sign-in request.</div>
    </div>
</div>

<div class="row g-4">
    @foreach(['microsoft' => 'Microsoft', 'google' => 'Google'] as $provider => $label)
        @php
            $setting = $settings[$provider];
            $domains = implode("\n", $setting->allowed_domains ?? []);
        @endphp
        <div class="col-xl-6">
            <form method="POST" action="{{ route('admin.sso-settings.update') }}" class="form-card h-100">
                @csrf
                @method('PUT')
                <input type="hidden" name="provider" value="{{ $provider }}">

                <div class="form-card-header">
                    <span class="icon-wrap {{ $provider === 'microsoft' ? 'icon-blue' : 'icon-red' }}">
                        <i class="bi bi-{{ $provider === 'microsoft' ? 'microsoft' : 'google' }}"></i>
                    </span>
                    {{ $label }} SSO
                </div>
                <div class="form-card-body">
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="is_enabled" value="1" class="form-check-input" @checked(old('provider') === $provider ? old('is_enabled') : $setting->is_enabled)>
                            <span class="form-check-label">Enable {{ $label }} sign-in</span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Client ID</label>
                        <input type="text" name="client_id" class="form-control" value="{{ old('provider') === $provider ? old('client_id') : $setting->client_id }}" placeholder="{{ $label }} application client ID">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Client Secret</label>
                        <input type="password" name="client_secret" class="form-control" placeholder="{{ $setting->exists && filled($setting->client_secret) ? 'Leave blank to keep existing secret' : 'Paste client secret value' }}">
                        @if($setting->exists && filled($setting->client_secret))
                            <div class="form-text">A secret is saved. Enter a new value only when rotating it.</div>
                        @endif
                    </div>

                    @if($provider === 'microsoft')
                    <div class="mb-3">
                        <label class="form-label">Tenant</label>
                        <input type="text" name="tenant" class="form-control" value="{{ old('provider') === $provider ? old('tenant') : ($setting->tenant ?: 'common') }}" placeholder="common, tenant ID, or domain">
                        <div class="form-text">Use <strong>common</strong> for multi-tenant sign-in, or a tenant ID/domain to restrict this organization.</div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Allowed Email Domains</label>
                        <textarea name="allowed_domains" class="form-control" rows="3" placeholder="company.com&#10;company.co.in">{{ old('provider') === $provider ? old('allowed_domains') : $domains }}</textarea>
                        <div class="form-text">Optional. One domain per line or comma separated. Leave blank to allow any existing user in this organization.</div>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary btn-save">
                        <i class="bi bi-check2-circle"></i> Save {{ $label }}
                    </button>
                </div>
            </form>
        </div>
    @endforeach
</div>
@endsection
