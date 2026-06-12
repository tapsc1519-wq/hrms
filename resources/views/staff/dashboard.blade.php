@extends('layouts.app')

@section('title', 'My Dashboard')

@push('styles')
<style>
    .employee-dashboard { font-size: .84rem; }
    .employee-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 58%, #0f766e 100%);
        border-radius: 16px;
        box-shadow: 0 14px 30px rgba(15, 23, 42, .16);
        color: #fff;
        margin-bottom: 1rem;
        overflow: hidden;
        padding: 1.15rem 1.25rem;
        position: relative;
    }
    .employee-hero::after {
        background: rgba(255, 255, 255, .1);
        border-radius: 50%;
        bottom: -70px;
        content: "";
        height: 170px;
        position: absolute;
        right: -48px;
        width: 170px;
    }
    .employee-hero-content {
        align-items: center;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        position: relative;
        z-index: 1;
    }
    .employee-hero h4 {
        font-size: 1.08rem;
        font-weight: 850;
        line-height: 1.25;
        margin: 0 0 .25rem;
    }
    .employee-hero p {
        color: rgba(226, 232, 240, .88);
        font-size: .78rem;
        line-height: 1.4;
        margin: 0;
    }
    .employee-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: .42rem;
        margin-top: .75rem;
    }
    .employee-chip {
        align-items: center;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: 999px;
        color: #fff;
        display: inline-flex;
        font-size: .68rem;
        font-weight: 750;
        gap: .34rem;
        padding: .32rem .62rem;
    }
    .hero-date {
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .24);
        border-radius: 13px;
        min-width: 148px;
        padding: .75rem;
        text-align: right;
    }
    .hero-date .label {
        color: rgba(226, 232, 240, .8);
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .hero-date .value {
        font-size: .95rem;
        font-weight: 850;
        margin-top: .12rem;
    }
    .dashboard-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, .86fr) minmax(0, 1.14fr);
        margin-bottom: 1rem;
    }
    .dash-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 15px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .055);
    }
    .dash-card-header {
        align-items: center;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        gap: .75rem;
        justify-content: space-between;
        padding: .82rem 1rem;
    }
    .dash-card-body { padding: 1rem; }
    .section-title {
        color: #0f172a;
        font-size: .92rem;
        font-weight: 850;
        margin: 0;
    }
    .section-sub {
        color: #64748b;
        font-size: .72rem;
        margin-top: .12rem;
    }
    .panel-label {
        color: #64748b;
        font-size: .66rem;
        font-weight: 850;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .attendance-status {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: .68rem;
        font-weight: 800;
        gap: .34rem;
        padding: .32rem .6rem;
    }
    .attendance-time-grid {
        display: grid;
        gap: .65rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin: .85rem 0;
    }
    .attendance-time {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 13px;
        padding: .72rem;
    }
    .attendance-time .value {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 850;
        line-height: 1.1;
        margin-top: .18rem;
    }
    .attendance-action-btn {
        align-items: center;
        border-radius: 12px;
        display: inline-flex;
        font-size: .86rem;
        font-weight: 800;
        justify-content: center;
        min-height: 44px;
        width: 100%;
    }
    .attention-grid {
        display: grid;
        gap: .65rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .attention-item,
    .quick-card {
        align-items: center;
        border: 1px solid #e2e8f0;
        border-radius: 13px;
        color: inherit;
        display: flex;
        gap: .7rem;
        min-height: 74px;
        padding: .78rem;
        text-decoration: none;
        transition: border-color .16s, box-shadow .16s, transform .16s;
    }
    .attention-item:hover,
    .quick-card:hover {
        border-color: #bfdbfe;
        box-shadow: 0 10px 22px rgba(15, 23, 42, .08);
        color: inherit;
        transform: translateY(-1px);
    }
    .item-icon,
    .list-icon {
        align-items: center;
        border-radius: 12px;
        display: inline-flex;
        flex-shrink: 0;
        height: 38px;
        justify-content: center;
        width: 38px;
    }
    .item-value {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 850;
        line-height: 1.05;
    }
    .item-label,
    .list-sub {
        color: #64748b;
        font-size: .7rem;
        line-height: 1.35;
    }
    .quick-grid {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .quick-card {
        align-items: flex-start;
        flex-direction: column;
        min-height: 118px;
    }
    .quick-title {
        color: #0f172a;
        font-size: .78rem;
        font-weight: 850;
        line-height: 1.25;
    }
    .quick-sub {
        color: #64748b;
        font-size: .69rem;
        line-height: 1.35;
        margin-top: .1rem;
    }
    .work-card-body { padding: .35rem 1rem 1rem; }
    .list-row {
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        gap: .7rem;
        padding: .72rem 0;
    }
    .list-row:last-child { border-bottom: 0; }
    .list-title {
        color: #1e293b;
        font-size: .76rem;
        font-weight: 800;
        line-height: 1.25;
    }
    .soft-blue { background: #eff6ff; color: #2563eb; }
    .soft-green { background: #f0fdf4; color: #16a34a; }
    .soft-amber { background: #fffbeb; color: #b45309; }
    .soft-orange { background: #fff7ed; color: #ea580c; }
    .soft-red { background: #fef2f2; color: #dc2626; }
    .soft-purple { background: #f5f3ff; color: #7c3aed; }
    .soft-teal { background: #f0fdfa; color: #0f766e; }
    .soft-slate { background: #f8fafc; color: #475569; }
    .empty-box {
        color: #94a3b8;
        font-size: .76rem;
        padding: 1.15rem 0 .85rem;
        text-align: center;
    }
    @media (max-width: 1199.98px) {
        .quick-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 991.98px) {
        .dashboard-grid { grid-template-columns: 1fr; }
        .quick-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .employee-hero-content { align-items: flex-start; flex-direction: column; }
        .hero-date { text-align: left; }
    }
    @media (max-width: 575.98px) {
        .attention-grid,
        .quick-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
@php
    $activeSession = $todayAttendance?->sessions?->firstWhere('sign_out_at', null);
    $signedIn = (bool) $activeSession;
    $sessionCount = $todayAttendance?->sessions?->count() ?? 0;
    $attendanceLabel = $signedIn ? 'Signed In' : ($sessionCount > 0 ? 'Signed Out' : 'Not Signed In');
    $attendanceClass = $signedIn ? 'soft-blue' : ($sessionCount > 0 ? 'soft-green' : 'soft-slate');

    $attentionItems = [];
    if ($hasHrms) {
        $attentionItems[] = [
            'label' => 'Pending Documents',
            'value' => $stats['pending_documents'],
            'icon' => 'bi-file-earmark-check',
            'color' => 'green',
            'route' => route('staff.profile.show'),
        ];
        $attentionItems[] = [
            'label' => 'My Leave Requests',
            'value' => $stats['pending_leaves'],
            'icon' => 'bi-calendar-check',
            'color' => 'teal',
            'route' => route('staff.leaves.index'),
        ];
    }
    if ($hasItam) {
        $attentionItems[] = [
            'label' => 'Incoming Handovers',
            'value' => $stats['incoming_handovers'],
            'icon' => 'bi-arrow-left-right',
            'color' => 'purple',
            'route' => route('staff.my-assets.index'),
        ];
    }
    if ($hasSupport) {
        $attentionItems[] = [
            'label' => 'Open Tickets',
            'value' => $stats['open_tickets'],
            'icon' => 'bi-headset',
            'color' => 'red',
            'route' => route('staff.tickets.index'),
        ];
    }

    $quickLinks = [];
    if ($hasHrms) {
        $quickLinks[] = ['title' => 'My Profile', 'subtitle' => 'Personal, job and documents', 'icon' => 'bi-person-vcard-fill', 'color' => 'green', 'route' => route('staff.profile.show')];
        $quickLinks[] = ['title' => 'My Attendance', 'subtitle' => 'Logs and corrections', 'icon' => 'bi-clock-history', 'color' => 'blue', 'route' => route('staff.attendance.index')];
        $quickLinks[] = ['title' => 'My Leaves', 'subtitle' => 'Apply and track leaves', 'icon' => 'bi-calendar-check-fill', 'color' => 'teal', 'route' => route('staff.leaves.index')];
    }
    if ($hasPayroll) {
        $quickLinks[] = ['title' => 'My Payslips', 'subtitle' => 'Download salary slips', 'icon' => 'bi-receipt-cutoff', 'color' => 'amber', 'route' => route('staff.payslips.index')];
    }
    if ($hasItam) {
        $quickLinks[] = ['title' => 'My Assets', 'subtitle' => 'Assets and handovers', 'icon' => 'bi-box-seam-fill', 'color' => 'purple', 'route' => route('staff.my-assets.index')];
        $quickLinks[] = ['title' => 'My Requests', 'subtitle' => 'Request assets from IT', 'icon' => 'bi-clipboard-plus-fill', 'color' => 'blue', 'route' => route('staff.requests.index')];
    }
    if ($hasSam) {
        $quickLinks[] = ['title' => 'My Software', 'subtitle' => 'Assigned software licenses', 'icon' => 'bi-display-fill', 'color' => 'green', 'route' => route('staff.my-software.index')];
    }
    if ($hasSupport) {
        $quickLinks[] = ['title' => 'Support Tickets', 'subtitle' => 'Raise and track tickets', 'icon' => 'bi-headset', 'color' => 'red', 'route' => route('staff.tickets.index')];
    }
@endphp

<div class="employee-dashboard">
    <div class="employee-hero">
        <div class="employee-hero-content">
            <div>
                <h4>Hi {{ auth()->user()->name }}, here is your workspace</h4>
                <p>
                    @if($employee?->employee_code)
                        {{ $employee->employee_code }} &middot;
                    @endif
                    {{ auth()->user()->job_title ?? 'Employee' }}
                    @if(auth()->user()->department?->name)
                        &middot; {{ auth()->user()->department->name }}
                    @endif
                </p>
                <div class="employee-chip-row">
                    @if($employee?->shift)
                        <span class="employee-chip"><i class="bi bi-clock"></i>{{ $employee->shift->name }}</span>
                    @endif
                    @if($employee?->manager)
                        <span class="employee-chip"><i class="bi bi-person-check"></i>Manager: {{ $employee->manager->name }}</span>
                    @endif
                </div>
            </div>
            <div class="hero-date">
                <div class="label">Today</div>
                <div class="value">{{ now()->format('D, d-m-Y') }}</div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        @if($hasHrms)
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h5 class="section-title">Attendance</h5>
                        <div class="section-sub">{{ now()->format('l, d-m-Y') }}</div>
                    </div>
                    <span class="attendance-status {{ $attendanceClass }}">
                        <i class="bi bi-circle-fill" style="font-size:.45rem"></i>{{ $attendanceLabel }}
                    </span>
                </div>
                <div class="dash-card-body">
                    <div class="attendance-time-grid">
                        <div class="attendance-time">
                            <div class="panel-label">Sign In</div>
                            <div class="value">{{ $activeSession?->sign_in_at?->format('h:i A') ?? $todayAttendance?->sign_in_at?->format('h:i A') ?? '--' }}</div>
                        </div>
                        <div class="attendance-time">
                            <div class="panel-label">Sign Out</div>
                            <div class="value">{{ $todayAttendance?->sign_out_at?->format('h:i A') ?? '--' }}</div>
                        </div>
                    </div>

                    @if(!$signedIn)
                        <form method="POST" action="{{ route('staff.attendance.sign-in') }}">
                            @csrf
                            <button class="btn btn-primary attendance-action-btn">
                                <i class="bi bi-box-arrow-in-right me-2"></i>{{ $sessionCount > 0 ? 'Sign In Again' : 'Sign In' }}
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('staff.attendance.sign-out') }}">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-danger attendance-action-btn">
                                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        <div class="dash-card">
            <div class="dash-card-header">
                <div>
                    <h5 class="section-title">Needs Attention</h5>
                    <div class="section-sub">Pending items that may need your action.</div>
                </div>
            </div>
            <div class="dash-card-body">
                <div class="attention-grid">
                    @forelse($attentionItems as $item)
                        <a href="{{ $item['route'] }}" class="attention-item">
                            <span class="item-icon soft-{{ $item['color'] }}"><i class="bi {{ $item['icon'] }}"></i></span>
                            <span>
                                <span class="item-value d-block">{{ $item['value'] }}</span>
                                <span class="item-label d-block">{{ $item['label'] }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="empty-box">No pending items.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="dash-card mb-3">
        <div class="dash-card-header">
            <div>
                <h5 class="section-title">Quick Access</h5>
                <div class="section-sub">Common employee self-service areas.</div>
            </div>
        </div>
        <div class="dash-card-body">
            <div class="quick-grid">
                @foreach($quickLinks as $link)
                    <a href="{{ $link['route'] }}" class="quick-card">
                        <span class="item-icon soft-{{ $link['color'] }}"><i class="bi {{ $link['icon'] }}"></i></span>
                        <span>
                            <span class="quick-title d-block">{{ $link['title'] }}</span>
                            <span class="quick-sub d-block">{{ $link['subtitle'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if(count($managementCards) > 0)
        <div class="dash-card mb-3">
            <div class="dash-card-header">
                <div>
                    <h5 class="section-title">Management Access</h5>
                    <div class="section-sub">Actions available from your assigned permission role.</div>
                </div>
                <span class="badge bg-primary">{{ count($managementCards) }}</span>
            </div>
            <div class="dash-card-body">
                <div class="quick-grid">
                    @foreach($managementCards as $card)
                        <a href="{{ route($card['route']) }}" class="quick-card">
                            <span class="item-icon soft-{{ $card['color'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                            <span>
                                <span class="quick-title d-block">{{ $card['title'] }}</span>
                                <span class="quick-sub d-block">{{ $card['subtitle'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        @if($hasHrms)
            <div class="col-lg-4">
                <div class="dash-card h-100">
                    <div class="dash-card-header">
                        <h5 class="section-title">Recent Leaves</h5>
                        <a href="{{ route('staff.leaves.index') }}" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                    <div class="work-card-body">
                        @forelse($recentLeaves as $leave)
                            <div class="list-row">
                                <span class="list-icon soft-teal"><i class="bi bi-calendar-check"></i></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="list-title">{{ $leave->leave_type_label }}</div>
                                    <div class="list-sub">{{ $leave->from_date?->format('d-m-Y') }} to {{ $leave->to_date?->format('d-m-Y') }}</div>
                                </div>
                                <span class="badge bg-{{ $leave->status_badge }}">{{ ucfirst($leave->status) }}</span>
                            </div>
                        @empty
                            <div class="empty-box">No leave requests yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        @if($hasItam)
            <div class="col-lg-4">
                <div class="dash-card h-100">
                    <div class="dash-card-header">
                        <h5 class="section-title">Assigned Assets</h5>
                        <a href="{{ route('staff.my-assets.index') }}" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                    <div class="work-card-body">
                        @forelse($myAssets as $assignment)
                            <div class="list-row">
                                <span class="list-icon soft-purple"><i class="bi {{ $assignment->asset->category?->icon ?? 'bi-box' }}"></i></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="list-title">{{ $assignment->asset->name }}</div>
                                    <div class="list-sub">{{ $assignment->asset->asset_tag }} &middot; {{ $assignment->assigned_date?->format('d-m-Y') }}</div>
                                </div>
                                <span class="badge bg-success">Active</span>
                            </div>
                        @empty
                            <div class="empty-box">No assets assigned.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        @if($hasSupport)
            <div class="col-lg-4">
                <div class="dash-card h-100">
                    <div class="dash-card-header">
                        <h5 class="section-title">Support Tickets</h5>
                        <a href="{{ route('staff.tickets.create') }}" class="btn btn-sm btn-primary">New</a>
                    </div>
                    <div class="work-card-body">
                        @forelse($myTickets as $ticket)
                            <div class="list-row">
                                <span class="list-icon soft-red"><i class="bi bi-headset"></i></span>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('staff.tickets.show', $ticket) }}" class="list-title text-decoration-none d-block">{{ \Illuminate\Support\Str::limit($ticket->subject, 34) }}</a>
                                    <div class="list-sub">{{ $ticket->ticket_number }} &middot; {{ $ticket->created_at?->format('d-m-Y') }}</div>
                                </div>
                                <span class="badge bg-{{ $ticket->status_badge }}">{{ $ticket->status_label }}</span>
                            </div>
                        @empty
                            <div class="empty-box">No support tickets yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
