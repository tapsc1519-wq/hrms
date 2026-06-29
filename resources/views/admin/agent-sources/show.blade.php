@extends('layouts.app')
@section('title', $deviceAgent->hostname)

@section('content')
@php
    $hardware = $deviceAgent->hardware_info ?? [];
    $network = $deviceAgent->network_info ?? [];
    $security = $deviceAgent->security_info ?? [];
    $healthBadge = $deviceAgent->health_status === 'healthy' ? 'success' : ($deviceAgent->health_status === 'stale' ? 'warning' : 'danger');
    $canQueueCommands = (bool) $deviceAgent->credential?->is_active;
@endphp
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div><a href="{{ route('admin.agent-sources.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Endpoint Management</a><div class="d-flex align-items-center gap-2"><h4 class="mb-0">{{ $deviceAgent->hostname }}</h4><span class="badge bg-{{ $healthBadge }}">{{ ucfirst($deviceAgent->health_status) }}</span></div><p>{{ $deviceAgent->os_name ?: 'Unknown operating system' }} {{ $deviceAgent->os_version }}</p></div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if(auth()->user()->hasPermission('software.agents.manage'))
        <form method="POST" action="{{ route('admin.agent-sources.commands.inventory-refresh', $deviceAgent) }}">@csrf<button class="btn btn-primary btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Queue Inventory Refresh</button></form>
        @if($deviceAgent->credential?->is_active)
        <form method="POST" action="{{ route('admin.agent-sources.credential.revoke', $deviceAgent) }}" onsubmit="return confirm('Revoke this device credential? The agent will stop reporting until it is re-enrolled.')">@csrf @method('PATCH')<button class="btn btn-outline-danger btn-sm"><i class="bi bi-shield-x me-1"></i>Revoke Device Access</button></form>
        @endif
        @endif
        <div class="text-end small ms-1"><div class="text-muted">Last seen</div><div class="fw-bold">{{ $deviceAgent->last_seen_at?->format('d M Y, h:i A') ?? 'Never' }}</div></div>
    </div>
</div>

@if(auth()->user()->hasPermission('endpoint.device.control') || auth()->user()->hasPermission('endpoint.software.manage'))
<div class="table-card mb-3">
    <div class="card-header"><span class="fw-semibold">Endpoint Actions</span></div>
    <div class="card-body">
        @if(! $canQueueCommands)
            <div class="alert alert-warning small mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Commands cannot be queued until this endpoint has an active device credential. Re-enroll the device or restore agent access first.
            </div>
        @elseif($deviceAgent->health_status !== 'healthy')
            <div class="alert alert-info small mb-3">
                <i class="bi bi-clock-history me-1"></i>
                This endpoint is {{ $deviceAgent->health_status }}. Commands can be queued, but they will run only after the agent checks in before the command expires.
            </div>
        @endif
        <div class="row g-3 align-items-stretch">
            @if(auth()->user()->hasPermission('endpoint.device.control'))
            <div class="col-lg-5">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-1"><i class="bi bi-shield-lock me-1 text-primary"></i>Device Controls</div>
                    <div class="text-muted small mb-3">Lock the active session immediately or schedule a controlled restart.</div>
                    <div class="d-flex gap-2 flex-wrap">
                        <form method="POST" action="{{ route('admin.agent-sources.commands.lock', $deviceAgent) }}" onsubmit="return confirm('Lock the active Windows session on {{ addslashes($deviceAgent->hostname) }}?')">
                            @csrf
                            <button class="btn btn-outline-primary btn-sm" @disabled(! $canQueueCommands)><i class="bi bi-lock-fill me-1"></i>Lock Session</button>
                        </form>
                        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#restartEndpointModal" @disabled(! $canQueueCommands)><i class="bi bi-arrow-clockwise me-1"></i>Restart</button>
                    </div>
                </div>
            </div>
            @endif
            @if(auth()->user()->hasPermission('endpoint.software.manage'))
            <div class="col-lg-7">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-1"><i class="bi bi-box-arrow-down me-1 text-primary"></i>Managed Software</div>
                    <div class="text-muted small mb-3">Only catalog applications explicitly enabled for endpoint deployment are available.</div>
                    @if($managedSoftware->isNotEmpty())
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Install and remove commands use the selected software's WinGet Package ID on this endpoint.
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <form method="POST" action="{{ route('admin.agent-sources.commands.software-install', $deviceAgent) }}" onsubmit="return confirm('Install the selected approved software on this endpoint?')">
                                @csrf
                                <div class="input-group input-group-sm"><select name="software_id" class="form-select" required @disabled(! $canQueueCommands)><option value="">Choose software to install</option>@foreach($managedSoftware as $item)<option value="{{ $item->id }}">{{ $item->name }} - {{ $item->winget_package_id }}</option>@endforeach</select><button class="btn btn-primary" @disabled(! $canQueueCommands)><i class="bi bi-download me-1"></i>Install</button></div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="POST" action="{{ route('admin.agent-sources.commands.software-uninstall', $deviceAgent) }}" onsubmit="return confirm('Remove the selected software from this endpoint?')">
                                @csrf
                                <div class="input-group input-group-sm"><select name="software_id" class="form-select" required @disabled(! $canQueueCommands)><option value="">Choose software to remove</option>@foreach($managedSoftware as $item)<option value="{{ $item->id }}">{{ $item->name }} - {{ $item->winget_package_id }}</option>@endforeach</select><button class="btn btn-outline-danger" @disabled(! $canQueueCommands)><i class="bi bi-trash3 me-1"></i>Remove</button></div>
                            </form>
                        </div>
                    </div>
                    @else
                    <a href="{{ route('admin.software.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Configure Managed Software</a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

<div class="table-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Signed Command History</span><span class="badge bg-light text-dark">{{ $commands->total() }}</span></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Command</th><th>Queued</th><th>Delivered</th><th>Executed</th><th>Status</th><th>Result</th><th class="text-end pe-4">Action</th></tr></thead><tbody>
    @forelse($commands as $command)<tr><td class="ps-4"><div class="fw-bold">{{ ucwords(str_replace('_', ' ', $command->command_type)) }}</div>@if(in_array($command->command_type, ['software_install', 'software_uninstall'], true))<div class="small">{{ data_get($command->payload, 'name', 'Managed software') }}</div><div class="text-muted small font-monospace">{{ data_get($command->payload, 'package_id', 'No package ID') }}</div>@endif<div class="text-muted small font-monospace">{{ $command->command_uuid }}</div></td><td>{{ $command->created_at->format('d M Y, H:i') }}<div class="text-muted small">{{ $command->createdBy?->name ?? 'System' }}</div></td><td>{{ $command->delivered_at?->format('d M Y, H:i') ?? '-' }}</td><td>{{ $command->executed_at?->format('d M Y, H:i') ?? '-' }}</td><td><span class="badge bg-{{ $command->status_badge }}">{{ ucfirst($command->status) }}</span></td><td><div class="small">{{ data_get($command->result, 'message') ?: ($command->error_message ?: 'No result yet') }}</div></td><td class="text-end pe-4">@if(in_array($command->status, ['queued','delivered']))<form method="POST" action="{{ route('admin.agent-sources.commands.cancel', [$deviceAgent, $command]) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger">Cancel</button></form>@else<span class="text-muted small">Closed</span>@endif</td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">No commands have been queued for this device.</td></tr>@endforelse
    </tbody></table></div>
    @if($commands->hasPages())<div class="p-3 border-top">{{ $commands->links() }}</div>@endif
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3"><div class="table-card h-100"><div class="card-body"><div class="text-muted small">Asset</div><div class="fw-bold">{{ $deviceAgent->asset?->asset_tag ?? 'Not matched' }}</div><div class="small">{{ $deviceAgent->serial_number ?: 'No serial number' }}</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="table-card h-100"><div class="card-body"><div class="text-muted small">Employee</div><div class="fw-bold">{{ $deviceAgent->user?->name ?? 'Not matched' }}</div><div class="small">{{ $deviceAgent->user?->employee_id ?: 'No employee code' }}</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="table-card h-100"><div class="card-body"><div class="text-muted small">Agent Access</div><div class="fw-bold">{{ $deviceAgent->credential?->is_active ? 'Active' : ($deviceAgent->credential ? 'Revoked' : 'Enrollment pending') }}</div><div class="small">{{ $deviceAgent->credential ? $deviceAgent->credential->key_prefix.'...' : 'Agent '.$deviceAgent->agent_version }}</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="table-card h-100"><div class="card-body"><div class="text-muted small">Installed Software</div><div class="fw-bold">{{ $deviceAgent->installed_software_count }}</div><div class="small">Last inventory {{ $deviceAgent->last_inventory_at?->diffForHumans() ?? 'never' }}</div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-7"><div class="table-card h-100"><div class="card-header"><span class="fw-semibold">Hardware and Operating System</span></div><div class="card-body"><div class="row g-3">
        @foreach([
            ['Manufacturer', data_get($hardware, 'manufacturer', 'Unknown')], ['Model', data_get($hardware, 'model', 'Unknown')],
            ['Device Type', ucfirst(data_get($hardware, 'device_type', 'unknown'))], ['Domain', data_get($hardware, 'domain', 'Not joined')],
            ['CPU', data_get($hardware, 'cpu.name', 'Unknown')], ['CPU Cores', data_get($hardware, 'cpu.physical_cores', 0).' physical / '.data_get($hardware, 'cpu.logical_cores', 0).' logical'],
            ['Memory', data_get($hardware, 'memory.total_bytes') ? number_format(data_get($hardware, 'memory.total_bytes') / 1073741824, 1).' GB' : 'Unknown'],
            ['Uptime', data_get($hardware, 'uptime_minutes') ? number_format(data_get($hardware, 'uptime_minutes') / 60, 1).' hours' : 'Unknown'],
            ['Motherboard', trim(data_get($hardware, 'motherboard.manufacturer', '').' '.data_get($hardware, 'motherboard.model', '')) ?: 'Unknown'],
            ['BIOS', data_get($hardware, 'bios.version', 'Unknown')],
        ] as [$label, $value])
        <div class="col-md-6"><div class="text-muted small">{{ $label }}</div><div class="fw-semibold">{{ $value }}</div></div>
        @endforeach
    </div></div></div></div>
    <div class="col-xl-5"><div class="table-card h-100"><div class="card-header"><span class="fw-semibold">Security Status</span></div><div class="card-body">
        <div class="mb-3"><div class="text-muted small mb-1">Antivirus</div>@forelse(data_get($security, 'antivirus', []) as $item)<div class="fw-semibold">{{ data_get($item, 'name', 'Unknown product') }}</div>@empty<div class="text-muted">Not reported</div>@endforelse</div>
        <div class="mb-3"><div class="text-muted small mb-1">Firewall Profiles</div>@forelse(data_get($security, 'firewall', []) as $item)<span class="badge bg-{{ data_get($item, 'enabled') ? 'success' : 'danger' }} me-1">{{ data_get($item, 'profile', 'Profile') }} {{ data_get($item, 'enabled') ? 'On' : 'Off' }}</span>@empty<div class="text-muted">Not reported</div>@endforelse</div>
        <div><div class="text-muted small mb-1">BitLocker</div>@forelse(data_get($security, 'bitlocker', []) as $item)<div><span class="fw-semibold">{{ data_get($item, 'volume', 'Volume') }}</span> - {{ data_get($item, 'protection', 'Unknown') }}</div>@empty<div class="text-muted">Not reported</div>@endforelse</div>
    </div></div></div>
</div>

@if(data_get($hardware, 'disks'))
<div class="table-card mb-3"><div class="card-header"><span class="fw-semibold">Storage</span></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Drive</th><th>Capacity</th><th>Used</th><th>Free</th><th>Usage</th></tr></thead><tbody>@foreach(data_get($hardware, 'disks', []) as $disk)@php $capacity=(float)data_get($disk,'capacity_bytes',0); $free=(float)data_get($disk,'free_bytes',0); $used=max(0,$capacity-$free); $percent=$capacity>0?round($used/$capacity*100):0; @endphp<tr><td class="ps-4 fw-bold">{{ data_get($disk, 'name') }}</td><td>{{ number_format($capacity/1073741824,1) }} GB</td><td>{{ number_format($used/1073741824,1) }} GB</td><td>{{ number_format($free/1073741824,1) }} GB</td><td><div class="progress" style="height:6px;width:130px"><div class="progress-bar {{ $percent>90?'bg-danger':($percent>75?'bg-warning':'bg-primary') }}" style="width:{{ $percent }}%"></div></div><div class="text-muted small">{{ $percent }}%</div></td></tr>@endforeach</tbody></table></div></div>
@endif

<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Software Inventory</span><span class="badge bg-light text-dark">{{ $discoveries->total() }}</span></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th class="ps-4">Software</th><th>Version</th><th>Mapped Product</th><th>Last Used</th><th>Usage</th><th>Status</th></tr></thead><tbody>
    @forelse($discoveries as $item)<tr><td class="ps-4"><div class="fw-bold">{{ $item->raw_name }}</div><div class="text-muted small">{{ $item->raw_publisher ?: 'Unknown publisher' }}</div></td><td>{{ $item->raw_version ?: '-' }}</td><td>{{ $item->software?->name ?? 'Not mapped' }}</td><td>{{ $item->last_used_date?->format('d M Y') ?? 'No usage recorded' }}</td><td><div>{{ $item->usage_count ?? 0 }} launches</div><div class="text-muted small">{{ $item->total_runtime_minutes ?? 0 }} sampled minutes</div></td><td><span class="badge bg-{{ $item->is_installed ? 'success' : 'secondary' }}">{{ $item->is_installed ? 'Installed' : 'Removed' }}</span></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted py-4">No software inventory has been received.</td></tr>@endforelse
    </tbody></table></div>
    @if($discoveries->hasPages())<div class="p-3 border-top">{{ $discoveries->links() }}</div>@endif
</div>

@if(auth()->user()->hasPermission('endpoint.device.control'))
<div class="modal fade" id="restartEndpointModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.agent-sources.commands.restart', $deviceAgent) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Schedule Device Restart</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Delay</label><select name="delay_minutes" class="form-select" required><option value="5">5 minutes</option><option value="10">10 minutes</option><option value="15">15 minutes</option><option value="30">30 minutes</option><option value="60">60 minutes</option></select></div>
                <div><label class="form-label">Employee Message</label><textarea name="message" class="form-control" rows="3" maxlength="180">This device will restart to complete an administrator-requested operation.</textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-warning"><i class="bi bi-arrow-clockwise me-1"></i>Queue Restart</button></div>
        </form>
    </div>
</div>
@endif
@endsection
