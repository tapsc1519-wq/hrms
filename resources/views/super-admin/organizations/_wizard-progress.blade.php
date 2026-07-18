@php
    $currentStep = $currentStep ?? 1;
    $adminUser = isset($organization) ? $organization->users->firstWhere('role', 'admin') : null;
    $subscription = $opsBridgeSubscription ?? (isset($organization) ? $organization->productSubscriptions->first(fn ($item) => $item->product?->slug === 'opsbridge') : null);
    $steps = [
        [
            'number' => 1,
            'title' => 'Organization',
            'body' => 'Capture company identity and contact details.',
            'done' => isset($organization) && $organization->exists,
            'icon' => 'bi-building',
        ],
        [
            'number' => 2,
            'title' => 'Product',
            'body' => 'Map purchased product, domain and database.',
            'done' => (bool) $subscription,
            'icon' => 'bi-box-seam',
        ],
        [
            'number' => 3,
            'title' => 'Billing',
            'body' => 'Set trial, plan, partner and commission.',
            'done' => isset($organization) && filled($organization->billing_status),
            'icon' => 'bi-credit-card',
        ],
        [
            'number' => 4,
            'title' => 'First Admin',
            'body' => 'Create the customer admin login for handover.',
            'done' => (bool) $adminUser,
            'icon' => 'bi-person-check',
        ],
        [
            'number' => 5,
            'title' => 'Handover',
            'body' => 'Share credentials and let Admin continue setup.',
            'done' => isset($organization) && (bool) $organization->onboarding_credentials_shared_at,
            'icon' => 'bi-flag',
        ],
    ];
    $doneCount = collect($steps)->where('done', true)->count();
    $nextStep = collect($steps)->firstWhere('done', false);
@endphp

<style>
    .platform-wizard {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 18px rgba(15,23,42,.06);
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .platform-wizard-head {
        align-items: center;
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 58%, #0f766e 100%);
        color: #fff;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1rem 1.15rem;
    }
    .platform-wizard-title {
        font-size: .98rem;
        font-weight: 800;
        line-height: 1.25;
    }
    .platform-wizard-subtitle {
        font-size: .76rem;
        line-height: 1.35;
        opacity: .82;
    }
    .platform-wizard-count {
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.22);
        border-radius: 999px;
        flex-shrink: 0;
        font-size: .74rem;
        font-weight: 800;
        padding: .38rem .65rem;
    }
    .platform-wizard-body {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }
    .platform-wizard-step {
        border-right: 1px solid #eef2f7;
        padding: .95rem;
    }
    .platform-wizard-step:last-child { border-right: 0; }
    .platform-wizard-step.active { background: #eff6ff; }
    .platform-wizard-step.done { background: #f0fdf4; }
    .platform-wizard-icon {
        align-items: center;
        background: #f1f5f9;
        border-radius: 8px;
        color: #475569;
        display: inline-flex;
        height: 34px;
        justify-content: center;
        margin-bottom: .55rem;
        width: 34px;
    }
    .platform-wizard-step.done .platform-wizard-icon {
        background: #dcfce7;
        color: #15803d;
    }
    .platform-wizard-step.active .platform-wizard-icon {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .platform-wizard-step-title {
        color: #0f172a;
        font-size: .79rem;
        font-weight: 800;
        line-height: 1.25;
    }
    .platform-wizard-step-body {
        color: #64748b;
        font-size: .7rem;
        line-height: 1.35;
        margin-top: .25rem;
    }
    @media (max-width: 991.98px) {
        .platform-wizard-body { grid-template-columns: 1fr; }
        .platform-wizard-step { border-right: 0; border-bottom: 1px solid #eef2f7; }
    }
</style>

<div class="platform-wizard" data-tour="organization-onboarding-wizard">
    <div class="platform-wizard-head">
        <div>
            <div class="platform-wizard-title">Organization Onboarding Wizard</div>
            <div class="platform-wizard-subtitle">
                @if($nextStep)
                    Next step: {{ $nextStep['title'] }}. {{ $nextStep['body'] }}
                @else
                    This organization is ready for customer handover.
                @endif
            </div>
        </div>
        <div class="platform-wizard-count">{{ $doneCount }}/{{ count($steps) }} complete</div>
    </div>
    <div class="platform-wizard-body">
        @foreach($steps as $step)
            <div class="platform-wizard-step {{ $step['done'] ? 'done' : '' }} {{ $currentStep === $step['number'] ? 'active' : '' }}">
                <div class="platform-wizard-icon">
                    <i class="bi {{ $step['done'] ? 'bi-check2' : $step['icon'] }}"></i>
                </div>
                <div class="platform-wizard-step-title">{{ $step['number'] }}. {{ $step['title'] }}</div>
                <div class="platform-wizard-step-body">{{ $step['body'] }}</div>
            </div>
        @endforeach
    </div>
</div>
