@extends('layouts.app')
@section('title', 'Admin Dashboard')

@push('styles')
<style>
    .suite-dashboard { font-size: .84rem; }
    .suite-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 54%, #0f766e 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.35rem 1.45rem;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .18);
    }
    .suite-hero h4 { font-size: 1.18rem; font-weight: 800; margin: 0; line-height: 1.25; }
    .suite-hero p { color: rgba(219, 234, 254, .86); font-size: .8rem; margin: .25rem 0 0; }
    .suite-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.22);
        color: #fff; border-radius: 999px; padding: .34rem .7rem;
        font-size: .72rem; font-weight: 750;
    }
    .suite-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
    }
    .module-card { padding: 1rem; height: 100%; position: relative; overflow: hidden; }
    .module-card::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--accent-color); }
    .module-icon {
        width: 38px; height: 38px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--soft-color); color: var(--accent-color);
        font-size: 1rem;
    }
    .module-title { color: #0f172a; font-size: .9rem; font-weight: 800; margin: 0; }
    .module-status { border-radius: 999px; font-size: .66rem; font-weight: 800; padding: .25rem .5rem; }
    .module-value { color: #0f172a; font-size: 1.35rem; font-weight: 850; line-height: 1.1; margin-top: .8rem; }
    .module-label, .suite-muted { color: #64748b; font-size: .73rem; line-height: 1.35; }
    .panel-header {
        display: flex; align-items: center; justify-content: space-between;
        gap: .75rem; padding: .85rem 1rem; border-bottom: 1px solid #eef2f7;
    }
    .panel-title { color: #0f172a; font-size: .88rem; font-weight: 800; margin: 0; }
    .panel-body { padding: .9rem 1rem; }
    .mini-stat {
        border: 1px solid #e2e8f0; border-radius: 12px; padding: .75rem;
        background: #f8fafc; height: 100%;
    }
    .mini-stat-value { color: #0f172a; font-size: 1.08rem; font-weight: 850; line-height: 1.1; }
    .mini-stat-label { color: #64748b; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; margin-top: .2rem; }
    .list-row {
        display: flex; align-items: center; gap: .7rem;
        padding: .65rem 0; border-bottom: 1px solid #f1f5f9;
    }
    .list-row:last-child { border-bottom: 0; }
    .list-icon {
        width: 33px; height: 33px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #f1f5f9; color: #475569; flex-shrink: 0;
    }
    .list-title { color: #1e293b; font-size: .8rem; font-weight: 750; line-height: 1.25; }
    .list-sub { color: #64748b; font-size: .7rem; margin-top: .1rem; }
    .soft-badge { border-radius: 999px; font-size: .66rem; font-weight: 800; padding: .25rem .5rem; white-space: nowrap; }
    .soft-blue { background: #eff6ff; color: #2563eb; }
    .soft-green { background: #f0fdf4; color: #16a34a; }
    .soft-amber { background: #fffbeb; color: #b45309; }
    .soft-red { background: #fef2f2; color: #dc2626; }
    .soft-purple { background: #f5f3ff; color: #7c3aed; }
    .soft-slate { background: #f8fafc; color: #475569; }
    .chart-box { height: 210px; position: relative; }
    .empty-box { color: #94a3b8; font-size: .78rem; padding: 1.35rem 0; text-align: center; }
</style>
@endpush

@section('content')
@php
    $enabledModuleCards = collect($moduleCards)->where('enabled', true);
    $enabledModules = $enabledModuleCards->count();
    $hasItamDashboard = (bool) ($moduleCards['itam']['enabled'] ?? false);
    $hasSamDashboard = (bool) ($moduleCards['sam']['enabled'] ?? false);
    $hasHrmsDashboard = (bool) ($moduleCards['hrms']['enabled'] ?? false);
    $hasPayrollDashboard = (bool) ($moduleCards['payroll']['enabled'] ?? false);
    $hasSupportDashboard = (bool) ($moduleCards['support']['enabled'] ?? false);
    $activeActions = 0;
    if ($hasHrmsDashboard) {
        $activeActions += $hrmsStats['pending_leaves'] + $hrmsStats['pending_regularizations'] + $hrmsStats['pending_documents'];
    }
    if ($hasItamDashboard) {
        $activeActions += $itamStats['pending_requests'] + $itamStats['pending_pos'] + $itamStats['maintenance_due'];
    }
    if ($hasSamDashboard) {
        $activeActions += $samStats['expiring'];
    }
    if ($hasPayrollDashboard) {
        $activeActions += $payrollStats['draft_runs'] + $payrollStats['approved_runs'];
    }
    if ($hasSupportDashboard) {
        $activeActions += $supportStats['open'] + $supportStats['in_progress'];
    }
    $moduleColors = [
        'blue' => ['#2563eb', '#eff6ff'],
        'green' => ['#16a34a', '#f0fdf4'],
        'purple' => ['#7c3aed', '#f5f3ff'],
        'amber' => ['#b45309', '#fffbeb'],
        'red' => ['#dc2626', '#fef2f2'],
    ];
@endphp

<div class="suite-dashboard">
    <div class="suite-hero mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h4>{{ $organization?->name ?? 'Organization' }} Command Center</h4>
                <p>Workspace view based on enabled modules for this organization.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <span class="suite-chip"><i class="bi bi-grid-fill"></i>{{ $enabledModules }} modules enabled</span>
                <span class="suite-chip"><i class="bi bi-lightning-charge-fill"></i>{{ $activeActions }} pending actions</span>
                <span class="suite-chip"><i class="bi bi-calendar3"></i>{{ now()->format('d-m-Y') }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @forelse($enabledModuleCards as $key => $module)
            @php
                $colors = $moduleColors[$module['color']] ?? ['#475569', '#f8fafc'];
                $accent = $colors[0];
                $soft = $colors[1];
            @endphp
            <div class="col-md-6 col-xl">
                <div class="suite-card module-card" style="--accent-color: {{ $accent }}; --soft-color: {{ $soft }}">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="module-icon"><i class="bi {{ $module['icon'] }}"></i></span>
                            <h5 class="module-title">{{ $module['title'] }}</h5>
                        </div>
                    </div>
                    <div class="module-value">{{ number_format($module['primary']) }}</div>
                    <div class="module-label">{{ $module['primary_label'] }}</div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <div class="fw-bold" style="font-size:.86rem;color:#0f172a">{{ number_format($module['secondary']) }}</div>
                            <div class="module-label">{{ $module['secondary_label'] }}</div>
                        </div>
                        <a href="{{ route($module['route']) }}" class="btn btn-sm btn-outline-primary">Open</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="suite-card p-4 text-center">
                    <i class="bi bi-grid fs-2 d-block mb-2 text-muted"></i>
                    <div class="fw-bold" style="color:#0f172a">No modules enabled</div>
                    <div class="suite-muted">Ask the platform administrator to enable modules for this organization.</div>
                </div>
            </div>
        @endforelse
    </div>

    @if($enabledModules > 0)
    <div class="row g-3 mb-3">
        <div class="{{ $hasItamDashboard ? 'col-lg-8' : 'col-lg-12' }}">
            <div class="suite-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title">Operational Health</h5>
                    <span class="soft-badge {{ $activeActions > 0 ? 'soft-amber' : 'soft-green' }}">
                        {{ $activeActions > 0 ? $activeActions . ' actions' : 'All clear' }}
                    </span>
                </div>
                <div class="panel-body">
                    <div class="row g-2">
                        @if($hasHrmsDashboard)
                        <div class="col-sm-6 col-xl-3"><div class="mini-stat"><div class="mini-stat-value">{{ $hrmsStats['present_today'] }}/{{ $hrmsStats['active_employees'] }}</div><div class="mini-stat-label">Present Today</div></div></div>
                        @endif
                        @if($hasItamDashboard)
                        <div class="col-sm-6 col-xl-3"><div class="mini-stat"><div class="mini-stat-value">{{ $itamStats['assigned'] }}/{{ $itamStats['assets'] }}</div><div class="mini-stat-label">Assets Assigned</div></div></div>
                        @endif
                        @if($hasSamDashboard)
                        <div class="col-sm-6 col-xl-3"><div class="mini-stat"><div class="mini-stat-value">{{ $samStats['assigned'] }}</div><div class="mini-stat-label">Software Assigned</div></div></div>
                        @endif
                        @if($hasPayrollDashboard)
                        <div class="col-sm-6 col-xl-3"><div class="mini-stat"><div class="mini-stat-value">&#8377;{{ number_format($payrollStats['latest_net'], 0) }}</div><div class="mini-stat-label">Latest Payroll</div></div></div>
                        @endif
                    </div>

                    <div class="row g-2 mt-2">
                        @if($hasHrmsDashboard)
                        <div class="col-sm-6 col-xl-3"><div class="mini-stat"><div class="mini-stat-value">{{ $hrmsStats['pending_leaves'] }}</div><div class="mini-stat-label">Pending Leaves</div></div></div>
                        @endif
                        @if($hasItamDashboard)
                        <div class="col-sm-6 col-xl-3"><div class="mini-stat"><div class="mini-stat-value">{{ $itamStats['pending_requests'] }}</div><div class="mini-stat-label">Asset Requests</div></div></div>
                        @endif
                        @if($hasSamDashboard)
                        <div class="col-sm-6 col-xl-3"><div class="mini-stat"><div class="mini-stat-value">{{ $samStats['expiring'] }}</div><div class="mini-stat-label">Licenses Expiring</div></div></div>
                        @endif
                        @if($hasSupportDashboard)
                        <div class="col-sm-6 col-xl-3"><div class="mini-stat"><div class="mini-stat-value">{{ $supportStats['urgent'] }}</div><div class="mini-stat-label">Urgent Tickets</div></div></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($hasItamDashboard)
        <div class="col-lg-4">
            <div class="suite-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title">Purchasing Trend</h5>
                    <span class="soft-badge soft-blue">INR</span>
                </div>
                <div class="panel-body">
                    <div class="chart-box"><canvas id="purchaseChart"></canvas></div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="row g-3 mb-3">
        @if($hasHrmsDashboard)
        <div class="col-lg-4">
            <div class="suite-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title">HRMS Queue</h5>
                    <a href="{{ route('admin.leaves.index') }}" class="btn btn-sm btn-outline-primary">Leaves</a>
                </div>
                <div class="panel-body">
                    @forelse($pendingLeaves as $leave)
                        <div class="list-row">
                            <span class="list-icon"><i class="bi bi-calendar-check"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="list-title">{{ $leave->employee?->user?->name ?? 'Employee' }}</div>
                                <div class="list-sub">{{ $leave->leave_type_label }} &middot; {{ $leave->from_date?->format('d-m-Y') }} to {{ $leave->to_date?->format('d-m-Y') }}</div>
                            </div>
                            <span class="soft-badge soft-amber">{{ $leave->total_days }}d</span>
                        </div>
                    @empty
                        <div class="empty-box">No pending leave requests.</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif

        @if($hasItamDashboard)
        <div class="col-lg-4">
            <div class="suite-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title">ITAM Activity</h5>
                    <a href="{{ route('admin.assets.index') }}" class="btn btn-sm btn-outline-primary">Assets</a>
                </div>
                <div class="panel-body">
                    @forelse($recentAssets as $asset)
                        <div class="list-row">
                            <span class="list-icon"><i class="bi bi-box-seam"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('admin.assets.show', $asset) }}" class="list-title text-decoration-none d-block">{{ \Illuminate\Support\Str::limit($asset->name, 34) }}</a>
                                <div class="list-sub">{{ $asset->asset_tag }} &middot; {{ $asset->category?->name ?? 'Uncategorised' }}</div>
                            </div>
                            <span class="soft-badge {{ $asset->status === 'available' ? 'soft-green' : ($asset->status === 'assigned' ? 'soft-blue' : 'soft-amber') }}">{{ ucwords(str_replace('_', ' ', $asset->status)) }}</span>
                        </div>
                    @empty
                        <div class="empty-box">No assets added yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif

        @if($hasSupportDashboard)
        <div class="col-lg-4">
            <div class="suite-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title">Support Tickets</h5>
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-outline-primary">Tickets</a>
                </div>
                <div class="panel-body">
                    @forelse($recentTickets as $ticket)
                        <div class="list-row">
                            <span class="list-icon"><i class="bi bi-headset"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="list-title text-decoration-none d-block">{{ \Illuminate\Support\Str::limit($ticket->subject, 34) }}</a>
                                <div class="list-sub">{{ $ticket->ticket_number }} &middot; {{ $ticket->requester?->name ?? 'Unknown' }}</div>
                            </div>
                            <span class="soft-badge {{ $ticket->priority === 'urgent' ? 'soft-red' : ($ticket->priority === 'high' ? 'soft-amber' : 'soft-blue') }}">{{ ucfirst($ticket->priority) }}</span>
                        </div>
                    @empty
                        <div class="empty-box">No active support tickets.</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="row g-3">
        @if($hasPayrollDashboard)
        <div class="col-lg-6">
            <div class="suite-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title">Payroll Runs</h5>
                    <a href="{{ route('admin.payroll.runs') }}" class="btn btn-sm btn-outline-primary">Runs</a>
                </div>
                <div class="panel-body">
                    @forelse($recentPayrollRuns as $run)
                        <div class="list-row">
                            <span class="list-icon"><i class="bi bi-receipt-cutoff"></i></span>
                            <div class="flex-grow-1">
                                <div class="list-title">{{ $run->month }}</div>
                                <div class="list-sub">{{ $run->employee_count }} employees &middot; Net &#8377;{{ number_format((float) $run->total_net, 2) }}</div>
                            </div>
                            <span class="soft-badge {{ $run->status === 'paid' ? 'soft-green' : ($run->status === 'approved' ? 'soft-blue' : 'soft-amber') }}">{{ ucfirst($run->status) }}</span>
                        </div>
                    @empty
                        <div class="empty-box">No payroll runs generated yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif

        @if($hasItamDashboard)
        <div class="col-lg-6">
            <div class="suite-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title">Maintenance Schedule</h5>
                    <a href="{{ route('admin.maintenance.index') }}" class="btn btn-sm btn-outline-primary">Maintenance</a>
                </div>
                <div class="panel-body">
                    @forelse($upcomingMaintenance as $maintenance)
                        <div class="list-row">
                            <span class="list-icon"><i class="bi bi-tools"></i></span>
                            <div class="flex-grow-1">
                                <div class="list-title">{{ $maintenance->asset?->name ?? 'Unknown asset' }}</div>
                                <div class="list-sub">{{ $maintenance->scheduled_date?->format('d-m-Y') }} &middot; {{ $maintenance->technician_name ?? 'Technician not assigned' }}</div>
                            </div>
                            <span class="soft-badge soft-amber">{{ ucwords(str_replace('_', ' ', $maintenance->type)) }}</span>
                        </div>
                    @empty
                        <div class="empty-box">No maintenance scheduled in the next 30 days.</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const purchaseData = @json($purchaseChartData);
const purchaseChart = document.getElementById('purchaseChart');
if (purchaseChart) {
    new Chart(purchaseChart, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [{
                label: 'Purchase Spend',
                data: purchaseData,
                backgroundColor: 'rgba(37, 99, 235, .72)',
                borderRadius: 7,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ' \u20B9' + Number(ctx.parsed.y || 0).toLocaleString('en-IN');
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '\u20B9' + Number(value).toLocaleString('en-IN');
                        }
                    }
                }
            }
        }
    });
}
</script>
@endpush
