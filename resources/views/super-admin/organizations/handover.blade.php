@extends('layouts.app')
@section('title', 'Handover Pack - ' . $organization->name)

@section('content')
<style>
    .handover-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #0f766e 100%);
        border-radius: 8px;
        color: #fff;
        margin-bottom: 1rem;
        padding: 1.2rem;
    }
    .handover-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 18px rgba(15,23,42,.06);
    }
    .handover-label {
        color: #64748b;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .handover-value {
        color: #0f172a;
        font-size: .9rem;
        font-weight: 750;
        word-break: break-word;
    }
    .handover-message {
        background: #0f172a;
        border: 0;
        border-radius: 8px;
        color: #e2e8f0;
        font-family: Consolas, Monaco, monospace;
        font-size: .82rem;
        min-height: 310px;
        padding: 1rem;
        resize: vertical;
        white-space: pre-wrap;
    }
    .handover-copy-feedback {
        color: #15803d;
        display: none;
        font-size: .78rem;
        font-weight: 700;
    }
</style>

<div class="page-header">
    <a href="{{ route('super-admin.organizations.edit', $organization) }}" class="back-link"><i class="bi bi-arrow-left"></i> Organization Onboarding</a>
    <h4>Customer Admin Handover Pack</h4>
    <p>Prepare the login details and first-step guidance for <strong>{{ $organization->name }}</strong>.</p>
</div>

@include('super-admin.organizations._wizard-progress', ['currentStep' => 5, 'opsBridgeSubscription' => $opsBridgeSubscription])

<div class="handover-hero" data-tour="handover-summary">
    <div class="row g-3 align-items-center">
        <div class="col-lg-8">
            <div class="small text-uppercase fw-bold opacity-75 mb-2">Ready-to-share handover</div>
            <h1 class="mb-2">OpsBridge access pack</h1>
            <p class="lead mb-0">Copy the customer message, add the temporary password manually, and mark credentials shared after sending.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <div class="badge bg-light text-dark mb-2">{{ $onboardingChecklist['completed'] }}/{{ $onboardingChecklist['total'] }} onboarding checks</div>
            <div>
                <span class="badge bg-{{ $organization->onboarding_credentials_shared_at ? 'success' : 'warning text-dark' }}">
                    {{ $organization->onboarding_credentials_shared_at ? 'Credentials Shared' : 'Credentials Pending' }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="handover-card p-3 mb-3" data-tour="handover-login-details">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded bg-primary-subtle text-primary" style="width:36px;height:36px">
                    <i class="bi bi-key"></i>
                </span>
                <div>
                    <h5 class="mb-0">Login Details</h5>
                    <div class="text-muted small">Use these details while sharing access.</div>
                </div>
            </div>

            <div class="d-grid gap-3">
                <div>
                    <div class="handover-label">Login URL</div>
                    <div class="handover-value"><a href="{{ $loginUrl }}" target="_blank" rel="noopener">{{ $loginUrl }}</a></div>
                </div>
                <div>
                    <div class="handover-label">First Admin</div>
                    <div class="handover-value">{{ $admin?->name ?? 'Not created yet' }}</div>
                    <div class="text-muted small">{{ $admin?->email ?? 'Create the first admin before customer handover.' }}</div>
                </div>
                <div>
                    <div class="handover-label">Temporary Password</div>
                    <input type="text" id="temporaryPasswordInput" class="form-control form-control-sm" placeholder="Enter manually before copying">
                    <div class="form-text">Password is not stored here. Enter it only when preparing the message.</div>
                </div>
            </div>

            @if(!$admin)
                <a href="{{ route('super-admin.users.create', ['organization_id' => $organization->id, 'role' => 'admin']) }}" class="btn btn-primary btn-sm w-100 mt-3">
                    <i class="bi bi-person-plus me-1"></i>Create First Admin
                </a>
            @endif
        </div>

        <div class="handover-card p-3 mb-3">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded bg-success-subtle text-success" style="width:36px;height:36px">
                    <i class="bi bi-box-seam"></i>
                </span>
                <div>
                    <h5 class="mb-0">Product & Billing</h5>
                    <div class="text-muted small">Customer subscription snapshot.</div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <div class="handover-label">Product</div>
                    <div class="handover-value">{{ $opsBridgeSubscription?->product?->name ?? 'OpsBridge' }}</div>
                </div>
                <div class="col-6">
                    <div class="handover-label">Status</div>
                    <div class="handover-value">{{ ucfirst($opsBridgeSubscription?->status ?? $organization->billing_status ?? 'trial') }}</div>
                </div>
                <div class="col-6">
                    <div class="handover-label">Billing Cycle</div>
                    <div class="handover-value">{{ ucfirst($opsBridgeSubscription?->billing_cycle ?? $organization->billing_cycle ?? 'monthly') }}</div>
                </div>
                <div class="col-6">
                    <div class="handover-label">Monthly Amount</div>
                    <div class="handover-value">&#8377;{{ number_format((float) ($opsBridgeSubscription?->monthly_amount ?? $organization->monthly_amount ?? 0), 2) }}</div>
                </div>
                <div class="col-12">
                    <div class="handover-label">Enabled Modules</div>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        @forelse($enabledModules as $module)
                            <span class="badge bg-primary-subtle text-primary-emphasis">{{ $module }}</span>
                        @empty
                            <span class="badge bg-warning text-dark">No modules enabled</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="handover-card p-3" data-tour="handover-status">
            <h5 class="mb-2">Handover Status</h5>
            <p class="text-muted small mb-3">After sharing the copied message with the customer, mark credentials as shared.</p>
            <form method="POST" action="{{ route('super-admin.organizations.onboarding.update', $organization) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="credentials_shared" value="1">
                @if($organization->onboarding_initial_setup_completed_at)
                    <input type="hidden" name="initial_setup_completed" value="1">
                @endif
                <button class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-check2-circle me-1"></i>Mark Credentials Shared
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="handover-card p-3" data-tour="handover-message">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h5 class="mb-0">Customer Message</h5>
                    <div class="text-muted small">Copy this for WhatsApp, email, or manual onboarding.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="handoverCopyFeedback" class="handover-copy-feedback">Copied</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="copyHandoverMessage">
                        <i class="bi bi-clipboard-check me-1"></i>Copy Message
                    </button>
                </div>
            </div>
            <textarea id="handoverMessage" class="form-control handover-message">{{ $handoverMessage }}</textarea>

            <div class="alert alert-info small mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                The temporary password placeholder will be replaced automatically from the field on the left before copying.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var copyButton = document.getElementById('copyHandoverMessage');
    var message = document.getElementById('handoverMessage');
    var password = document.getElementById('temporaryPasswordInput');
    var feedback = document.getElementById('handoverCopyFeedback');

    if (!copyButton || !message) return;

    copyButton.addEventListener('click', function () {
        var text = message.value;
        if (password && password.value.trim()) {
            text = text.replace('[enter temporary password here]', password.value.trim());
        }

        navigator.clipboard.writeText(text).then(function () {
            if (!feedback) return;
            feedback.style.display = 'inline';
            window.setTimeout(function () { feedback.style.display = 'none'; }, 1800);
        });
    });
})();
</script>
@endpush
