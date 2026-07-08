@extends('layouts.app')
@section('title', 'Mail Settings')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h4><i class="bi bi-envelope-check-fill me-2 text-primary"></i>Mail Settings</h4>
        <p>Check the platform email setup used for partner invitations, password resets, and system notifications.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

@if(in_array($mail['default_mailer'], ['log', 'array'], true))
    <div class="alert alert-warning d-flex align-items-start gap-2">
        <i class="bi bi-info-circle-fill mt-1"></i>
        <div>
            Current mailer is <strong>{{ $mail['default_mailer'] }}</strong>. Emails will not reach a real inbox until SMTP or another delivery mailer is configured in the server <code>.env</code> file.
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-blue"><i class="bi bi-sliders"></i></div>
                Active Mail Configuration
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">Default Mailer</div>
                        <div class="fw-bold">{{ $mail['default_mailer'] ?: 'Not configured' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">Active Transport</div>
                        <div class="fw-bold">{{ $mail['active_transport'] ?: 'Not configured' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">From Address</div>
                        <div class="fw-bold">{{ $mail['from_address'] ?: 'Not configured' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">From Name</div>
                        <div class="fw-bold">{{ $mail['from_name'] ?: 'Not configured' }}</div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">SMTP Host</div>
                        <div class="fw-bold">{{ $mail['smtp_host'] ?: 'Not configured' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">SMTP Port</div>
                        <div class="fw-bold">{{ $mail['smtp_port'] ?: 'Not configured' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">SMTP Scheme</div>
                        <div class="fw-bold">{{ $mail['smtp_scheme'] ?: 'Not configured' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">SMTP Username</div>
                        <div class="fw-bold">{{ $mail['smtp_username'] ?: 'Not configured' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">SMTP Password</div>
                        <div class="fw-bold">
                            @if($mail['smtp_password_set'])
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Configured</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Missing</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-semibold text-uppercase">Platform Login URL</div>
                        <div class="fw-bold text-break">{{ $mail['platform_login_url'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-teal"><i class="bi bi-send-check-fill"></i></div>
                Send Test Email
            </div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('super-admin.mail-settings.test') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Recipient Email <span class="req">*</span></label>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="name@example.com"
                               required>
                        <div class="form-text">Use this after updating SMTP on the live server.</div>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-save w-100">
                        <i class="bi bi-send"></i> Send Test Email
                    </button>
                </form>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-amber"><i class="bi bi-list-check"></i></div>
                Before Testing
            </div>
            <div class="form-card-body">
                <div class="d-flex gap-3 mb-3">
                    <i class="bi bi-check2-circle text-success"></i>
                    <div>Set <code>MAIL_MAILER=smtp</code> on the server.</div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <i class="bi bi-check2-circle text-success"></i>
                    <div>Confirm SMTP host, port, username, password, and from address are correct.</div>
                </div>
                <div class="d-flex gap-3">
                    <i class="bi bi-check2-circle text-success"></i>
                    <div>Run <code>php artisan config:clear</code> after changing the live <code>.env</code>.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
