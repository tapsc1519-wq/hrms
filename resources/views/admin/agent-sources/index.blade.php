@extends('layouts.app')
@section('title', 'Device Agent Sources')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div><h4>Device Agent Sources</h4><p>Monitor device inventory health, matching, agent versions, and secure access.</p></div>
    <div class="d-flex gap-2 flex-wrap"><a href="{{ route('admin.agent-sources.windows-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-windows me-1"></i>Download Windows Installer</a><a href="{{ route('admin.agent-sources.windows-package') }}" class="btn btn-outline-secondary btn-sm" title="Advanced PowerShell package"><i class="bi bi-file-zip me-1"></i>PowerShell Package</a><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTokenModal"><i class="bi bi-key me-1"></i>Create Enrollment Token</button></div>
</div>

@if(session('new_agent_token'))
<div class="alert alert-warning">
    <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Copy this enrollment token now. It cannot be displayed again.</div>
    <div class="input-group"><input id="newAgentToken" type="text" class="form-control font-monospace" value="{{ session('new_agent_token') }}" readonly><button type="button" class="btn btn-outline-dark" onclick="navigator.clipboard.writeText(document.getElementById('newAgentToken').value)"><i class="bi bi-copy me-1"></i>Copy</button></div>
</div>
@endif

<div class="row g-3 mb-3">
    @foreach([
        ['Enrolled Devices', $stats['devices'], 'grad-blue', 'Known agent installations'],
        ['Healthy', $stats['healthy'], 'grad-green', 'Seen within 24 hours'],
        ['Needs Attention', $stats['stale'] + $stats['offline'], 'grad-orange', $stats['stale'].' stale, '.$stats['offline'].' offline'],
        ['Not Fully Linked', $stats['unlinked'], 'grad-purple', 'Asset or employee missing'],
    ] as [$label, $value, $color, $sub])
    <div class="col-sm-6 col-xl-3"><div class="stat-card-gradient {{ $color }}"><div class="card-body"><div class="stat-label">{{ $label }}</div><div class="stat-number">{{ $value }}</div><div class="stat-sub">{{ $sub }}</div></div></div></div>
    @endforeach
</div>

<div class="table-card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-semibold">Fleet Filters</span>
        <div class="small text-muted">Current agent: <span class="fw-semibold text-dark">v{{ $currentVersion }}</span> · {{ $stats['outdated'] }} outdated or unknown</div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3"><label class="form-label">Search</label><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Device, serial, asset or employee"></div>
            <div class="col-sm-6 col-lg-2"><label class="form-label">Health</label><select name="health" class="form-select"><option value="">All health states</option><option value="healthy" @selected(request('health')==='healthy')>Healthy</option><option value="stale" @selected(request('health')==='stale')>Stale</option><option value="offline" @selected(request('health')==='offline')>Offline</option></select></div>
            <div class="col-sm-6 col-lg-2"><label class="form-label">Linking</label><select name="linking" class="form-select"><option value="">All linking states</option><option value="fully_linked" @selected(request('linking')==='fully_linked')>Fully linked</option><option value="asset_missing" @selected(request('linking')==='asset_missing')>Asset missing</option><option value="employee_missing" @selected(request('linking')==='employee_missing')>Employee missing</option></select></div>
            <div class="col-sm-6 col-lg-2"><label class="form-label">Device Access</label><select name="access" class="form-select"><option value="">All access states</option><option value="active" @selected(request('access')==='active')>Active</option><option value="revoked" @selected(request('access')==='revoked')>Revoked</option><option value="pending" @selected(request('access')==='pending')>Enrollment pending</option></select></div>
            <div class="col-sm-6 col-lg-1"><label class="form-label">Version</label><select name="version" class="form-select"><option value="">All</option>@foreach($versions as $version)<option value="{{ $version }}" @selected(request('version')===$version)>v{{ $version }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-lg-1"><label class="form-label">Rows</label><select name="per_page" class="form-select">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>@endforeach</select></div>
            <div class="col-auto"><button class="btn btn-primary" title="Apply filters"><i class="bi bi-funnel"></i></button></div>
            @if(request()->hasAny(['search','health','linking','access','version','per_page']))<div class="col-auto"><a href="{{ route('admin.agent-sources.index') }}" class="btn btn-outline-secondary">Clear</a></div>@endif
        </form>
    </div>
</div>

<form id="bulkRefreshForm" method="POST" action="{{ route('admin.agent-sources.commands.inventory-refresh.bulk') }}">@csrf</form>
<div class="table-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><span class="fw-semibold">Enrolled Devices</span><span class="badge bg-light text-dark ms-2">{{ $devices->total() }}</span></div>
        <button id="bulkRefreshButton" form="bulkRefreshForm" class="btn btn-outline-primary btn-sm" disabled><i class="bi bi-arrow-repeat me-1"></i>Refresh Selected <span id="selectedDeviceCount"></span></button>
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th class="ps-4" style="width:44px"><input id="selectPageDevices" class="form-check-input" type="checkbox" title="Select this page"></th><th>Device</th><th>Asset / Employee</th><th>Operating System</th><th>Agent</th><th>Software</th><th>Last Seen</th><th>Health</th><th>Access</th></tr></thead><tbody>
    @forelse($devices as $device)
    @php
        $healthBadge = $device->health_status === 'healthy' ? 'success' : ($device->health_status === 'stale' ? 'warning' : 'danger');
        $accessLabel = $device->credential?->is_active ? 'Active' : ($device->credential ? 'Revoked' : 'Pending');
        $accessBadge = $device->credential?->is_active ? 'success' : ($device->credential ? 'danger' : 'warning');
    @endphp
    <tr><td class="ps-4"><input class="form-check-input device-selector" form="bulkRefreshForm" type="checkbox" name="device_ids[]" value="{{ $device->id }}" aria-label="Select {{ $device->hostname }}"></td><td><a href="{{ route('admin.agent-sources.show', $device) }}" class="fw-bold text-decoration-none">{{ $device->hostname }}</a><div class="text-muted small">{{ $device->serial_number ?: $device->device_uuid }}</div></td><td><div class="{{ $device->asset ? '' : 'text-warning' }}">{{ $device->asset?->asset_tag ?? 'Asset not matched' }}</div><div class="small {{ $device->user ? 'text-muted' : 'text-warning' }}">{{ $device->user?->name ?? 'Employee not matched' }}</div></td><td><div>{{ $device->os_name ?: 'Unknown OS' }}</div><div class="text-muted small">{{ trim(($device->os_version ?? '').' '.($device->architecture ?? '')) ?: 'No version details' }}</div></td><td><span class="{{ $device->agent_version === $currentVersion ? '' : 'text-warning fw-semibold' }}">v{{ $device->agent_version ?: 'Unknown' }}</span></td><td><span class="badge bg-light text-dark">{{ $device->discoveries_count }}</span></td><td><div>{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</div><div class="text-muted small">{{ $device->last_inventory_at?->format('d M Y, H:i') ?? 'No inventory' }}</div></td><td><span class="badge bg-{{ $healthBadge }}">{{ ucfirst($device->health_status) }}</span></td><td><span class="badge bg-{{ $accessBadge }}">{{ $accessLabel }}</span></td></tr>
    @empty<tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-router fs-1 d-block mb-2 opacity-25"></i>{{ request()->hasAny(['search','health','linking','access','version']) ? 'No devices match these filters.' : 'No device agents have checked in yet.' }}</td></tr>@endforelse
    </tbody></table></div>
    @if($devices->hasPages())<div class="p-3 border-top">{{ $devices->links() }}</div>@endif
</div>

<details class="table-card mb-4" @if(session('new_agent_token')) open @endif>
    <summary class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer"><span class="fw-semibold"><i class="bi bi-gear me-1"></i>Deployment Setup</span><span class="small text-muted">{{ $stats['active_tokens'] }} active enrollment {{ Str::plural('token', $stats['active_tokens']) }}</span></summary>
    <div class="card-body border-top"><div class="row g-3"><div class="col-lg-7"><label class="form-label">Inventory API Endpoint</label><div class="input-group"><input id="agentEndpoint" type="text" class="form-control font-monospace" value="{{ url('/api/v1/agent/check-in') }}" readonly><button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('agentEndpoint').value)" title="Copy endpoint"><i class="bi bi-copy"></i></button></div></div><div class="col-lg-5"><label class="form-label">Authentication</label><div class="form-control bg-light text-muted">Enrollment token, then a unique device key</div></div></div></div>
    <div class="table-responsive border-top"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Enrollment Token</th><th>Prefix</th><th>Created</th><th>Last Used</th><th>Expiry</th><th>Status</th><th class="text-end pe-4">Action</th></tr></thead><tbody>
    @forelse($tokens as $token)<tr><td class="ps-4"><div class="fw-bold">{{ $token->name }}</div><div class="text-muted small">by {{ $token->createdBy?->name ?? 'Unknown user' }}</div></td><td class="font-monospace">{{ $token->token_prefix }}...</td><td>{{ $token->created_at->format('d M Y') }}</td><td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td><td>{{ $token->expires_at?->format('d M Y') ?? 'No expiry' }}</td><td><span class="badge bg-{{ $token->is_active ? 'success' : 'secondary' }}">{{ $token->is_active ? 'Active' : ($token->revoked_at ? 'Revoked' : 'Expired') }}</span></td><td class="text-end pe-4">@if($token->is_active)<form method="POST" action="{{ route('admin.agent-sources.tokens.revoke', $token) }}" onsubmit="return confirm('Revoke this enrollment token? It will no longer enroll devices.')">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle me-1"></i>Revoke</button></form>@else<span class="text-muted small">No action</span>@endif</td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">No enrollment tokens have been created.</td></tr>@endforelse
    </tbody></table></div>
</details>

<div class="modal fade" id="createTokenModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('admin.agent-sources.tokens.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Create Enrollment Token</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i>Use this token only while installing agents. Each enrolled device receives its own API key automatically.</div><div class="mb-3"><label class="form-label">Token Name</label><input type="text" name="name" class="form-control" required maxlength="100" placeholder="Example: Production Windows Devices"></div><div><label class="form-label">Expiry Date</label><input type="date" name="expires_at" class="form-control" min="{{ today()->addDay()->toDateString() }}"><div class="form-text">Use a short expiry for rollout. Revoking it will not disconnect devices already enrolled.</div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="bi bi-key me-1"></i>Create Enrollment Token</button></div></form></div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectPageDevices');
    const selectors = Array.from(document.querySelectorAll('.device-selector'));
    const button = document.getElementById('bulkRefreshButton');
    const count = document.getElementById('selectedDeviceCount');
    function updateSelection() {
        const selected = selectors.filter(item => item.checked).length;
        button.disabled = selected === 0;
        count.textContent = selected ? '(' + selected + ')' : '';
        selectAll.checked = selectors.length > 0 && selected === selectors.length;
        selectAll.indeterminate = selected > 0 && selected < selectors.length;
    }
    selectAll.addEventListener('change', function () { selectors.forEach(item => item.checked = selectAll.checked); updateSelection(); });
    selectors.forEach(item => item.addEventListener('change', updateSelection));
    updateSelection();
});
</script>
@endpush
