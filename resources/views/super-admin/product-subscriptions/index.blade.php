@extends('layouts.app')

@section('title', 'Product Subscriptions')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-ui-checks-grid me-2 text-primary"></i>Product Subscriptions</h4>
        <p>Control which Niyantron product each organization can use, including status, billing and product database mapping.</p>
    </div>
    <a href="{{ route('super-admin.products.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-grid-3x3-gap-fill me-1"></i>Products
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Subscriptions</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#0f172a">{{ number_format($totalSubscriptions) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Active</div>
            <div class="fw-bold mt-1 text-success" style="font-size:1.35rem">{{ number_format($activeSubscriptions) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Trial</div>
            <div class="fw-bold mt-1 text-primary" style="font-size:1.35rem">{{ number_format($trialSubscriptions) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Monthly Value</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#0f172a">&#8377;{{ number_format($monthlyValue, 2) }}</div>
        </div>
    </div>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search Organization</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, email or slug">
            </div>
            <div class="col-md-3">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ (int) request('product_id') === $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                @if(request()->hasAny(['search', 'product_id', 'status']))
                    <a href="{{ route('super-admin.product-subscriptions.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Organization</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Plan</th>
                    <th>Billing</th>
                    <th>Domain</th>
                    <th>Database</th>
                    <th>Dates</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $subscription)
                    <tr>
                        <td>
                            @if($subscription->organization)
                                <a href="{{ route('super-admin.organizations.show', $subscription->organization) }}" class="text-decoration-none fw-semibold">{{ $subscription->organization->name }}</a>
                                <div class="text-muted small">{{ $subscription->organization->email ?? $subscription->organization->slug }}</div>
                            @else
                                <span class="fw-semibold">Organization #{{ $subscription->organization_id }}</span>
                                <div class="text-muted small">Record not found in current product DB</div>
                            @endif
                        </td>
                        <td><span class="fw-semibold">{{ $subscription->product?->name ?? 'Product #' . $subscription->product_id }}</span></td>
                        <td><span class="badge bg-{{ $subscription->status_badge }}">{{ $statuses[$subscription->status] ?? ucfirst($subscription->status) }}</span></td>
                        <td>{{ $subscription->plan_name ?: '-' }}</td>
                        <td>
                            <strong>&#8377;{{ number_format((float) $subscription->monthly_amount, 2) }}</strong>
                            <div class="text-muted small">{{ ucfirst($subscription->billing_cycle) }}</div>
                        </td>
                        <td><span class="text-muted small">{{ $subscription->product_domain ?: '-' }}</span></td>
                        <td><span class="badge bg-light text-dark">{{ $subscription->product_database ?: '-' }}</span></td>
                        <td>
                            <div class="text-muted small">Trial: {{ $subscription->trial_ends_at?->format('d-m-Y') ?? '-' }}</div>
                            <div class="text-muted small">Sub: {{ $subscription->subscription_ends_at?->format('d-m-Y') ?? '-' }}</div>
                        </td>
                        <td>
                            <a href="{{ route('super-admin.product-subscriptions.edit', $subscription) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-ui-checks-grid d-block mb-2" style="font-size:1.45rem"></i>
                            No product subscriptions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($subscriptions->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $subscriptions->links() }}</div>
    @endif
</div>
@endsection
