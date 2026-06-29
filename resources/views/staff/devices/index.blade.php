@extends('layouts.app')
@section('title', 'My Device')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-laptop me-2 text-primary"></i>My Device</h4>
        <p>Install the company device agent so IT can keep your assigned device inventory current.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#downloadAgentModal">
            <i class="bi bi-download me-1"></i>Download Agent
        </button>
        <form method="POST" action="{{ route('staff.devices.tokens.store') }}">
            @csrf
            <button class="btn btn-outline-primary btn-sm">
                <i class="bi bi-key me-1"></i>Create Setup Token
            </button>
        </form>
    </div>
</div>

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
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="{{ route('staff.devices.windows-installer') }}" class="border rounded-3 p-3 h-100 d-flex gap-3 text-decoration-none text-dark">
                            <div class="fs-3 text-primary"><i class="bi bi-windows"></i></div>
                            <div>
                                <div class="fw-semibold">Windows</div>
                                <div class="small text-muted">Download the Windows installer for your company PC.</div>
                                <span class="badge bg-primary mt-2">Download EXE</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('staff.devices.unix-installer') }}" class="border rounded-3 p-3 h-100 d-flex gap-3 text-decoration-none text-dark">
                            <div class="fs-3 text-primary"><i class="bi bi-apple"></i></div>
                            <div>
                                <div class="fw-semibold">macOS / Linux</div>
                                <div class="small text-muted">Download the self-contained shell installer.</div>
                                <span class="badge bg-primary mt-2">Download SH</span>
                            </div>
                        </a>
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
                        ['Download installer', 'Choose the Windows installer or the macOS/Linux installer for your company device.'],
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
                    <div class="form-text">Current Windows agent version: v{{ $currentVersion }}. macOS/Linux installer uses the same API endpoint and setup token.</div>
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
        <span class="fw-semibold">My Reported Devices</span>
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
