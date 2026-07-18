@extends('layouts.app')
@section('title', 'Niyantron Platform')

@push('styles')
<style>
    .platform-dashboard { font-size: .84rem; }
    .platform-dashboard .platform-header {
        align-items: flex-start;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        margin-bottom: 1.2rem;
    }
    .platform-dashboard .platform-title {
        color: #0f172a;
        font-size: 1.45rem;
        font-weight: 850;
        margin: 0;
    }
    .platform-dashboard .platform-subtitle {
        color: #64748b;
        font-size: .86rem;
        margin: .22rem 0 0;
    }
    .platform-dashboard .platform-kicker {
        color: #2563eb;
        font-size: .72rem;
        font-weight: 850;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .platform-dashboard .metric-tile,
    .platform-dashboard .platform-panel,
    .platform-dashboard .action-tile,
    .platform-dashboard .product-tile {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
    }
    .platform-dashboard .metric-tile {
        min-height: 122px;
        padding: 1rem;
    }
    .platform-dashboard .metric-label {
        color: #64748b;
        font-size: .7rem;
        font-weight: 850;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .platform-dashboard .metric-value {
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 850;
        line-height: 1.1;
        margin-top: .45rem;
    }
    .platform-dashboard .metric-note,
    .platform-dashboard .muted-note {
        color: #64748b;
        font-size: .74rem;
        line-height: 1.35;
    }
    .platform-dashboard .metric-icon {
        align-items: center;
        border-radius: 11px;
        display: inline-flex;
        height: 38px;
        justify-content: center;
        width: 38px;
    }
    .platform-dashboard .panel-header {
        align-items: center;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        padding: .95rem 1rem;
    }
    .platform-dashboard .panel-title {
        color: #0f172a;
        font-size: .9rem;
        font-weight: 850;
        margin: 0;
    }
    .platform-dashboard .panel-body { padding: 1rem; }
    .platform-dashboard .action-tile {
        color: #0f172a;
        display: block;
        min-height: 92px;
        padding: .9rem;
        text-decoration: none;
        transition: border-color .16s, transform .16s, box-shadow .16s;
    }
    .platform-dashboard .action-tile:hover {
        border-color: #bfdbfe;
        box-shadow: 0 12px 28px rgba(37, 99, 235, .12);
        transform: translateY(-2px);
    }
    .platform-dashboard .action-icon {
        align-items: center;
        background: #eff6ff;
        border-radius: 10px;
        color: #2563eb;
        display: inline-flex;
        height: 34px;
        justify-content: center;
        margin-bottom: .55rem;
        width: 34px;
    }
    .platform-dashboard .action-title,
    .platform-dashboard .product-name {
        color: #0f172a;
        font-size: .84rem;
        font-weight: 850;
    }
    .platform-dashboard .product-tile {
        padding: 1rem;
    }
    .platform-dashboard .product-icon {
        align-items: center;
        border-radius: 12px;
        color: #fff;
        display: inline-flex;
        height: 42px;
        justify-content: center;
        width: 42px;
    }
    .platform-dashboard .status-row {
        align-items: center;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        padding: .7rem 0;
    }
    .platform-dashboard .status-row:last-child { border-bottom: 0; }
    .platform-dashboard .check-row {
        align-items: flex-start;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        gap: .75rem;
        padding: .75rem 0;
    }
    .platform-dashboard .check-row:last-child { border-bottom: 0; }
    .platform-dashboard .check-icon {
        align-items: center;
        border-radius: 10px;
        display: inline-flex;
        flex-shrink: 0;
        height: 34px;
        justify-content: center;
        width: 34px;
    }
    .platform-dashboard .health-strip {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
        padding: .85rem 1rem;
    }
    .platform-dashboard .table { font-size: .82rem; }
    .platform-dashboard .table thead th {
        color: #64748b;
        font-size: .7rem;
        font-weight: 850;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    @media (max-width: 767.98px) {
        .platform-dashboard .platform-header { display: block; }
        .platform-dashboard .platform-header .btn { margin-top: .75rem; }
    }
</style>
@endpush

@section('content')
@php
    $platformPercent = $platformProgress['total'] > 0 ? round(($platformProgress['complete'] / $platformProgress['total']) * 100) : 100;
@endphp
<div class="platform-dashboard">
    <div class="platform-header">
        <div>
            <div class="platform-kicker">Niyantron Control Center</div>
            <h4 class="platform-title">Platform Dashboard</h4>
            <p class="platform-subtitle">Manage products, organizations, partner referrals, subscriptions and commissions from one place.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('super-admin.organizations.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-building-add me-1"></i>Add Organization
            </a>
            <a href="{{ route('super-admin.partners.create') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i>Add Partner
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="metric-tile h-100">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="metric-label">Monthly Value</div>
                        <div class="metric-value">&#8377;{{ number_format((float) $stats['monthly_value'], 0) }}</div>
                        <div class="metric-note mt-2">Annual run-rate &#8377;{{ number_format((float) $stats['annualized_value'], 0) }}</div>
                    </div>
                    <span class="metric-icon bg-success-subtle text-success"><i class="bi bi-currency-rupee"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-tile h-100">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="metric-label">Subscriptions</div>
                        <div class="metric-value">{{ number_format($stats['subscriptions']) }}</div>
                        <div class="metric-note mt-2">{{ $stats['active_subscriptions'] }} active, {{ $stats['trial_subscriptions'] }} trial</div>
                    </div>
                    <span class="metric-icon bg-primary-subtle text-primary"><i class="bi bi-ui-checks-grid"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-tile h-100">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="metric-label">Partners</div>
                        <div class="metric-value">{{ number_format($stats['active_partners']) }}</div>
                        <div class="metric-note mt-2">{{ $stats['partners'] }} total partner records</div>
                    </div>
                    <span class="metric-icon bg-info-subtle text-info"><i class="bi bi-person-workspace"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-tile h-100">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="metric-label">Pending Commission</div>
                        <div class="metric-value">&#8377;{{ number_format((float) $stats['pending_commission_amount'], 0) }}</div>
                        <div class="metric-note mt-2">{{ $stats['pending_commissions'] }} entries waiting</div>
                    </div>
                    <span class="metric-icon bg-warning-subtle text-warning"><i class="bi bi-cash-coin"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="health-strip mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-lg-4">
                <div class="platform-kicker">Platform Readiness</div>
                <div class="fw-bold" style="color:#0f172a">{{ $platformProgress['complete'] }} of {{ $platformProgress['total'] }} foundation steps complete</div>
            </div>
            <div class="col-lg-4">
                <div class="progress" style="height:9px">
                    <div class="progress-bar" style="width:{{ $platformPercent }}%"></div>
                </div>
                <div class="muted-note mt-1">{{ $platformPercent }}% ready for multi-product operations</div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex justify-content-lg-end flex-wrap gap-2">
                    <span class="badge bg-primary-subtle text-primary">{{ $organizationHealth['trial'] }} trial orgs</span>
                    <span class="badge bg-success-subtle text-success">{{ $organizationHealth['active'] }} active orgs</span>
                    <span class="badge bg-warning-subtle text-warning">{{ $organizationHealth['attention'] }} need attention</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="platform-panel h-100">
                <div class="panel-header">
                    <div>
                        <p class="panel-title">Product Launcher</p>
                        <div class="muted-note">Open product portals and review product-level subscription health.</div>
                    </div>
                    <a href="{{ route('super-admin.products.index') }}" class="btn btn-outline-primary btn-sm">Manage Products</a>
                </div>
                <div class="panel-body">
                    <div class="row g-3">
                        @forelse($products as $item)
                            @php $product = $item['product']; @endphp
                            <div class="col-md-6">
                                <div class="product-tile h-100">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div class="d-flex align-items-start gap-3 min-w-0">
                                            <span class="product-icon flex-shrink-0" style="background:{{ $product->color ?: '#2563eb' }}">
                                                <i class="bi {{ $product->icon ?: 'bi-grid-3x3-gap-fill' }}"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <div class="product-name">{{ $product->name }}</div>
                                                <div class="muted-note text-truncate">{{ $product->domain ?: 'Domain not configured' }}</div>
                                            </div>
                                        </div>
                                        <span class="badge bg-{{ $product->status_badge }}">{{ str($product->status)->headline() }}</span>
                                    </div>
                                    <div class="row g-2 mt-3">
                                        <div class="col-4">
                                            <div class="muted-note">Subs</div>
                                            <div class="fw-bold">{{ $item['subscriptions'] }}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="muted-note">Active</div>
                                            <div class="fw-bold text-success">{{ $item['active_subscriptions'] }}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="muted-note">MRR</div>
                                            <div class="fw-bold">&#8377;{{ number_format((float) $item['monthly_value'], 0) }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        @if($item['launch_url'])
                                            <a href="{{ $item['launch_url'] }}" class="btn btn-primary btn-sm" target="_blank" rel="noopener">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>Open
                                            </a>
                                        @endif
                                        <a href="{{ route('super-admin.product-subscriptions.index', ['product_id' => $product->id]) }}" class="btn btn-outline-primary btn-sm">
                                            Subscriptions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted py-4">No products configured yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="platform-panel h-100">
                <div class="panel-header">
                    <div>
                        <p class="panel-title">Quick Actions</p>
                        <div class="muted-note">Most common platform tasks.</div>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('super-admin.organizations.create') }}" class="action-tile h-100">
                                <span class="action-icon"><i class="bi bi-building-add"></i></span>
                                <div class="action-title">Add Organization</div>
                                <div class="muted-note mt-1">Create tenant and product subscription.</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('super-admin.product-subscriptions.index') }}" class="action-tile h-100">
                                <span class="action-icon"><i class="bi bi-ui-checks-grid"></i></span>
                                <div class="action-title">Subscriptions</div>
                                <div class="muted-note mt-1">Manage access, billing and product DB.</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('super-admin.partners.index') }}" class="action-tile h-100">
                                <span class="action-icon"><i class="bi bi-person-workspace"></i></span>
                                <div class="action-title">Partners</div>
                                <div class="muted-note mt-1">Onboard sales and referral partners.</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('super-admin.partner-commissions.index') }}" class="action-tile h-100">
                                <span class="action-icon"><i class="bi bi-cash-stack"></i></span>
                                <div class="action-title">Commissions</div>
                                <div class="muted-note mt-1">Approve and mark payouts paid.</div>
                            </a>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <div class="fw-bold mb-2" style="color:#0f172a;font-size:.84rem">Launch Checklist</div>
                        @foreach($platformChecklist as $item)
                            <div class="check-row">
                                <span class="check-icon {{ $item['complete'] ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' }}">
                                    <i class="bi {{ $item['complete'] ? 'bi-check-lg' : $item['icon'] }}"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold" style="color:#0f172a;font-size:.8rem">{{ $item['title'] }}</div>
                                    <div class="muted-note">{{ $item['description'] }}</div>
                                </div>
                                @if(! $item['complete'])
                                    <a href="{{ $item['route'] }}" class="btn btn-sm btn-outline-primary">{{ $item['action'] }}</a>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        @foreach($subscriptionBreakdown as $label => $count)
                            @php
                                $badge = match($label) {
                                    'Active' => 'success',
                                    'Overdue' => 'warning',
                                    'Suspended' => 'danger',
                                    'Cancelled' => 'secondary',
                                    default => 'primary',
                                };
                            @endphp
                            <div class="status-row">
                                <span class="fw-semibold">{{ $label }}</span>
                                <span class="badge bg-{{ $badge }}">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="platform-panel">
                <div class="panel-header">
                    <div>
                        <p class="panel-title">Recent Product Subscriptions</p>
                        <div class="muted-note">Latest organization-to-product mappings.</div>
                    </div>
                    <a href="{{ route('super-admin.product-subscriptions.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Organization</th>
                                <th>Product</th>
                                <th>Partner</th>
                                <th>Monthly</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSubscriptions as $subscription)
                                <tr>
                                    <td>
                                        @if($subscription->organization)
                                            <a href="{{ route('super-admin.organizations.show', $subscription->organization) }}" class="fw-semibold text-decoration-none">{{ $subscription->organization->name }}</a>
                                            <div class="muted-note">{{ $subscription->organization->email ?? $subscription->organization->slug }}</div>
                                        @else
                                            <span class="fw-semibold">Organization #{{ $subscription->organization_id }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $subscription->product?->name ?? 'Product #' . $subscription->product_id }}</td>
                                    <td>{{ $subscription->partner?->display_name ?? 'Direct' }}</td>
                                    <td>&#8377;{{ number_format((float) $subscription->monthly_amount, 0) }}</td>
                                    <td><span class="badge bg-{{ $subscription->status_badge }}">{{ str($subscription->status)->headline() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No subscriptions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="platform-panel mb-3">
                <div class="panel-header">
                    <div>
                        <p class="panel-title">Trials Ending Soon</p>
                        <div class="muted-note">Subscriptions ending within 7 days.</div>
                    </div>
                </div>
                <div class="panel-body p-0">
                    @forelse($trialsEndingSoon as $subscription)
                        <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 border-bottom">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $subscription->organization?->name ?? 'Organization #' . $subscription->organization_id }}</div>
                                <div class="muted-note">{{ $subscription->product?->name ?? 'Product' }} | &#8377;{{ number_format((float) $subscription->monthly_amount, 0) }}/mo</div>
                            </div>
                            <span class="badge bg-warning text-dark">{{ $subscription->trial_ends_at?->format('d-m-Y') }}</span>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No trials ending in the next 7 days.</div>
                    @endforelse
                </div>
            </div>

            <div class="platform-panel">
                <div class="panel-header">
                    <div>
                        <p class="panel-title">Recent Commissions</p>
                        <div class="muted-note">Partner payout pipeline.</div>
                    </div>
                    <a href="{{ route('super-admin.partner-commissions.index') }}" class="btn btn-outline-primary btn-sm">View</a>
                </div>
                <div class="panel-body p-0">
                    @forelse($recentCommissions as $commission)
                        <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 border-bottom">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $commission->partner?->display_name ?? 'Partner #' . $commission->partner_id }}</div>
                                <div class="muted-note">{{ $commission->product?->name ?? 'Product' }} | {{ $commission->organization?->name ?? 'Organization #' . $commission->organization_id }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">&#8377;{{ number_format((float) $commission->commission_amount, 0) }}</div>
                                <span class="badge bg-{{ $commission->status_badge }}">{{ str($commission->status)->headline() }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No commission entries yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
