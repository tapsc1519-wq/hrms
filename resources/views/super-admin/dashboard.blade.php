@extends('layouts.app')
@section('title', 'Super Admin Dashboard')

@push('styles')
<style>
    .sa-dashboard { font-size: .84rem; }
    .sa-dashboard .section-title { color: #1e293b; font-size: .84rem; font-weight: 800; margin: 0; }
    .sa-dashboard .section-subtitle,
    .sa-dashboard .small-note { color: #64748b; font-size: .74rem; line-height: 1.35; }
    .sa-dashboard .compact-card {
        background: #fff;
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .07);
    }
    .sa-dashboard .compact-card .card-header { padding: .95rem 1rem; }
    .sa-dashboard .compact-card .card-body { padding: 1rem; }

    .sa-dashboard .metric-card {
        border: 0;
        border-radius: 15px;
        color: #fff;
        min-height: 136px;
        overflow: hidden;
        position: relative;
    }
    .sa-dashboard .metric-card .card-body { padding: 1.05rem; }
    .sa-dashboard .metric-card::after {
        background: rgba(255,255,255,.18);
        border-radius: 999px;
        content: "";
        height: 86px;
        position: absolute;
        right: -28px;
        top: -30px;
        width: 86px;
    }
    .sa-dashboard .metric-copy {
        max-width: calc(100% - 54px);
        position: relative;
        z-index: 1;
    }
    .sa-dashboard .metric-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .03em;
        opacity: .88;
        text-transform: uppercase;
    }
    .sa-dashboard .metric-value {
        font-size: 1.42rem;
        font-weight: 800;
        line-height: 1.18;
        margin-top: .55rem;
    }
    .sa-dashboard .metric-sub {
        font-size: .74rem;
        line-height: 1.35;
        margin-top: .42rem;
        opacity: .88;
    }
    .sa-dashboard .metric-icon {
        align-items: center;
        background: rgba(255,255,255,.2);
        border-radius: 12px;
        display: flex;
        flex: 0 0 42px;
        height: 42px;
        justify-content: center;
        position: relative;
        width: 42px;
        z-index: 1;
    }

    .sa-dashboard .table { font-size: .84rem; }
    .sa-dashboard .table thead th {
        color: #64748b;
        font-size: .71rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .sa-dashboard .module-pill {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        min-height: 88px;
        padding: .82rem;
    }
    .sa-dashboard .module-icon {
        align-items: center;
        border-radius: 10px;
        display: flex;
        flex: 0 0 34px;
        height: 34px;
        justify-content: center;
        width: 34px;
    }
    .sa-dashboard .module-name { color: #1e293b; font-size: .82rem; font-weight: 800; }
    .sa-dashboard .module-money { color: #0f172a; font-size: .8rem; font-weight: 800; white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="sa-dashboard">
    <div class="page-header d-flex justify-content-between align-items-start">
        <div>
            <h4>Super Admin Dashboard</h4>
            <p>Platform health, subscriptions, trials and module adoption - {{ now()->format('d-m-Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('super-admin.organizations.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-buildings me-1"></i>Organizations
            </a>
            <a href="{{ route('super-admin.organizations.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-building-add me-1"></i>Add Organization
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card metric-card grad-blue h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between gap-3">
                        <div class="metric-copy">
                            <div class="metric-label">Monthly Revenue</div>
                            <div class="metric-value">&#8377;{{ number_format($stats['monthly_revenue'], 0) }}</div>
                            <div class="metric-sub">Yearly run-rate &#8377;{{ number_format($stats['annualized_revenue'], 0) }}</div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-currency-rupee"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card metric-card grad-green h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between gap-3">
                        <div class="metric-copy">
                            <div class="metric-label">Organizations</div>
                            <div class="metric-value">{{ $stats['organizations'] }}</div>
                            <div class="metric-sub">{{ $stats['active_orgs'] }} active accounts</div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-building-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card metric-card grad-orange h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between gap-3">
                        <div class="metric-copy">
                            <div class="metric-label">Trial Accounts</div>
                            <div class="metric-value">{{ $stats['trial_orgs'] }}</div>
                            <div class="metric-sub">{{ $trialsEndingSoon->count() }} ending in 7 days</div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card metric-card grad-red h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between gap-3">
                        <div class="metric-copy">
                            <div class="metric-label">Needs Attention</div>
                            <div class="metric-value">{{ $stats['overdue_orgs'] + $stats['suspended_orgs'] }}</div>
                            <div class="metric-sub">{{ $stats['overdue_orgs'] }} overdue, {{ $stats['suspended_orgs'] }} suspended</div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-exclamation-triangle"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="compact-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <p class="section-title">Module Revenue & Adoption</p>
                        <div class="section-subtitle">Enabled organizations and monthly contribution by module.</div>
                    </div>
                    <span class="badge bg-primary" style="font-size:.72rem">{{ $moduleOverview->sum('enabled_count') }} enabled modules</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($moduleOverview as $module)
                            <div class="col-md-6">
                                <div class="module-pill">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="module-icon bg-light text-primary"><i class="bi {{ $module['icon'] }}"></i></div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex justify-content-between gap-2">
                                                <div class="module-name text-truncate">{{ $module['full_name'] }}</div>
                                                <div class="module-money">&#8377;{{ number_format($module['monthly_revenue'], 0) }}</div>
                                            </div>
                                            <div class="small-note mt-1">{{ $module['enabled_count'] }} orgs enabled | &#8377;{{ number_format($module['price'], 0) }}/mo base</div>
                                            <div class="progress mt-2" style="height:5px">
                                                @php $pct = $stats['organizations'] > 0 ? min(100, ($module['enabled_count'] / $stats['organizations']) * 100) : 0; @endphp
                                                <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="compact-card h-100">
                <div class="card-header bg-white">
                    <p class="section-title">Billing Status</p>
                    <div class="section-subtitle">Current organization subscription state.</div>
                </div>
                <div class="card-body">
                    @foreach($billingBreakdown as $label => $count)
                        @php
                            $badge = match($label) {
                                'Active' => 'success',
                                'Overdue' => 'warning',
                                'Suspended' => 'danger',
                                'Cancelled' => 'secondary',
                                default => 'primary',
                            };
                        @endphp
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                            <span class="fw-semibold" style="font-size:.82rem">{{ $label }}</span>
                            <span class="badge bg-{{ $badge }}" style="font-size:.72rem">{{ $count }}</span>
                        </div>
                    @endforeach
                    <div class="mt-3 small-note">
                        Use Organization Edit -> Manage Modules to change trial period, billing status and module access.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="compact-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <p class="section-title">Recent Organizations</p>
                        <div class="section-subtitle">Latest tenants with trial and billing amount.</div>
                    </div>
                    <a href="{{ route('super-admin.organizations.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Organization</th>
                                <th>Modules</th>
                                <th>Trial Ends</th>
                                <th>Monthly</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrgs as $org)
                                <tr>
                                    <td>
                                        <a href="{{ route('super-admin.organizations.edit', $org) }}" class="text-decoration-none fw-semibold text-dark">{{ $org->name }}</a>
                                        <div class="text-muted" style="font-size:.74rem">{{ $org->email ?? $org->slug }}</div>
                                    </td>
                                    <td><span class="badge bg-light text-dark">{{ $org->modules->where('is_enabled', true)->count() }}</span></td>
                                    <td>{{ $org->trial_ends_at?->format('d-m-Y') ?? '-' }}</td>
                                    <td>&#8377;{{ number_format((float) $org->monthly_amount, 0) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $org->billing_status_badge }}" style="font-size:.72rem">
                                            {{ ucfirst($org->billing_status ?? 'trial') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No organizations yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="compact-card h-100">
                <div class="card-header bg-white">
                    <p class="section-title">Trials Ending Soon</p>
                    <div class="section-subtitle">Organizations whose trial ends within 7 days.</div>
                </div>
                <div class="card-body p-0">
                    @forelse($trialsEndingSoon as $org)
                        <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 border-bottom">
                            <div class="min-w-0">
                                <a href="{{ route('super-admin.organizations.show', $org) }}" class="text-decoration-none fw-semibold text-dark">{{ $org->name }}</a>
                                <div class="small-note">Monthly bill &#8377;{{ number_format((float) $org->monthly_amount, 0) }}</div>
                            </div>
                            <span class="badge bg-warning text-dark" style="font-size:.72rem">{{ $org->trial_ends_at?->format('d-m-Y') }}</span>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4" style="font-size:.84rem">No trials ending in the next 7 days.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
