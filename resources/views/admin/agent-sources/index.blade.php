@extends('layouts.app')
@section('title', 'Endpoint Management')

@section('content')
@php $canManageAgents = auth()->user()->hasPermission('software.agents.manage'); @endphp
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2" data-tour="endpoint-list-header">
    <div><h4>Endpoint Management</h4><p>Monitor managed computers, deploy approved software, and review signed device actions.</p></div>
    @if(auth()->user()->hasPermission('software.agents.manage'))<div class="d-flex gap-2 flex-wrap" data-tour="endpoint-installer-actions"><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#downloadAgentModal"><i class="bi bi-download me-1"></i>Download Agent</button><a href="{{ route('admin.agent-sources.windows-package') }}" class="btn btn-outline-secondary btn-sm" title="Advanced PowerShell package"><i class="bi bi-file-zip me-1"></i>PowerShell Package</a><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTokenModal"><i class="bi bi-key me-1"></i>Create Enrollment Token</button></div>@endif
</div>

@if(session('new_agent_token'))
<div class="alert alert-warning">
    <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Copy this enrollment token now. It cannot be displayed again.</div>
    <div class="input-group"><input id="newAgentToken" type="text" class="form-control font-monospace" value="{{ session('new_agent_token') }}" readonly><button type="button" class="btn btn-outline-dark" onclick="navigator.clipboard.writeText(document.getElementById('newAgentToken').value)"><i class="bi bi-copy me-1"></i>Copy</button></div>
</div>
@endif

<div class="row g-3 mb-3" data-tour="endpoint-health-summary">
    @foreach([
        ['Enrolled Devices', $stats['devices'], 'grad-blue', 'Known agent installations'],
        ['Healthy', $stats['healthy'], 'grad-green', 'Seen within 24 hours'],
        ['Needs Attention', $stats['stale'] + $stats['offline'], 'grad-orange', $stats['stale'].' stale, '.$stats['offline'].' offline'],
        ['Not Fully Linked', $stats['unlinked'], 'grad-purple', 'Asset or employee missing'],
    ] as [$label, $value, $color, $sub])
    <div class="col-sm-6 col-xl-3"><div class="stat-card-gradient {{ $color }}"><div class="card-body"><div class="stat-label">{{ $label }}</div><div class="stat-number">{{ $value }}</div><div class="stat-sub">{{ $sub }}</div></div></div></div>
    @endforeach
</div>

<div class="table-card mb-3" data-tour="endpoint-filters">
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

@if($canManageAgents)<form id="bulkRefreshForm" method="POST" action="{{ route('admin.agent-sources.commands.inventory-refresh.bulk') }}">@csrf</form>@endif
<div class="table-card mb-4" data-tour="endpoint-device-table">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><span class="fw-semibold">Enrolled Devices</span><span class="badge bg-light text-dark ms-2">{{ $devices->total() }}</span></div>
        @if($canManageAgents)<button id="bulkRefreshButton" form="bulkRefreshForm" class="btn btn-outline-primary btn-sm" disabled data-tour="endpoint-bulk-refresh"><i class="bi bi-arrow-repeat me-1"></i>Refresh Selected <span id="selectedDeviceCount"></span></button>@endif
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th class="ps-4" style="width:44px"><input id="selectPageDevices" class="form-check-input" type="checkbox" title="Select this page"></th><th>Device</th><th>Asset / Employee</th><th>Operating System</th><th>Agent</th><th>Software</th><th>Last Seen</th><th>Health</th><th>Access</th><th class="text-end pe-4">Action</th></tr></thead><tbody>
    @forelse($devices as $device)
    @php
        $healthBadge = $device->health_status === 'healthy' ? 'success' : ($device->health_status === 'stale' ? 'warning' : 'danger');
        $accessLabel = $device->credential?->is_active ? 'Active' : ($device->credential ? 'Revoked' : 'Pending');
        $accessBadge = $device->credential?->is_active ? 'success' : ($device->credential ? 'danger' : 'warning');
    @endphp
    <tr><td class="ps-4"><input class="form-check-input device-selector" form="bulkRefreshForm" type="checkbox" name="device_ids[]" value="{{ $device->id }}" aria-label="Select {{ $device->hostname }}"></td><td><a href="{{ route('admin.agent-sources.show', $device) }}" class="fw-bold text-decoration-none">{{ $device->hostname }}</a><div class="text-muted small">{{ $device->serial_number ?: $device->device_uuid }}</div></td><td><div class="{{ $device->asset ? '' : 'text-warning' }}">{{ $device->asset?->asset_tag ?? 'Asset not matched' }}</div><div class="small {{ $device->user ? 'text-muted' : 'text-warning' }}">{{ $device->user?->name ?? 'Employee not matched' }}</div></td><td><div>{{ $device->os_name ?: 'Unknown OS' }}</div><div class="text-muted small">{{ trim(($device->os_version ?? '').' '.($device->architecture ?? '')) ?: 'No version details' }}</div></td><td><span class="{{ $device->agent_version === $currentVersion ? '' : 'text-warning fw-semibold' }}">v{{ $device->agent_version ?: 'Unknown' }}</span></td><td><span class="badge bg-light text-dark">{{ $device->discoveries_count }}</span></td><td><div>{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</div><div class="text-muted small">{{ $device->last_inventory_at?->format('d M Y, H:i') ?? 'No inventory' }}</div></td><td><span class="badge bg-{{ $healthBadge }}">{{ ucfirst($device->health_status) }}</span></td><td><span class="badge bg-{{ $accessBadge }}">{{ $accessLabel }}</span></td><td class="text-end pe-4">@if($canManageAgents)<button class="btn btn-sm {{ $device->asset && $device->user ? 'btn-outline-secondary' : 'btn-warning' }}" data-bs-toggle="modal" data-bs-target="#linkDevice{{ $device->id }}"><i class="bi bi-link-45deg me-1"></i>Link</button>@else<span class="text-muted small">View only</span>@endif</td></tr>
    @empty<tr><td colspan="10" class="text-center text-muted py-5"><i class="bi bi-router fs-1 d-block mb-2 opacity-25"></i>{{ request()->hasAny(['search','health','linking','access','version']) ? 'No devices match these filters.' : 'No device agents have checked in yet.' }}</td></tr>@endforelse
    </tbody></table></div>
    @if($canManageAgents)
        @foreach($devices as $device)
        <div class="modal fade" id="linkDevice{{ $device->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('admin.agent-sources.linking.update', $device) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><div><h5 class="modal-title mb-0">Link Endpoint</h5><small class="text-muted">{{ $device->hostname }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Asset</label><select name="asset_id" class="form-select"><option value="">No asset linked</option>@foreach($assets as $asset)<option value="{{ $asset->id }}" @selected($device->asset_id === $asset->id)>{{ $asset->asset_tag }}{{ $asset->name ? ' - '.$asset->name : '' }}</option>@endforeach</select></div><div><label class="form-label">Employee</label><select name="user_id" class="form-select"><option value="">No employee linked</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($device->user_id === $user->id)>{{ $user->name }}{{ $user->employee_id ? ' - '.$user->employee_id : '' }}</option>@endforeach</select></div><div class="alert alert-info small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Correct links improve SAM device coverage, usage attribution, and audit confidence.</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Links</button></div></form></div></div>
        @endforeach
    @endif
    @if($devices->hasPages())<div class="p-3 border-top">{{ $devices->links() }}</div>@endif
</div>

@if($canManageAgents)
<div class="modal fade" id="downloadAgentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Download Device Agent</h5>
                    <small class="text-muted">Select the operating system for this device.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <button type="button" class="border rounded-3 p-3 h-100 w-100 d-flex gap-3 text-start bg-white agent-download-option" data-bs-toggle="collapse" data-bs-target="#adminWindowsInstallGuide" aria-expanded="false" aria-controls="adminWindowsInstallGuide">
                            <div class="fs-3 text-primary"><i class="bi bi-windows"></i></div>
                            <div>
                                <div class="fw-semibold">Windows</div>
                                <div class="small text-muted">Open the guided Windows installer steps before downloading.</div>
                                <span class="badge bg-primary mt-2">Guided EXE Installer</span>
                            </div>
                        </button>
                    </div>
                    <div class="col-md-6">
                        @if($macosPkgAvailable)
                            <a href="{{ route('admin.agent-sources.macos-installer') }}" class="border rounded-3 p-3 h-100 d-flex gap-3 text-decoration-none text-dark agent-download-option">
                                <div class="fs-3 text-primary"><i class="bi bi-apple"></i></div>
                                <div>
                                    <div class="fw-semibold">macOS</div>
                                    <div class="small text-muted">Download the signed package installer for Apple devices.</div>
                                    <span class="badge bg-primary mt-2">Download PKG</span>
                                </div>
                            </a>
                        @else
                            <button type="button" class="border rounded-3 p-3 h-100 w-100 d-flex gap-3 text-start bg-white agent-download-option" data-bs-toggle="collapse" data-bs-target="#adminMacosInstallGuide" aria-expanded="false" aria-controls="adminMacosInstallGuide">
                                <div class="fs-3 text-primary"><i class="bi bi-apple"></i></div>
                                <div>
                                    <div class="fw-semibold">macOS</div>
                                    <div class="small text-muted">Open the guided macOS installer steps before downloading.</div>
                                    <span class="badge bg-primary mt-2">Guided COMMAND Installer</span>
                                </div>
                            </button>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="border rounded-3 p-3 h-100 w-100 d-flex gap-3 text-start bg-white agent-download-option" data-bs-toggle="collapse" data-bs-target="#adminLinuxInstallGuide" aria-expanded="false" aria-controls="adminLinuxInstallGuide">
                            <div class="fs-3 text-primary"><i class="bi bi-ubuntu"></i></div>
                            <div>
                                <div class="fw-semibold">Linux</div>
                                <div class="small text-muted">Open the guided Linux installer steps before downloading.</div>
                                <span class="badge bg-primary mt-2">Guided SH Installer</span>
                            </div>
                        </button>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100 d-flex gap-3 bg-light text-muted">
                            <div class="fs-3"><i class="bi bi-android2"></i></div>
                            <div>
                                <div class="fw-semibold">Android</div>
                                <div class="small">Mobile device agent will be added in a future release.</div>
                                <span class="badge bg-secondary mt-2">Coming soon</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100 d-flex gap-3 bg-light text-muted">
                            <div class="fs-3"><i class="bi bi-phone"></i></div>
                            <div>
                                <div class="fw-semibold">iOS</div>
                                <div class="small">Mobile device agent will be added in a future release.</div>
                                <span class="badge bg-secondary mt-2">Coming soon</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="collapse border rounded-3 p-3 mt-3 bg-light" id="adminWindowsInstallGuide" data-bs-parent="#downloadAgentModal">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-windows fs-4 text-primary"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Windows installer guide</div>
                            <div class="small text-muted">Follow these steps before installing the Windows agent. Windows may ask for administrator approval during setup.</div>
                        </div>
                        <a href="{{ route('admin.agent-sources.windows-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download Windows Installer</a>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">1</span>Create token</div><div class="text-muted">Create an enrollment token first and keep it ready for the installer.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">2</span>Download EXE</div><div class="text-muted">Click Download Windows Installer and save the file on the target computer.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">3</span>Run as administrator</div><div class="text-muted">Double-click the installer. If Windows asks for permission, choose Yes to continue.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">4</span>Enter setup details</div><div class="text-muted">Enter the API endpoint and enrollment token when the installer asks.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">5</span>Allow security prompts</div><div class="text-muted">If SmartScreen or antivirus warns about a new internal installer, choose More info and Run anyway only if it came from this portal.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">6</span>Confirm check-in</div><div class="text-muted">After success, the PC appears in Enrolled Devices and starts reporting inventory.</div></div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('admin.agent-sources.windows-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download Windows Installer</a>
                    </div>
                </div>
                <div class="collapse border rounded-3 p-3 mt-3 bg-light" id="adminLinuxInstallGuide" data-bs-parent="#downloadAgentModal">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-ubuntu fs-4 text-primary"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Linux installer guide</div>
                            <div class="small text-muted">Follow these steps before installing the Linux shell agent. Root permission is required to install the background service.</div>
                        </div>
                        <a href="{{ route('admin.agent-sources.unix-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download Linux Installer</a>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">1</span>Create token</div><div class="text-muted">Create an enrollment token first and keep it ready for the installer prompt.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">2</span>Download SH</div><div class="text-muted">Click Download Linux Installer and save <span class="font-monospace">OpsBridge-Agent-Installer.sh</span>.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">3</span>Allow execution</div><div class="text-muted mb-2">Open Terminal in the download folder and run:</div><div class="font-monospace bg-white border rounded p-2">chmod +x OpsBridge-Agent-Installer*.sh</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">4</span>Run installer</div><div class="text-muted mb-2">Run it with root permission:</div><div class="font-monospace bg-white border rounded p-2">sudo ./OpsBridge-Agent-Installer*.sh</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">5</span>Enter setup details</div><div class="text-muted">Paste the API endpoint and enrollment token when Terminal asks.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">6</span>Confirm check-in</div><div class="text-muted">After success, the Linux computer appears in Enrolled Devices. If needed, run <span class="font-monospace">sudo python3 /opt/opsbridge-agent/opsbridge_agent.py --once</span>.</div></div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('admin.agent-sources.unix-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download Linux Installer</a>
                    </div>
                </div>
                @unless($macosPkgAvailable)
                <div class="collapse border rounded-3 p-3 mt-3 bg-light" id="adminMacosInstallGuide" data-bs-parent="#downloadAgentModal">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-apple fs-4 text-primary"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">macOS installer guide</div>
                            <div class="small text-muted">Follow these steps before installing the macOS command installer. macOS may block the file once because it is not Apple-signed.</div>
                        </div>
                        <a href="{{ route('admin.agent-sources.macos-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download macOS Installer</a>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">1</span>Create token</div>
                            <div class="text-muted">Create an enrollment token first. Keep it ready for the setup window. After enrollment, the Mac receives its own secure API key.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">2</span>Download file</div>
                            <div class="text-muted">Click macOS and save <span class="font-monospace">OpsBridge-Agent-Installer.command</span> in Downloads.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">3</span>If macOS blocks it</div>
                            <div class="text-muted">If the user sees "Apple could not verify" or "cannot be opened," go to System Settings &gt; Privacy &amp; Security and click Open Anyway for the OpsBridge installer.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">4</span>Allow execution</div>
                            <div class="text-muted mb-2">If macOS says the file cannot be executed because of access privileges, open Terminal and run:</div>
                            <div class="font-monospace bg-white border rounded p-2">cd ~/Downloads<br>chmod +x OpsBridge-Agent-Installer*.command</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">5</span>Run installer</div>
                            <div class="text-muted mb-2">Double-click the file again, or run it with admin permission:</div>
                            <div class="font-monospace bg-white border rounded p-2">sudo ./OpsBridge-Agent-Installer*.command</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">6</span>Enter details in setup window</div>
                            <div class="text-muted">Two OpsBridge Agent Setup windows appear: first for the API endpoint, then for the enrollment token. The token field is hidden while typing.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">7</span>Approve admin password</div>
                            <div class="text-muted">macOS asks for the computer admin password. This is required to install the background agent service.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">8</span>Confirm check-in</div>
                            <div class="text-muted">After success, the Mac appears in Enrolled Devices. If needed, run <span class="font-monospace">sudo python3 /opt/opsbridge-agent/opsbridge_agent.py --once</span> to force one check-in.</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('admin.agent-sources.macos-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download macOS Installer</a>
                    </div>
                </div>
                @endunless
                <div class="alert alert-info small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Create an enrollment token before installing the agent on a new device.</div>
            </div>
        </div>
    </div>
</div>

<details class="table-card mb-4" data-tour="endpoint-deployment-setup" @if(session('new_agent_token')) open @endif>
    <summary class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer"><span class="fw-semibold"><i class="bi bi-gear me-1"></i>Deployment Setup</span><span class="small text-muted">{{ $stats['active_tokens'] }} active enrollment {{ Str::plural('token', $stats['active_tokens']) }}</span></summary>
    <div class="card-body border-top"><div class="row g-3"><div class="col-lg-7"><label class="form-label">Inventory API Endpoint</label><div class="input-group"><input id="agentEndpoint" type="text" class="form-control font-monospace" value="{{ url('/api/v1/agent/check-in') }}" readonly><button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('agentEndpoint').value)" title="Copy endpoint"><i class="bi bi-copy"></i></button></div></div><div class="col-lg-5"><label class="form-label">Authentication</label><div class="form-control bg-light text-muted">Enrollment token, then a unique device key</div></div></div></div>
    <div class="table-responsive border-top"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Enrollment Token</th><th>Prefix</th><th>Created</th><th>Last Used</th><th>Expiry</th><th>Status</th><th class="text-end pe-4">Action</th></tr></thead><tbody>
    @forelse($tokens as $token)<tr><td class="ps-4"><div class="fw-bold">{{ $token->name }}</div><div class="text-muted small">by {{ $token->createdBy?->name ?? 'Unknown user' }}</div>@if($token->assignedUser)<div class="small text-primary"><i class="bi bi-person-check me-1"></i>Employee install for {{ $token->assignedUser->name }}</div>@endif</td><td class="font-monospace">{{ $token->token_prefix }}...</td><td>{{ $token->created_at->format('d M Y') }}</td><td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td><td>{{ $token->expires_at?->format('d M Y') ?? 'No expiry' }}</td><td><span class="badge bg-{{ $token->is_active ? 'success' : 'secondary' }}">{{ $token->is_active ? 'Active' : ($token->revoked_at ? 'Revoked' : 'Expired') }}</span></td><td class="text-end pe-4">@if($token->is_active)<form method="POST" action="{{ route('admin.agent-sources.tokens.revoke', $token) }}" onsubmit="return confirm('Revoke this enrollment token? It will no longer enroll devices.')">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle me-1"></i>Revoke</button></form>@else<span class="text-muted small">No action</span>@endif</td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">No enrollment tokens have been created.</td></tr>@endforelse
    </tbody></table></div>
</details>

<div class="modal fade" id="createTokenModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('admin.agent-sources.tokens.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Create Enrollment Token</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i>Use this token only while installing agents. Each enrolled device receives its own API key automatically.</div><div class="mb-3"><label class="form-label">Token Name</label><input type="text" name="name" class="form-control" required maxlength="100" placeholder="Example: Production Windows Devices"></div><div><label class="form-label">Expiry Date</label><input type="date" name="expires_at" class="form-control" min="{{ today()->addDay()->toDateString() }}"><div class="form-text">Use a short expiry for rollout. Revoking it will not disconnect devices already enrolled.</div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="bi bi-key me-1"></i>Create Enrollment Token</button></div></form></div></div>
@endif
@endsection

@if($canManageAgents)
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
@endif
