@extends('layouts.app')
@section('title', 'SAM Overview')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4>SAM Overview</h4>
        <p>Operate software discovery, normalization, compliance, renewals, and endpoint coverage from one place.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.software-normalization.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-diagram-3 me-1"></i>Normalize
        </a>
        <a href="{{ route('admin.software-compliance.index') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-shield-check me-1"></i>Compliance
        </a>
        @if(auth()->user()->hasPermission('endpoint.view'))
        <a href="{{ route('admin.agent-sources.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pc-display-horizontal me-1"></i>Endpoints
        </a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Inventory Coverage', $coverage['healthy_percent'].'%', 'grad-blue', $stats['healthy_devices'].' healthy of '.$stats['devices'].' enrolled devices'],
        ['Normalization', $coverage['normalized_percent'].'%', 'grad-green', $stats['mapped_records'].' mapped of '.$stats['installed_records'].' installed records'],
        ['Unknown Software', $stats['unknown_records'], 'grad-orange', 'Records waiting for review'],
        ['Open SAM Actions', $stats['open_actions'], 'grad-red', 'Compliance tasks still open'],
    ] as [$label, $value, $color, $sub])
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient {{ $color }}"><div class="card-body">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-number">{{ $value }}</div>
            <div class="stat-sub">{{ $sub }}</div>
        </div></div>
    </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Device Coverage</span>
                <a href="{{ route('admin.agent-sources.index') }}" class="btn btn-sm btn-outline-secondary">Open</a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Healthy</span><strong>{{ $stats['healthy_devices'] }}</strong></div>
                <div class="progress mb-3" style="height:8px"><div class="progress-bar bg-success" style="width: {{ $coverage['healthy_percent'] }}%"></div></div>
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Stale</span><span>{{ $stats['stale_devices'] }}</span></div>
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Offline</span><span>{{ $stats['offline_devices'] }}</span></div>
                <div class="d-flex justify-content-between text-muted small"><span>Total enrolled</span><span>{{ $stats['devices'] }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">License Signals</span>
                <a href="{{ route('admin.software-licenses.index') }}" class="btn btn-sm btn-outline-secondary">Licenses</a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Active licenses</span><strong>{{ $stats['active_licenses'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Expiring in 30 days</span><span>{{ $stats['expiring_licenses'] }}</span></div>
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Expired active licenses</span><span>{{ $stats['expired_licenses'] }}</span></div>
                <div class="d-flex justify-content-between text-muted small"><span>Catalog products</span><span>{{ $stats['catalog_items'] }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Next Actions</span>
                <a href="{{ route('admin.software-compliance.index') }}" class="btn btn-sm btn-outline-secondary">Review</a>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start gap-2 mb-3">
                    <span class="badge bg-primary rounded-pill mt-1">1</span>
                    <div><div class="fw-bold">Refresh inventory</div><div class="text-muted small">Confirm healthy devices before compliance review.</div></div>
                </div>
                <div class="d-flex align-items-start gap-2 mb-3">
                    <span class="badge bg-primary rounded-pill mt-1">2</span>
                    <div><div class="fw-bold">Normalize unknowns</div><div class="text-muted small">Map high-volume groups to catalog records.</div></div>
                </div>
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-primary rounded-pill mt-1">3</span>
                    <div><div class="fw-bold">Resolve risk</div><div class="text-muted small">Allocate, purchase, approve exception, or uninstall.</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Normalization Backlog</span>
                <a href="{{ route('admin.software-normalization.index') }}" class="btn btn-sm btn-outline-primary">Workbench</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">Discovered Software</th><th>Impact</th><th class="text-end pe-4">Last Seen</th></tr></thead>
                    <tbody>
                        @forelse($normalizationGroups as $group)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $group->raw_name }}</div><div class="text-muted small">{{ $group->raw_publisher ?: 'Unknown publisher' }}</div></td>
                            <td><span class="badge bg-warning text-dark">{{ $group->installation_count }} installs</span><div class="text-muted small">{{ $group->device_count }} devices, {{ $group->user_count }} users</div></td>
                            <td class="text-end pe-4">{{ $group->latest_seen_at ? \Carbon\Carbon::parse($group->latest_seen_at)->diffForHumans() : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No unknown software waiting for normalization.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Highest Compliance Risk</span>
                <a href="{{ route('admin.software-compliance.index') }}" class="btn btn-sm btn-outline-primary">Compliance</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">Software</th><th>Gap</th><th>Risk</th><th class="text-end pe-4">Exposure</th></tr></thead>
                    <tbody>
                        @forelse($riskRows as $row)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $row['software']->name }}</div><div class="text-muted small">{{ $row['software']->vendor ?: 'Unknown vendor' }}</div></td>
                            <td><span class="badge bg-light text-dark">{{ ucfirst($row['status']) }}</span><div class="text-muted small">{{ $row['required_seats'] }} required, {{ $row['purchased_seats'] }} purchased</div></td>
                            <td><span class="badge bg-{{ $row['risk_badge'] }}">{{ $row['risk_score'] }}/100</span></td>
                            <td class="text-end pe-4">Rs {{ number_format($row['financial_exposure'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No mapped compliance risk detected yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Upcoming Renewals</span>
                <a href="{{ route('admin.software-licenses.renewals') }}" class="btn btn-sm btn-outline-primary">Renewals</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">License</th><th>Seats</th><th class="text-end pe-4">Expiry</th></tr></thead>
                    <tbody>
                        @forelse($renewals as $license)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $license->software?->name ?? 'Unknown software' }}</div><div class="text-muted small">{{ $license->purchase_batch ?: $license->license_type_label }}</div></td>
                            <td>{{ $license->seats }}</td>
                            <td class="text-end pe-4"><span class="badge bg-{{ $license->is_expired ? 'danger' : 'warning' }}">{{ $license->expiry_date?->format('d-m-Y') }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No licenses expiring in the next 60 days.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Open Remediation</span>
                <a href="{{ route('admin.software-compliance.index') }}" class="btn btn-sm btn-outline-primary">Resolve</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">Action</th><th>Owner</th><th class="text-end pe-4">Due</th></tr></thead>
                    <tbody>
                        @forelse($openActions as $action)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $action->action_type_label }}</div><div class="text-muted small">{{ $action->software?->name ?? 'Unknown software' }}</div></td>
                            <td>{{ $action->owner?->name ?? 'Unassigned' }}</td>
                            <td class="text-end pe-4">{{ $action->due_date?->format('d-m-Y') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No open SAM remediation actions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
