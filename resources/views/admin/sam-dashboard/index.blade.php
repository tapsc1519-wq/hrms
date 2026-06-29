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
        @if(auth()->user()->hasPermission('software.optimization.view'))
        <a href="{{ route('admin.software-optimization.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-graph-down-arrow me-1"></i>Optimize
        </a>
        @endif
        <a href="{{ route('admin.software-licenses.renewals', ['window' => 60, 'plan_status' => 'unplanned']) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar2-check me-1"></i>Renewals
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
        ['Open Demand', $stats['pending_requests'] + $stats['approved_requests'], 'grad-red', $stats['urgent_requests'].' urgent software requests'],
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
                <a href="{{ route('admin.software-licenses.renewals', ['window' => 60]) }}" class="btn btn-sm btn-outline-secondary">Renewals</a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Active licenses</span><strong>{{ $stats['active_licenses'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Expiring in 30 days</span><span>{{ $stats['expiring_licenses'] }}</span></div>
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Expired active licenses</span><span>{{ $stats['expired_licenses'] }}</span></div>
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Unplanned renewals</span><span>{{ $stats['unplanned_renewals'] }}</span></div>
                <div class="d-flex justify-content-between text-muted small"><span>Planned renewal spend</span><span>Rs {{ number_format((float) $stats['planned_renewal_spend'], 0) }}</span></div>
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
                <div class="d-flex align-items-start gap-2 mt-3">
                    <span class="badge bg-primary rounded-pill mt-1">4</span>
                    <div><div class="fw-bold">Reclaim waste</div><div class="text-muted small">Review inactive paid allocations before renewal.</div></div>
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
                    <thead><tr><th class="ps-4">License</th><th>Seats</th><th>Plan</th><th class="text-end pe-4">Expiry</th></tr></thead>
                    <tbody>
                        @forelse($renewals as $license)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $license->software?->name ?? 'Unknown software' }}</div><div class="text-muted small">{{ $license->purchase_batch ?: $license->license_type_label }}</div></td>
                            <td>{{ $license->seats }}</td>
                            <td>
                                @if($license->activeRenewalDecision)
                                    <span class="badge bg-{{ $license->activeRenewalDecision->decision_badge }}">{{ $license->activeRenewalDecision->decision_label }}</span>
                                    <div class="text-muted small">{{ $license->activeRenewalDecision->owner?->name ?? 'Unassigned' }}</div>
                                @else
                                    <a href="{{ route('admin.software-licenses.renewals', ['window' => 60, 'plan_status' => 'unplanned']) }}" class="btn btn-sm btn-outline-primary">Plan</a>
                                @endif
                            </td>
                            <td class="text-end pe-4"><span class="badge bg-{{ $license->is_expired ? 'danger' : 'warning' }}">{{ $license->expiry_date?->format('d-m-Y') }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No licenses expiring in the next 60 days.</td></tr>
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
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Usage Optimization</span>
                <a href="{{ route('admin.software-optimization.index') }}" class="btn btn-sm btn-outline-primary">Optimize</a>
            </div>
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Open employee reviews</span><strong class="text-dark">{{ $stats['open_usage_reviews'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small"><span>Reclaimed annual savings</span><strong class="text-dark">Rs {{ number_format((float) $stats['reclaimed_savings'], 0) }}</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">Employee</th><th>Software</th><th>Status</th><th class="text-end pe-4">Savings</th></tr></thead>
                    <tbody>
                        @forelse($usageReviews as $review)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $review->assignment?->user?->name ?? 'Unknown employee' }}</div><div class="text-muted small">{{ $review->inactivity_days ?? 0 }} inactive days</div></td>
                            <td><div class="fw-bold">{{ $review->assignment?->license?->software?->name ?? 'Unknown software' }}</div><div class="text-muted small">{{ $review->owner?->name ?? 'Unassigned' }}</div></td>
                            <td><span class="badge bg-{{ $review->status_badge }}">{{ $review->status_label }}</span></td>
                            <td class="text-end pe-4">Rs {{ number_format((float) $review->estimated_annual_savings, 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No usage optimization reviews yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Software Demand</span>
                <a href="{{ route('admin.software-requests.index', ['status' => 'approved']) }}" class="btn btn-sm btn-outline-primary">Requests</a>
            </div>
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Pending review</span><strong class="text-dark">{{ $stats['pending_requests'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small"><span>Approved awaiting allocation or PO</span><strong class="text-dark">{{ $stats['approved_requests'] }}</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">Employee</th><th>Software</th><th>Status</th><th class="text-end pe-4">Need</th></tr></thead>
                    <tbody>
                        @forelse($softwareRequests as $softwareRequest)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $softwareRequest->requester?->name ?? 'Unknown employee' }}</div><div class="text-muted small">{{ $softwareRequest->requester?->department?->name ?? $softwareRequest->requester?->employee_id ?? 'No department' }}</div></td>
                            <td><div class="fw-bold">{{ $softwareRequest->software?->name ?? 'Unknown software' }}</div><div class="text-muted small">{{ $softwareRequest->software?->vendor ?: 'Unknown publisher' }}</div></td>
                            <td><span class="badge bg-{{ $softwareRequest->status_badge }}">{{ $softwareRequest->status_label }}</span><div class="text-muted small">{{ ucfirst($softwareRequest->urgency) }} priority</div></td>
                            <td class="text-end pe-4">
                                <div>{{ $softwareRequest->needed_by?->format('d-m-Y') ?? 'No date' }}</div>
                                @if($softwareRequest->purchaseOrderItem)
                                    <div class="text-muted small">{{ $softwareRequest->purchaseOrderItem->purchaseOrder?->po_number ?? 'PO linked' }}</div>
                                @elseif($softwareRequest->status === 'approved')
                                    <div class="text-warning small">Needs allocation / PO</div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No pending or approved software demand.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Software Procurement</span>
                @if(auth()->user()->hasPermission('purchase_orders.manage'))
                    <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-sm btn-outline-primary">Purchase Orders</a>
                @endif
            </div>
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Open software PO lines</span><strong class="text-dark">{{ $stats['open_software_po_items'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small"><span>Pending software seats</span><strong class="text-dark">{{ $stats['pending_software_po_seats'] }}</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">PO / Supplier</th><th>Software</th><th>Progress</th><th class="text-end pe-4">Demand</th></tr></thead>
                    <tbody>
                        @forelse($softwareProcurement as $item)
                        <tr>
                            <td class="ps-4">
                                @if(auth()->user()->hasPermission('purchase_orders.manage'))
                                    <a href="{{ route('admin.purchase-orders.show', $item->purchaseOrder) }}" class="fw-bold text-decoration-none">{{ $item->purchaseOrder?->po_number ?? 'PO deleted' }}</a>
                                @else
                                    <div class="fw-bold">{{ $item->purchaseOrder?->po_number ?? 'PO deleted' }}</div>
                                @endif
                                <div class="text-muted small">{{ $item->purchaseOrder?->supplier?->name ?? 'Unknown supplier' }}</div>
                            </td>
                            <td><div class="fw-bold">{{ $item->software?->name ?? $item->item_name }}</div><div class="text-muted small">{{ $item->license_type ?: 'software' }} &middot; {{ $item->subscription_period ?: 'term not set' }}</div></td>
                            <td><span class="badge bg-{{ $item->pending_quantity > 0 ? 'warning text-dark' : 'success' }}">{{ $item->received_quantity }} / {{ $item->quantity }} received</span><div class="text-muted small">{{ ucfirst(str_replace('_', ' ', $item->purchaseOrder?->status ?? 'unknown')) }}</div></td>
                            <td class="text-end pe-4"><div>{{ $item->softwareRequests->count() }} request(s)</div><div class="text-muted small">{{ $item->softwareLicenses->sum('seats') }} license seat(s)</div></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No software purchase order lines yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Inventory Data Quality</span>
                @if(auth()->user()->hasPermission('endpoint.view'))
                    <a href="{{ route('admin.agent-sources.index') }}" class="btn btn-sm btn-outline-primary">Endpoints</a>
                @endif
            </div>
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Devices without asset link</span><strong class="text-dark">{{ $stats['unlinked_devices'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Devices without employee</span><strong class="text-dark">{{ $stats['unassigned_devices'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small"><span>Agents reporting errors</span><strong class="text-dark">{{ $stats['devices_with_errors'] }}</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">Device</th><th>Linkage</th><th>Health</th><th class="text-end pe-4">Last Seen</th></tr></thead>
                    <tbody>
                        @forelse($inventoryGaps as $device)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $device->hostname ?: $device->device_uuid }}</div><div class="text-muted small">{{ $device->serial_number ?: 'No serial number' }}</div></td>
                            <td>
                                @if($device->asset && $device->user)
                                    <span class="badge bg-success">Linked</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ !$device->asset ? 'No Asset' : 'No Employee' }}</span>
                                @endif
                                @if($device->last_error)<div class="text-danger small">{{ Str::limit($device->last_error, 45) }}</div>@endif
                            </td>
                            <td><span class="badge bg-{{ $device->health_status === 'healthy' ? 'success' : ($device->health_status === 'stale' ? 'warning text-dark' : 'danger') }}">{{ ucfirst($device->health_status) }}</span><div class="text-muted small">Agent {{ $device->agent_version ?: '-' }}</div></td>
                            <td class="text-end pe-4">{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No inventory data quality gaps found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Policy Governance</span>
                <a href="{{ route('admin.software-policies.index') }}" class="btn btn-sm btn-outline-primary">Policies</a>
            </div>
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Unreviewed policies</span><strong class="text-dark">{{ $stats['unreviewed_policies'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Stale policy reviews</span><strong class="text-dark">{{ $stats['stale_policies'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small"><span>Active exceptions / prohibited installs</span><strong class="text-dark">{{ $stats['active_policy_exceptions'] }} / {{ $stats['prohibited_installations'] }}</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">Software</th><th>Policy</th><th>Installs</th><th class="text-end pe-4">Reviewed</th></tr></thead>
                    <tbody>
                        @forelse($policyGaps as $software)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $software->name }}</div><div class="text-muted small">{{ $software->vendor ?: 'Unknown publisher' }}</div></td>
                            <td><span class="badge bg-{{ $software->policy_status_badge }}">{{ $software->policy_status_label }}</span><div class="text-muted small">{{ ucfirst($software->criticality) }} criticality</div></td>
                            <td>{{ $software->installed_count }}</td>
                            <td class="text-end pe-4"><div>{{ $software->policy_reviewed_at?->format('d-m-Y') ?? 'Never' }}</div><div class="text-muted small">{{ $software->policyReviewedBy?->name }}</div></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No policy governance gaps found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">License Evidence Quality</span>
                <a href="{{ route('admin.software-licenses.index') }}" class="btn btn-sm btn-outline-primary">Licenses</a>
            </div>
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between text-muted small mb-2"><span>Active licenses missing proof fields</span><strong class="text-dark">{{ $stats['licenses_missing_evidence'] }}</strong></div>
                <div class="d-flex justify-content-between text-muted small"><span>Active licenses missing cost</span><strong class="text-dark">{{ $stats['licenses_missing_cost'] }}</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-4">License</th><th>Proof Gaps</th><th>Seats</th><th class="text-end pe-4">Renewal</th></tr></thead>
                    <tbody>
                        @forelse($licenseEvidenceGaps as $license)
                        @php
                            $proofGaps = collect([
                                $license->evidence_document ? null : 'Evidence',
                                $license->invoice_number ? null : 'Invoice',
                                $license->po_number ? null : 'PO',
                                $license->vendor_id ? null : 'Supplier',
                                $license->purchase_date ? null : 'Purchase date',
                                ($license->purchase_price || $license->unit_cost) ? null : 'Cost',
                            ])->filter();
                        @endphp
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $license->software?->name ?? 'Unknown software' }}</div><div class="text-muted small">{{ $license->purchase_batch ?: $license->license_type_label }}</div></td>
                            <td><span class="badge bg-warning text-dark">{{ $proofGaps->implode(', ') }}</span><div class="text-muted small">{{ $license->vendor?->name ?? 'No supplier' }}</div></td>
                            <td>{{ $license->used_seats }} / {{ $license->seats }}</td>
                            <td class="text-end pe-4">{{ ($license->renewal_date ?? $license->expiry_date)?->format('d-m-Y') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No active license evidence gaps found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
