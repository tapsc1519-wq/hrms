@extends('layouts.app')
@section('title', 'Device Agent')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-shield-check me-2 text-primary"></i>Device Agent</h4>
        <p>Install the company endpoint agent so IT can keep your assigned computer inventory current.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#downloadAgentModal">
            <i class="bi bi-download me-1"></i>Download Agent
        </button>
        <form method="POST" action="{{ route('staff.devices.tokens.store') }}">
            @csrf
            <button class="btn btn-outline-primary btn-sm" @disabled(!$employeeAgentSetupReady)>
                <i class="bi bi-key me-1"></i>Create Setup Token
            </button>
        </form>
    </div>
</div>

@unless($employeeAgentSetupReady)
<div class="alert alert-warning border-0 shadow-sm">
    <div class="fw-bold mb-1"><i class="bi bi-database-exclamation me-1"></i>Employee device setup is pending</div>
    <div class="small">IT must run the latest database migration before setup tokens can be created from this page.</div>
</div>
@endunless

<div class="modal fade" id="downloadAgentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Download Device Agent</h5>
                    <small class="text-muted">Select the operating system for your device.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="border rounded-3 p-3 mb-3 bg-light">
                    <label class="form-label small fw-semibold mb-1">Agent API Endpoint</label>
                    <div class="input-group input-group-sm">
                        <input id="downloadAgentEndpointStaff" type="text" class="form-control font-monospace" value="{{ $agentEndpoint }}" readonly>
                        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('downloadAgentEndpointStaff').value)" title="Copy endpoint"><i class="bi bi-copy"></i></button>
                    </div>
                    <div class="form-text">Use this endpoint in the installer together with your setup token.</div>
                </div>
                <div class="border rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <div class="fw-semibold">Setup token</div>
                            <div class="small text-muted">Create a setup token before downloading. It is shown only once and expires in 24 hours.</div>
                        </div>
                        <form method="POST" action="{{ route('staff.devices.tokens.store') }}">
                            @csrf
                            <button class="btn btn-outline-primary btn-sm" @disabled(!$employeeAgentSetupReady)><i class="bi bi-key me-1"></i>Create Setup Token</button>
                        </form>
                    </div>
                    @if(session('new_agent_token'))
                    <div class="alert alert-warning small mt-3 mb-0">
                        <div class="fw-semibold mb-2">Copy this setup token now.</div>
                        <div class="input-group input-group-sm">
                            <input id="downloadModalNewAgentTokenStaff" type="text" class="form-control font-monospace" value="{{ session('new_agent_token') }}" readonly>
                            <button type="button" class="btn btn-outline-dark" onclick="navigator.clipboard.writeText(document.getElementById('downloadModalNewAgentTokenStaff').value)"><i class="bi bi-copy me-1"></i>Copy</button>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <button type="button" class="border rounded-3 p-3 h-100 w-100 d-flex gap-3 text-start bg-white" data-bs-toggle="collapse" data-bs-target="#staffWindowsInstallGuide" aria-expanded="false" aria-controls="staffWindowsInstallGuide">
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
                            <a href="{{ route('staff.devices.macos-installer') }}" class="border rounded-3 p-3 h-100 d-flex gap-3 text-decoration-none text-dark">
                                <div class="fs-3 text-primary"><i class="bi bi-apple"></i></div>
                                <div>
                                    <div class="fw-semibold">macOS</div>
                                    <div class="small text-muted">Download the signed package installer for your Apple device.</div>
                                    <span class="badge bg-primary mt-2">Download PKG</span>
                                </div>
                            </a>
                        @else
                            <button type="button" class="border rounded-3 p-3 h-100 w-100 d-flex gap-3 text-start bg-white" data-bs-toggle="collapse" data-bs-target="#staffMacosInstallGuide" aria-expanded="false" aria-controls="staffMacosInstallGuide">
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
                        <button type="button" class="border rounded-3 p-3 h-100 w-100 d-flex gap-3 text-start bg-white" data-bs-toggle="collapse" data-bs-target="#staffLinuxInstallGuide" aria-expanded="false" aria-controls="staffLinuxInstallGuide">
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
                <div class="collapse border rounded-3 p-3 mt-3 bg-light" id="staffWindowsInstallGuide" data-bs-parent="#downloadAgentModal">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-windows fs-4 text-primary"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Windows installer guide</div>
                            <div class="small text-muted">Follow these steps before installing the Windows agent. Windows may ask for administrator approval during setup.</div>
                        </div>
                        <a href="{{ route('staff.devices.windows-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download Windows Installer</a>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">1</span>Create setup token</div><div class="text-muted">Click Create Setup Token first. Copy it when it appears because it is shown only once.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">2</span>Download EXE</div><div class="text-muted">Click Download Windows Installer and save the file on your company PC.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">3</span>Run as administrator</div><div class="text-muted">Double-click the installer. If Windows asks for permission, choose Yes to continue.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">4</span>Enter setup details</div><div class="text-muted">Enter the API endpoint shown above and your setup token when the installer asks.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">5</span>Allow security prompts</div><div class="text-muted">If SmartScreen or antivirus warns about a new company installer, choose More info and Run anyway only if it came from this portal.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">6</span>Confirm check-in</div><div class="text-muted">After success, your PC appears in My Enrolled Devices and starts reporting inventory.</div></div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('staff.devices.windows-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download Windows Installer</a>
                    </div>
                </div>
                <div class="collapse border rounded-3 p-3 mt-3 bg-light" id="staffLinuxInstallGuide" data-bs-parent="#downloadAgentModal">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-ubuntu fs-4 text-primary"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Linux installer guide</div>
                            <div class="small text-muted">Follow these steps before installing the Linux shell agent. Root permission is required to install the background service.</div>
                        </div>
                        <a href="{{ route('staff.devices.unix-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download Linux Installer</a>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">1</span>Create setup token</div><div class="text-muted">Click Create Setup Token first. Copy it when it appears because it is shown only once.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">2</span>Download SH</div><div class="text-muted">Click Download Linux Installer and save <span class="font-monospace">OpsBridge-Agent-Installer.sh</span>.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">3</span>Allow execution</div><div class="text-muted mb-2">Open Terminal in the download folder and run:</div><div class="font-monospace bg-white border rounded p-2">chmod +x OpsBridge-Agent-Installer*.sh</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">4</span>Run installer</div><div class="text-muted mb-2">Run it with root permission:</div><div class="font-monospace bg-white border rounded p-2">sudo ./OpsBridge-Agent-Installer*.sh</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">5</span>Enter setup details</div><div class="text-muted">Paste the API endpoint shown above and your setup token when Terminal asks.</div></div>
                        <div class="col-md-6"><div class="fw-semibold mb-1"><span class="badge bg-primary me-2">6</span>Confirm check-in</div><div class="text-muted">After success, your Linux computer appears in My Enrolled Devices. If IT asks, run <span class="font-monospace">sudo python3 /opt/opsbridge-agent/opsbridge_agent.py --once</span>.</div></div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('staff.devices.unix-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download Linux Installer</a>
                    </div>
                </div>
                @unless($macosPkgAvailable)
                <div class="collapse border rounded-3 p-3 mt-3 bg-light" id="staffMacosInstallGuide" data-bs-parent="#downloadAgentModal">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-apple fs-4 text-primary"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">macOS installer guide</div>
                            <div class="small text-muted">Follow these steps before installing the macOS command installer. macOS may block the file once because it is not Apple-signed.</div>
                        </div>
                        <a href="{{ route('staff.devices.macos-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download macOS Installer</a>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">1</span>Create setup token</div>
                            <div class="text-muted">Click Create Setup Token first. Copy it when it appears because it is shown only once.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">2</span>Download file</div>
                            <div class="text-muted">Click macOS and save <span class="font-monospace">OpsBridge-Agent-Installer.command</span> in Downloads.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">3</span>If macOS blocks it</div>
                            <div class="text-muted">If you see "Apple could not verify" or "cannot be opened," go to System Settings &gt; Privacy &amp; Security and click Open Anyway for the OpsBridge installer.</div>
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
                            <div class="text-muted">Two OpsBridge Agent Setup windows appear: first for the API endpoint shown above, then for your setup token. The token field is hidden while typing.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">7</span>Approve admin password</div>
                            <div class="text-muted">macOS asks for your computer admin password. This is required to install the background agent service.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">8</span>Confirm check-in</div>
                            <div class="text-muted">After success, your Mac appears in My Enrolled Devices. If IT asks, run <span class="font-monospace">sudo python3 /opt/opsbridge-agent/opsbridge_agent.py --once</span> once.</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('staff.devices.macos-installer') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download macOS Installer</a>
                    </div>
                </div>
                @endunless
                <div class="alert alert-info small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Create a setup token before installing the agent on your device.</div>
            </div>
        </div>
    </div>
</div>

@if(session('new_agent_token'))
<div class="alert alert-warning border-0 shadow-sm">
    <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Copy this setup token now. It is shown only once and expires in 24 hours.</div>
    <div class="input-group">
        <input id="newAgentToken" type="text" class="form-control font-monospace" value="{{ session('new_agent_token') }}" readonly>
        <button type="button" class="btn btn-outline-dark" onclick="navigator.clipboard.writeText(document.getElementById('newAgentToken').value)">
            <i class="bi bi-copy me-1"></i>Copy
        </button>
    </div>
</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="table-card h-100">
            <div class="card-header"><span class="fw-semibold">Install Device Agent</span></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach([
                        ['Create setup token', 'Generate a token from this page. It is tied to your employee account and expires in 24 hours.'],
                        ['Download installer', 'Choose Windows, macOS, or Linux from the Download Agent selector.'],
                        ['Paste endpoint and token', 'Use the API endpoint below and paste your one-time setup token during installation.'],
                        ['Wait for check-in', 'After the first successful check-in, your device appears here and in admin Endpoint Management.'],
                    ] as [$title, $description])
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="fw-semibold mb-1"><span class="badge bg-primary me-2">{{ $loop->iteration }}</span>{{ $title }}</div>
                            <div class="text-muted small">{{ $description }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    <label class="form-label">Agent API Endpoint</label>
                    <div class="input-group">
                        <input id="agentEndpoint" type="text" class="form-control font-monospace" value="{{ $agentEndpoint }}" readonly>
                        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('agentEndpoint').value)" title="Copy endpoint">
                            <i class="bi bi-copy"></i>
                        </button>
                    </div>
                    <div class="form-text">Current Windows agent version: v{{ $currentVersion }}. macOS and Linux installers use the same API endpoint and setup token.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="table-card h-100">
            <div class="card-header"><span class="fw-semibold">Setup Tokens</span></div>
            <div class="card-body">
                @forelse($tokens as $token)
                    <div class="d-flex justify-content-between align-items-start gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <div class="fw-semibold">{{ $token->name }}</div>
                            <div class="small text-muted font-monospace">{{ $token->token_prefix }}...</div>
                            <div class="small text-muted">Expires {{ $token->expires_at?->diffForHumans() ?? 'never' }}</div>
                        </div>
                        <span class="badge bg-{{ $token->is_active ? 'success' : 'secondary' }}">{{ $token->is_active ? 'Active' : 'Closed' }}</span>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-key fs-2 d-block mb-2 opacity-50"></i>
                        No setup tokens created yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Reported Agent Devices</span>
        <span class="badge bg-light text-dark">{{ $devices->count() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Device</th><th>Operating System</th><th>Agent</th><th>Last Seen</th><th>Inventory</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($devices as $device)
                @php $healthBadge = $device->health_status === 'healthy' ? 'success' : ($device->health_status === 'stale' ? 'warning' : 'danger'); @endphp
                <tr>
                    <td class="ps-4"><div class="fw-bold">{{ $device->hostname }}</div><div class="text-muted small">{{ $device->serial_number ?: $device->device_uuid }}</div></td>
                    <td><div>{{ $device->os_name ?: 'Unknown OS' }}</div><div class="text-muted small">{{ trim(($device->os_version ?? '').' '.($device->architecture ?? '')) ?: 'No version details' }}</div></td>
                    <td>v{{ $device->agent_version ?: 'Unknown' }}</td>
                    <td>{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                    <td>{{ $device->last_inventory_at?->format('d M Y, H:i') ?? 'No inventory yet' }}</td>
                    <td><span class="badge bg-{{ $healthBadge }}">{{ ucfirst($device->health_status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-laptop fs-1 d-block mb-2 opacity-25"></i>No device has checked in from your account yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@if(session('new_agent_token'))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const downloadModal = document.getElementById('downloadAgentModal');
    if (downloadModal && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(downloadModal).show();
    }
});
</script>
@endpush
@endif
