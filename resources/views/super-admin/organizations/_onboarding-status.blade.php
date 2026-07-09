@php($canUpdateOnboarding = $canUpdateOnboarding ?? true)

<div class="form-card">
    <div class="form-card-header">
        <span class="icon-wrap icon-green"><i class="bi bi-list-check"></i></span>
        Onboarding Status
    </div>
    <div class="form-card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
                <div style="font-size:.84rem;font-weight:700;color:#1e293b">Customer Handover</div>
                <div class="text-muted" style="font-size:.74rem">Track the steps before the organization starts using OpsBridge.</div>
            </div>
            <span class="badge bg-primary" style="font-size:.72rem">{{ $onboardingChecklist['completed'] }}/{{ $onboardingChecklist['total'] }}</span>
        </div>

        <div class="progress mb-3" style="height:8px">
            <div class="progress-bar" style="width: {{ $onboardingChecklist['percent'] }}%"></div>
        </div>

        <div class="d-grid gap-2">
            @foreach($onboardingChecklist['items'] as $item)
                <div class="d-flex align-items-start gap-2 p-2 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0 {{ $item['done'] ? 'bg-success-subtle text-success' : 'bg-light text-muted' }}" style="width:28px;height:28px">
                        <i class="bi {{ $item['done'] ? 'bi-check2' : $item['icon'] }}"></i>
                    </span>
                    <div class="min-w-0">
                        <div style="font-size:.8rem;font-weight:800;color:#1e293b">{{ $item['label'] }}</div>
                        <div class="text-muted" style="font-size:.72rem;line-height:1.35">{{ $item['note'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($canUpdateOnboarding)
            <form method="POST" action="{{ route('super-admin.organizations.onboarding.update', $organization) }}" class="mt-3 pt-3 border-top">
                @csrf
                @method('PATCH')
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="credentials_shared" value="1" id="credentialsShared{{ $organization->id }}" @checked($organization->onboarding_credentials_shared_at)>
                    <label class="form-check-label" for="credentialsShared{{ $organization->id }}" style="font-size:.8rem;font-weight:700">Credentials shared manually</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="initial_setup_completed" value="1" id="initialSetup{{ $organization->id }}" @checked($organization->onboarding_initial_setup_completed_at)>
                    <label class="form-check-label" for="initialSetup{{ $organization->id }}" style="font-size:.8rem;font-weight:700">Initial setup completed</label>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-check2-circle me-1"></i>Update Onboarding Status
                </button>
            </form>
        @else
            <div class="mt-3 pt-3 border-top">
                <a href="{{ route('super-admin.organizations.show', $organization) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-list-check me-1"></i>Open Checklist
                </a>
            </div>
        @endif
    </div>
</div>
