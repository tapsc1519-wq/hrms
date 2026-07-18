@extends('layouts.app')

@section('title', $organization->name)

@push('styles')
<style>
    .org-module-page {
        font-size: .84rem;
    }

    .org-module-page .org-avatar {
        width: 42px;
        height: 42px;
        font-size: 1rem;
    }

    .org-module-page .org-status-badge,
    .org-module-page .org-count-badge,
    .org-module-page .module-dependency-badge {
        font-size: .72rem;
        font-weight: 700;
        line-height: 1;
        padding: .38rem .55rem;
    }

    .org-module-page .org-stat-card,
    .org-module-page .org-detail-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .07);
    }

    .org-module-page .org-stat-card {
        padding: .85rem;
    }

    .org-module-page .org-stat-value {
        font-size: 1.35rem;
        line-height: 1.1;
        font-weight: 800;
    }

    .org-module-page .org-stat-label,
    .org-module-page .module-description,
    .org-module-page .billing-help {
        font-size: .74rem;
        line-height: 1.35;
    }

    .org-module-page .org-detail-card .card-header {
        background: #fff;
        border-bottom-color: #e2e8f0;
        font-size: .84rem;
        font-weight: 700;
    }

    .org-module-page .org-detail-card .card-body,
    .org-module-page .org-detail-card .table {
        font-size: .84rem;
    }

    .org-module-page .org-detail-card .table thead th {
        color: #64748b;
        font-size: .71rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .org-module-page .module-toggle-card {
        border-color: #e2e8f0 !important;
        border-radius: 12px !important;
        color: #1e293b;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .org-module-page .module-toggle-card:hover {
        border-color: #93c5fd !important;
        box-shadow: 0 8px 20px rgba(37, 99, 235, .09);
        transform: translateY(-1px);
    }

    .org-module-page .module-title {
        font-size: .84rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .org-module-page .module-title i {
        color: #2563eb;
        font-size: .95rem;
    }

    .org-module-page .module-price {
        color: #0f172a;
        font-size: .78rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .org-module-page .billing-summary {
        background: linear-gradient(135deg, #eef6ff 0%, #f8fafc 100%);
        border: 1px solid #dbeafe;
        border-radius: 12px;
        padding: .85rem;
    }

    .org-module-page .billing-total {
        color: #1d4ed8;
        font-size: 1.22rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .org-module-page .billing-health-card {
        background: #fff;
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .07);
        overflow: hidden;
    }

    .org-module-page .billing-health-strip {
        height: 4px;
        background: linear-gradient(90deg, #2563eb, #14b8a6);
    }

    .org-module-page .billing-health-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    .org-module-page .billing-health-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: .75rem;
    }

    .org-module-page .billing-health-label {
        color: #64748b;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .org-module-page .billing-health-value {
        color: #0f172a;
        font-size: .9rem;
        font-weight: 800;
        line-height: 1.25;
        margin-top: .2rem;
    }
</style>
@endpush

@section('content')
<div class="org-module-page">
    <div class="mb-4">
        <a href="{{ route('super-admin.organizations.index') }}" class="back-link mb-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Organizations
        </a>
        <div class="d-flex align-items-center gap-3">
            @if($organization->logo)
                <img src="{{ asset('storage/' . $organization->logo) }}" class="rounded org-avatar" style="object-fit:cover;">
            @else
                <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0 org-avatar">
                    {{ strtoupper(substr($organization->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h4 class="page-title mb-0">{{ $organization->name }}</h4>
                <p class="page-subtitle mb-0">{{ $organization->slug }}</p>
            </div>
            <div class="ms-auto d-flex gap-2">
                <span class="badge org-status-badge bg-{{ $organization->status === 'active' ? 'success' : ($organization->status === 'suspended' ? 'danger' : 'secondary') }}">
                    {{ ucfirst($organization->status) }}
                </span>
                <a href="{{ route('super-admin.organizations.edit', $organization) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <a href="{{ route('super-admin.organizations.handover', $organization) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-send-check me-1"></i>Handover Pack
                </a>
            </div>
        </div>
    </div>

    @include('super-admin.organizations._wizard-progress', ['currentStep' => $organization->users->firstWhere('role', 'admin') ? 5 : 4])

    @php
        $enabledModules = $organization->modules->where('is_enabled', true)->pluck('module_key')->all();
        $monthlyAmount = (float) ($organization->monthly_amount ?? $organization->modules->where('is_enabled', true)->sum('monthly_price'));
        $annualAmount = $monthlyAmount * 12;
        $trialEndsAt = $organization->trial_ends_at;
        $trialDays = $organization->trialDaysRemaining();
        $subscriptionDays = $organization->subscriptionDaysRemaining();
        $hasBillingAccess = $organization->hasBillingAccess();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-sm-3">
            <div class="org-stat-card text-center">
                <div class="org-stat-value text-primary">{{ $organization->users->count() }}</div>
                <div class="text-muted org-stat-label">Users</div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="org-stat-card text-center">
                <div class="org-stat-value text-success">{{ $organization->assets->count() }}</div>
                <div class="text-muted org-stat-label">Assets</div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="org-stat-card text-center">
                <div class="org-stat-value text-info">{{ $organization->suppliers->count() }}</div>
                <div class="text-muted org-stat-label">Suppliers</div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="org-stat-card text-center">
                <div class="org-stat-value text-warning">{{ count($enabledModules) }}</div>
                <div class="text-muted org-stat-label">Enabled Modules</div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="org-stat-card text-center">
                <div class="org-stat-value text-primary">&#8377;{{ number_format($monthlyAmount, 0) }}</div>
                <div class="text-muted org-stat-label">Monthly Bill</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card org-detail-card mb-3">
                <div class="card-header">Contact & Details</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-5 text-muted">Email</div><div class="col-7">{{ $organization->email ?? '-' }}</div>
                        <div class="col-5 text-muted">Phone</div><div class="col-7">{{ $organization->phone ?? '-' }}</div>
                        <div class="col-5 text-muted">Address</div><div class="col-7">{{ $organization->address ?? '-' }}</div>
                        <div class="col-5 text-muted">City</div><div class="col-7">{{ $organization->city ?? '-' }}</div>
                        <div class="col-5 text-muted">Country</div><div class="col-7">{{ $organization->country ?? '-' }}</div>
                        <div class="col-5 text-muted">Website</div>
                        <div class="col-7">
                            @if($organization->website)
                                <a href="{{ $organization->website }}" target="_blank" rel="noopener">{{ $organization->website }}</a>
                            @else
                                -
                            @endif
                        </div>
                        <div class="col-5 text-muted">Tax Number</div><div class="col-7">{{ $organization->tax_number ?? '-' }}</div>
                        <div class="col-5 text-muted">Created</div><div class="col-7">{{ $organization->created_at->format('d-m-Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="billing-health-card mb-3">
                <div class="billing-health-strip"></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <div class="fw-bold" style="font-size:.9rem">Billing Health</div>
                            <div class="text-muted" style="font-size:.76rem">Subscription access, renewal dates and selected module value.</div>
                        </div>
                        <span class="badge bg-{{ $hasBillingAccess ? 'success' : 'danger' }} org-status-badge">
                            {{ $hasBillingAccess ? 'Access Allowed' : 'Access Blocked' }}
                        </span>
                    </div>

                    @if(!$hasBillingAccess)
                        <div class="alert alert-danger py-2 mb-3" style="font-size:.78rem">
                            {{ $organization->billingAccessMessage() }}
                        </div>
                    @endif

                    <div class="billing-health-grid">
                        <div class="billing-health-item">
                            <div class="billing-health-label">Billing Status</div>
                            <div class="billing-health-value">
                                <span class="badge bg-{{ $organization->billing_status_badge }}">{{ ucfirst($organization->billing_status ?? 'trial') }}</span>
                            </div>
                        </div>
                        <div class="billing-health-item">
                            <div class="billing-health-label">Billing Cycle</div>
                            <div class="billing-health-value">{{ ucfirst($organization->billing_cycle ?? 'monthly') }}</div>
                        </div>
                        <div class="billing-health-item">
                            <div class="billing-health-label">Monthly Value</div>
                            <div class="billing-health-value">&#8377;{{ number_format($monthlyAmount, 2) }}</div>
                        </div>
                        <div class="billing-health-item">
                            <div class="billing-health-label">Annual Value</div>
                            <div class="billing-health-value">&#8377;{{ number_format($annualAmount, 2) }}</div>
                        </div>
                        <div class="billing-health-item">
                            <div class="billing-health-label">Trial Ends</div>
                            <div class="billing-health-value">
                                {{ $organization->trial_ends_at?->format('d-m-Y') ?? '-' }}
                                @if(!is_null($trialDays))
                                    <div class="text-muted fw-normal" style="font-size:.72rem">{{ $trialDays >= 0 ? $trialDays . ' days left' : abs($trialDays) . ' days overdue' }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="billing-health-item">
                            <div class="billing-health-label">Subscription Ends</div>
                            <div class="billing-health-value">
                                {{ $organization->subscription_ends_at?->format('d-m-Y') ?? '-' }}
                                @if(!is_null($subscriptionDays))
                                    <div class="text-muted fw-normal" style="font-size:.72rem">{{ $subscriptionDays >= 0 ? $subscriptionDays . ' days left' : abs($subscriptionDays) . ' days overdue' }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('super-admin.organizations._onboarding-status', ['onboardingChecklist' => $onboardingChecklist])

            <div class="card org-detail-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Subscribed Products</span>
                    <a href="{{ route('super-admin.product-subscriptions.index', ['search' => $organization->name]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-ui-checks-grid me-1"></i>Manage
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Partner</th>
                                <th>Billing</th>
                                <th>Domain</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($organization->productSubscriptions as $subscription)
                                <tr>
                                    <td>
                                        <strong>{{ $subscription->product?->name ?? 'Product #' . $subscription->product_id }}</strong>
                                        <div class="text-muted" style="font-size:.72rem">{{ $subscription->plan_name ?: 'No plan name' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $subscription->status_badge }}">{{ ucfirst($subscription->status) }}</span>
                                    </td>
                                    <td>
                                        @if($subscription->partner)
                                            <span class="fw-semibold">{{ $subscription->partner->display_name }}</span>
                                            <div class="text-muted" style="font-size:.72rem">{{ number_format((float) ($subscription->commission_percent ?? $subscription->partner->default_commission_percent), 2) }}%</div>
                                        @else
                                            <span class="text-muted" style="font-size:.72rem">Direct</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>&#8377;{{ number_format((float) $subscription->monthly_amount, 2) }}</strong>
                                        <div class="text-muted" style="font-size:.72rem">{{ ucfirst($subscription->billing_cycle) }}</div>
                                    </td>
                                    <td>
                                        <span class="text-muted" style="font-size:.72rem">{{ $subscription->product_domain ?: '-' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('super-admin.product-subscriptions.edit', $subscription) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No product subscription mapped yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card org-detail-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Payments</span>
                    <span class="badge bg-{{ $organization->billing_status_badge }} org-count-badge">{{ ucfirst($organization->billing_status ?? 'trial') }}</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('super-admin.organizations.payments.store', $organization) }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">&#8377;</span>
                                <input type="number" name="amount" step="0.01" min="1" value="{{ old('amount', $monthlyAmount) }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Period Start</label>
                            <input type="date" name="period_start" value="{{ old('period_start', now()->toDateString()) }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Period End</label>
                            <input type="date" name="period_end" value="{{ old('period_end', now()->addMonth()->subDay()->toDateString()) }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Method</label>
                            <select name="payment_method" class="form-select form-select-sm" required>
                                @foreach(['bank_transfer' => 'Bank Transfer', 'upi' => 'UPI', 'cheque' => 'Cheque', 'cash' => 'Cash', 'card' => 'Card', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('payment_method', 'bank_transfer') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference No.</label>
                            <input type="text" name="reference_no" value="{{ old('reference_no') }}" class="form-control form-control-sm" placeholder="Txn / invoice no.">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Optional payment notes">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-primary btn-sm">
                                <i class="bi bi-receipt me-1"></i>Record Payment
                            </button>
                        </div>
                    </form>

                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold" style="font-size:.82rem">Recent Payments</span>
                            <span class="text-muted" style="font-size:.74rem">Valid until {{ $organization->subscription_ends_at?->format('d-m-Y') ?? '-' }}</span>
                        </div>
                        @forelse($organization->payments->sortByDesc('payment_date')->take(5) as $payment)
                            <div class="d-flex justify-content-between gap-2 py-2 border-bottom">
                                <div class="min-w-0">
                                    <div class="fw-semibold">&#8377;{{ number_format((float) $payment->amount, 2) }}</div>
                                    <div class="text-muted" style="font-size:.72rem">
                                        {{ $payment->period_start->format('d-m-Y') }} to {{ $payment->period_end->format('d-m-Y') }}
                                    </div>
                                    @if($payment->reference_no)
                                        <div class="text-muted" style="font-size:.72rem">Ref: {{ $payment->reference_no }}</div>
                                    @endif
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="badge bg-light text-dark" style="font-size:.68rem">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                    <div class="text-muted mt-1" style="font-size:.72rem">{{ $payment->payment_date->format('d-m-Y') }}</div>
                                    <a href="{{ route('super-admin.payments.show', $payment) }}" class="d-inline-block mt-1" style="font-size:.72rem">Receipt</a>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-3" style="font-size:.82rem">No payments recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card org-detail-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Users
                    <span class="badge bg-primary rounded-pill org-count-badge">{{ $organization->users->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Role</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($organization->users->take(10) as $u)
                            <tr>
                                <td><strong>{{ $u->name }}</strong><br><span class="text-muted">{{ $u->email }}</span></td>
                                <td><span class="badge bg-{{ $u->role === 'admin' ? 'primary' : ($u->role === 'supplier' ? 'info' : 'secondary') }}">{{ ucwords(str_replace('_', ' ', $u->role)) }}</span></td>
                                <td><span class="badge bg-{{ $u->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($u->status) }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No users yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <form method="POST" action="{{ route('super-admin.organizations.modules.update', $organization) }}" class="card org-detail-card">
                @csrf
                @method('PATCH')
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Module Access & Pricing</span>
                    <span class="badge bg-primary org-count-badge">{{ count($enabledModules) }} enabled</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small">
                        Select trial duration and enabled modules. After the trial ends, the organization should pay the calculated monthly amount to continue using the selected modules.
                    </div>

                    <div class="billing-summary mb-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Free Trial</label>
                                <select name="trial_months" class="form-select form-select-sm" id="trialMonths">
                                    @for($month = 1; $month <= 6; $month++)
                                        <option value="{{ $month }}" {{ (int) old('trial_months', $organization->trial_months ?? 1) === $month ? 'selected' : '' }}>
                                            {{ $month }} {{ $month === 1 ? 'Month' : 'Months' }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Billing Status</label>
                                <select name="billing_status" class="form-select form-select-sm">
                                    @foreach(['trial' => 'Trial', 'active' => 'Active', 'overdue' => 'Overdue', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('billing_status', $organization->billing_status ?? 'trial') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Billing Cycle</label>
                                <select name="billing_cycle" class="form-select form-select-sm">
                                    <option value="monthly" {{ old('billing_cycle', $organization->billing_cycle ?? 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="annual" {{ old('billing_cycle', $organization->billing_cycle ?? 'monthly') === 'annual' ? 'selected' : '' }}>Annual</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <div class="billing-help text-muted">
                                    Trial started: {{ $organization->trial_started_at?->format('d-m-Y') ?? 'Starts on first save' }}<br>
                                    Trial ends: <strong>{{ $trialEndsAt ? $trialEndsAt->format('d-m-Y') : 'Will be calculated' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <div class="billing-help text-muted">Monthly amount after trial</div>
                                <div class="billing-total" id="moduleTotal">&#8377;{{ number_format($monthlyAmount, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        @foreach($modules as $key => $module)
                            @php
                                $isEnabled = in_array($key, $enabledModules, true);
                                $dependsOn = $module['depends_on'] ?? [];
                                $savedModule = $organization->modules->firstWhere('module_key', $key);
                                $modulePrice = (float) ($savedModule?->monthly_price ?? $module['monthly_price'] ?? 0);
                            @endphp
                            <div class="col-md-6">
                                <label class="border p-3 h-100 d-flex gap-3 module-toggle-card" style="cursor:pointer">
                                    <input type="checkbox" class="form-check-input mt-1 module-checkbox"
                                           name="modules[]" value="{{ $key }}"
                                           data-module="{{ $key }}"
                                           data-depends="{{ implode(',', $dependsOn) }}"
                                           data-price="{{ $modulePrice }}"
                                           @checked($isEnabled)>
                                    <span class="flex-grow-1">
                                        <span class="d-flex align-items-start justify-content-between gap-2">
                                            <span class="d-flex align-items-center gap-2 module-title">
                                                <i class="bi {{ $module['icon'] }}"></i> {{ $module['name'] }}
                                            </span>
                                            <span class="module-price">&#8377;{{ number_format($modulePrice, 0) }}/mo</span>
                                        </span>
                                        <span class="d-block text-muted module-description mt-1">{{ $module['description'] }}</span>
                                        @if($dependsOn)
                                            <span class="badge bg-warning text-dark mt-2 module-dependency-badge">
                                                Requires {{ collect($dependsOn)->map(fn($dep) => $modules[$dep]['short_name'] ?? $dep)->implode(', ') }}
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer bg-white text-end">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Save Access & Pricing</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('change', function (event) {
    if (!event.target.classList.contains('module-checkbox')) return;

    if (event.target.checked) {
        const dependencies = (event.target.dataset.depends || '').split(',').filter(Boolean);
        dependencies.forEach(function (key) {
            const checkbox = document.querySelector('.module-checkbox[data-module="' + key + '"]');
            if (checkbox) checkbox.checked = true;
        });
    }

    updateModuleTotal();
});

function updateModuleTotal() {
    let total = 0;
    document.querySelectorAll('.module-checkbox:checked').forEach(function (checkbox) {
        total += Number(checkbox.dataset.price || 0);
    });

    const totalEl = document.getElementById('moduleTotal');
    if (totalEl) {
        totalEl.textContent = new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR'
        }).format(total);
    }
}

document.addEventListener('DOMContentLoaded', updateModuleTotal);
</script>
@endpush
