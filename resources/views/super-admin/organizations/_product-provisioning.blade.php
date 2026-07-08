@php
    $subscription = $opsBridgeSubscription ?? null;
    $isProvisioned = (bool) old('provision_opsbridge', $subscription ? $subscription->status !== 'cancelled' : true);
    $selectedPartnerId = old('partner_id', $subscription?->partner_id);
    $selectedStatus = old('subscription_status', $subscription?->status ?? 'trial');
    $selectedBillingCycle = old('billing_cycle', $subscription?->billing_cycle ?? 'monthly');
    $trialMonths = old('trial_months', $organization->trial_months ?? 1);
    $monthlyAmount = old('monthly_amount', $subscription?->monthly_amount ?? $defaultMonthlyAmount ?? 0);
    $productDomain = old('product_domain', $subscription?->product_domain ?? $opsBridgeProduct?->domain ?? config('niyantron.products.opsbridge.domain'));
    $productDatabase = old('product_database', $subscription?->product_database ?? config('database.connections.' . config('database.product_connection', 'opsbridge') . '.database'));
@endphp

<div class="form-card">
    <div class="form-card-header d-flex align-items-center justify-content-between">
        <div>
            <span class="icon-wrap icon-purple"><i class="bi bi-box-seam"></i></span>
            Product Provisioning
        </div>
        <span class="badge bg-light text-dark">{{ $opsBridgeProduct?->name ?? 'OpsBridge' }}</span>
    </div>
    <div class="form-card-body">
        <div class="border rounded-3 p-3 mb-3" style="background:#f8fafc;border-color:#e2e8f0!important">
            <div class="form-check form-switch d-flex align-items-center gap-2 ps-0">
                <input type="hidden" name="provision_opsbridge" value="0">
                <input class="form-check-input ms-0" type="checkbox" role="switch" id="provisionOpsBridge" name="provision_opsbridge" value="1" {{ $isProvisioned ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="provisionOpsBridge">Provision OpsBridge for this organization</label>
            </div>
            <div class="text-muted mt-2" style="font-size:.78rem">
                This creates the platform subscription record and maps the organization to the OpsBridge product database and domain.
            </div>
        </div>

        <div id="opsBridgeProvisioningFields" class="{{ $isProvisioned ? '' : 'd-none' }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Subscription Status</label>
                    <select name="subscription_status" class="form-select @error('subscription_status') is-invalid @enderror">
                        @foreach($subscriptionStatuses as $value => $label)
                            <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('subscription_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Plan Name</label>
                    <input type="text" name="plan_name" value="{{ old('plan_name', $subscription?->plan_name ?? 'OpsBridge') }}" class="form-control @error('plan_name') is-invalid @enderror" placeholder="OpsBridge">
                    @error('plan_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Billing Cycle</label>
                    <select name="billing_cycle" class="form-select @error('billing_cycle') is-invalid @enderror">
                        @foreach($billingCycles as $value => $label)
                            <option value="{{ $value }}" {{ $selectedBillingCycle === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('billing_cycle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Trial Months</label>
                    <input type="number" name="trial_months" min="1" max="12" value="{{ $trialMonths }}" class="form-control @error('trial_months') is-invalid @enderror">
                    @error('trial_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Monthly Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">&#8377;</span>
                        <input type="number" name="monthly_amount" step="0.01" min="0" value="{{ $monthlyAmount }}" class="form-control @error('monthly_amount') is-invalid @enderror">
                        @error('monthly_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subscription Ends</label>
                    <input type="date" name="subscription_ends_at" value="{{ old('subscription_ends_at', $subscription?->subscription_ends_at?->toDateString()) }}" class="form-control @error('subscription_ends_at') is-invalid @enderror">
                    @error('subscription_ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Partner Referral</label>
                    <select name="partner_id" id="organizationPartnerSelect" class="form-select @error('partner_id') is-invalid @enderror">
                        <option value="">Direct / No Partner</option>
                        @foreach($partners as $partner)
                            <option value="{{ $partner->id }}"
                                    data-commission="{{ $partner->default_commission_percent }}"
                                    {{ (string) $selectedPartnerId === (string) $partner->id ? 'selected' : '' }}>
                                {{ $partner->display_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('partner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Commission</label>
                    <div class="input-group">
                        <input type="number" name="commission_percent" id="organizationCommissionPercent" step="0.01" min="0" max="100" value="{{ old('commission_percent', $subscription?->commission_percent) }}" class="form-control @error('commission_percent') is-invalid @enderror" placeholder="0.00">
                        <span class="input-group-text">%</span>
                        @error('commission_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Product Domain</label>
                    <input type="text" name="product_domain" value="{{ $productDomain }}" class="form-control @error('product_domain') is-invalid @enderror" placeholder="opsbridge.niyantron.com">
                    @error('product_domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Product Database</label>
                    <input type="text" name="product_database" value="{{ $productDatabase }}" class="form-control @error('product_database') is-invalid @enderror" placeholder="blogynqo_hrms">
                    @error('product_database')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Platform Notes</label>
                    <textarea name="subscription_notes" rows="3" class="form-control @error('subscription_notes') is-invalid @enderror" placeholder="Internal notes for subscription, sales or onboarding team">{{ old('subscription_notes', $subscription?->notes) }}</textarea>
                    @error('subscription_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.getElementById('provisionOpsBridge')?.addEventListener('change', function () {
    document.getElementById('opsBridgeProvisioningFields')?.classList.toggle('d-none', !this.checked);
});

document.getElementById('organizationPartnerSelect')?.addEventListener('change', function () {
    var selected = this.options[this.selectedIndex];
    var commissionInput = document.getElementById('organizationCommissionPercent');
    if (!commissionInput || commissionInput.value) return;
    commissionInput.value = selected.dataset.commission || '';
});
</script>
@endpush
@endonce
