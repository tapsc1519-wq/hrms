@extends('layouts.app')
@section('title', 'Pricing Settings')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-currency-rupee me-2 text-primary"></i>Pricing Settings</h4>
        <p>Set the default monthly price for each module. These prices are used when plans are saved for an organization.</p>
    </div>
    <a href="{{ route('super-admin.dashboard') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.74rem;font-weight:700;text-transform:uppercase">Active Billing Orgs</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#1e293b">{{ number_format($activeOrganizations) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.74rem;font-weight:700;text-transform:uppercase">Current Monthly Revenue</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#1e293b">&#8377;{{ number_format((float) $currentMonthlyRevenue, 0) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.74rem;font-weight:700;text-transform:uppercase">Configured Modules</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#1e293b">{{ $modules->count() }}</div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('super-admin.pricing.update') }}">
    @csrf
    @method('PUT')

    <div class="form-card">
        <div class="form-card-header">
            <span class="icon-wrap icon-blue"><i class="bi bi-tags-fill"></i></span>
            Module Prices
        </div>
        <div class="form-card-body">
            <div class="row g-3">
                @foreach($modules as $module)
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100" style="border-color:#e2e8f0!important">
                            <div class="d-flex align-items-start gap-3">
                                <div class="icon-wrap icon-purple"><i class="bi {{ $module['icon'] }}"></i></div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <div style="font-size:.84rem;font-weight:800;color:#1e293b">{{ $module['name'] }}</div>
                                            <div class="text-muted" style="font-size:.74rem;line-height:1.35">{{ $module['description'] }}</div>
                                        </div>
                                        <span class="badge bg-light text-dark" style="font-size:.7rem">{{ $module['short_name'] }}</span>
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label">Monthly Price</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">&#8377;</span>
                                            <input type="number"
                                                   min="0"
                                                   step="0.01"
                                                   name="prices[{{ $module['key'] }}]"
                                                   value="{{ old('prices.' . $module['key'], $module['price']) }}"
                                                   class="form-control @error('prices.' . $module['key']) is-invalid @enderror"
                                                   required>
                                            @error('prices.' . $module['key'])
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-text">Original default: &#8377;{{ number_format($module['default_price'], 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="alert alert-light border mt-3 mb-0" style="font-size:.82rem">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="apply_to_existing" value="1" id="applyExisting">
                    <label class="form-check-label" for="applyExisting">
                        Apply these prices to all existing organizations and recalculate their monthly amount.
                    </label>
                </div>
                <div class="text-muted mt-1" style="font-size:.74rem">
                    Leave unchecked if you want existing customers to keep their current saved prices.
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions mt-2">
        <a href="{{ route('super-admin.dashboard') }}" class="btn-cancel">Cancel</a>
        <button type="submit" class="btn btn-primary btn-save">
            <i class="bi bi-check-lg"></i> Save Pricing
        </button>
    </div>
</form>
@endsection
