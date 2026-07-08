@extends('layouts.app')

@section('title', 'Edit Product Subscription')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Product Subscription</h4>
        <p>Update product access, billing status, database mapping and renewal dates.</p>
    </div>
    <a href="{{ route('super-admin.product-subscriptions.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Subscriptions
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="table-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width:46px;height:46px;background:linear-gradient(135deg,#2563eb,#14b8a6)">
                        <i class="bi {{ $productSubscription->product?->icon ?? 'bi-grid-3x3-gap-fill' }}"></i>
                    </div>
                    <div>
                        <div class="fw-bold">{{ $productSubscription->product?->name ?? 'Product #' . $productSubscription->product_id }}</div>
                        <div class="text-muted small">{{ $productSubscription->product?->domain ?? $productSubscription->product_domain ?? '-' }}</div>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Organization</div>
                    @if($productSubscription->organization)
                        <a href="{{ route('super-admin.organizations.show', $productSubscription->organization) }}" class="fw-semibold text-decoration-none">{{ $productSubscription->organization->name }}</a>
                        <div class="text-muted small">{{ $productSubscription->organization->email ?? '-' }}</div>
                        <div class="text-muted small">{{ $productSubscription->organization->slug }}</div>
                    @else
                        <div class="fw-semibold">Organization #{{ $productSubscription->organization_id }}</div>
                        <div class="text-muted small">This record is mapped by ID for future database separation.</div>
                    @endif
                </div>

                <div class="border-top pt-3 mt-3">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Current State</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-{{ $productSubscription->status_badge }}">{{ $statuses[$productSubscription->status] ?? ucfirst($productSubscription->status) }}</span>
                        <span class="badge bg-light text-dark">{{ ucfirst($productSubscription->billing_cycle) }}</span>
                        <span class="badge bg-light text-dark">&#8377;{{ number_format((float) $productSubscription->monthly_amount, 2) }}/mo</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form method="POST" action="{{ route('super-admin.product-subscriptions.update', $productSubscription) }}" class="table-card">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $productSubscription->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Plan Name</label>
                        <input type="text" name="plan_name" value="{{ old('plan_name', $productSubscription->plan_name) }}" class="form-control @error('plan_name') is-invalid @enderror" placeholder="OpsBridge">
                        @error('plan_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Billing Cycle</label>
                        <select name="billing_cycle" class="form-select @error('billing_cycle') is-invalid @enderror" required>
                            @foreach($billingCycles as $value => $label)
                                <option value="{{ $value }}" {{ old('billing_cycle', $productSubscription->billing_cycle) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('billing_cycle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Monthly Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">&#8377;</span>
                            <input type="number" name="monthly_amount" step="0.01" min="0" value="{{ old('monthly_amount', $productSubscription->monthly_amount) }}" class="form-control @error('monthly_amount') is-invalid @enderror" required>
                            @error('monthly_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Product Domain</label>
                        <input type="text" name="product_domain" value="{{ old('product_domain', $productSubscription->product_domain) }}" class="form-control @error('product_domain') is-invalid @enderror" placeholder="opsbridge.niyantron.com">
                        @error('product_domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Product Database</label>
                        <input type="text" name="product_database" value="{{ old('product_database', $productSubscription->product_database) }}" class="form-control @error('product_database') is-invalid @enderror" placeholder="niyantron_opsbridge">
                        @error('product_database')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Trial Started</label>
                        <input type="date" name="trial_started_at" value="{{ old('trial_started_at', $productSubscription->trial_started_at?->toDateString()) }}" class="form-control @error('trial_started_at') is-invalid @enderror">
                        @error('trial_started_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Trial Ends</label>
                        <input type="date" name="trial_ends_at" value="{{ old('trial_ends_at', $productSubscription->trial_ends_at?->toDateString()) }}" class="form-control @error('trial_ends_at') is-invalid @enderror">
                        @error('trial_ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Payment</label>
                        <input type="date" name="last_payment_at" value="{{ old('last_payment_at', $productSubscription->last_payment_at?->toDateString()) }}" class="form-control @error('last_payment_at') is-invalid @enderror">
                        @error('last_payment_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subscription Started</label>
                        <input type="date" name="subscription_started_at" value="{{ old('subscription_started_at', $productSubscription->subscription_started_at?->toDateString()) }}" class="form-control @error('subscription_started_at') is-invalid @enderror">
                        @error('subscription_started_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subscription Ends</label>
                        <input type="date" name="subscription_ends_at" value="{{ old('subscription_ends_at', $productSubscription->subscription_ends_at?->toDateString()) }}" class="form-control @error('subscription_ends_at') is-invalid @enderror">
                        @error('subscription_ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Internal platform notes for this subscription">{{ old('notes', $productSubscription->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white text-end">
                <button class="btn btn-primary btn-sm">
                    <i class="bi bi-check2 me-1"></i>Save Subscription
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
