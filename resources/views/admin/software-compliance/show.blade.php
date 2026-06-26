@extends('layouts.app')
@section('title', 'Compliance Details')

@section('content')
@php
    $riskBadge = $row['risk_level'] === 'high' ? 'danger' : ($row['risk_level'] === 'medium' ? 'warning' : 'success');
@endphp

<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4>{{ $software->name }} Compliance</h4>
        <p>
            {{ $software->vendor ?: 'Unknown vendor' }}
            @if($software->edition) &middot; {{ $software->edition }} @endif
            &middot; {{ $software->license_metric_label }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.software-compliance.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        <a href="{{ route('admin.software.show', $software) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-display me-1"></i>Catalog
        </a>
        <a href="{{ route('admin.software-licenses.index', ['search' => $software->name]) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-key me-1"></i>Licenses
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient grad-blue"><div class="card-body">
            <div class="stat-label">Required</div>
            <div class="stat-number">{{ $row['required_seats'] }}</div>
            <div class="stat-sub">{{ $row['installed_count'] }} installs discovered</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient grad-green"><div class="card-body">
            <div class="stat-label">Purchased</div>
            <div class="stat-number">{{ $row['purchased_seats'] }}</div>
            <div class="stat-sub">{{ $row['allocated_count'] }} seats allocated</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient grad-orange"><div class="card-body">
            <div class="stat-label">Mismatch</div>
            <div class="stat-number">{{ $row['allocation_mismatch_count'] }}</div>
            <div class="stat-sub">discovered users not allocated</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient grad-red"><div class="card-body">
            <div class="stat-label">Risk</div>
            <div class="stat-number">{{ $row['risk_score'] }}</div>
            <div class="stat-sub">{{ ucfirst($row['risk_level']) }} risk score</div>
        </div></div>
    </div>
</div>

<div class="table-card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <span class="badge bg-{{ $row['status_meta']['badge'] }} me-2">{{ $row['status_meta']['label'] }}</span>
            <span class="badge bg-{{ $riskBadge }}">{{ ucfirst($row['risk_level']) }} Risk</span>
        </div>
        <div class="text-muted small">
            Missing seats: <strong class="text-dark">{{ $row['missing_seats'] }}</strong>
            &middot; Estimated exposure: <strong class="text-dark">Rs {{ number_format($row['financial_exposure'], 2) }}</strong>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-5">
        <div class="form-card h-100">
            <div class="form-card-header">
                <div class="icon-wrap icon-green"><i class="bi bi-person-check"></i></div>
                Available License Pools
            </div>
            <div class="form-card-body">
                @if($missingUsers->total() === 0)
                <div class="text-center text-muted py-4">
                    <i class="bi bi-check2-circle fs-2 d-block mb-2 opacity-50"></i>
                    No discovered users are missing allocation.
                </div>
                @elseif($availableLicenses->isNotEmpty())
                    <div class="text-muted small mb-3">
                        Use the Allocate button beside each missing employee. This avoids scrolling through a large employee dropdown in bigger organizations.
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($availableLicenses as $license)
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold">{{ $license->license_type_label }}</div>
                                    <div class="text-muted small">
                                        {{ $license->purchase_batch ?: $license->invoice_number ?: 'No reference' }}
                                        @if($license->expiry_date) &middot; expires {{ $license->expiry_date->format('d-m-Y') }} @endif
                                    </div>
                                </div>
                                <span class="badge bg-success">{{ $license->available_seats }} available</span>
                            </div>
                        @endforeach
                    </div>
                @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-key fs-2 d-block mb-2 opacity-50"></i>
                    No available license seats found. Record a purchase action or add more licenses.
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="table-card h-100">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
                <h5 class="mb-1">Users Missing Allocation</h5>
                <p class="text-muted small mb-0">These users have the software discovered on a device but no active license allocation.</p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Detected Devices</th>
                            <th class="text-end pe-4">Last Used</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($missingUsers as $user)
                        @php
                            $userDiscoveries = $mismatchedDiscoveries->where('user_id', $user->id);
                            $latestUse = $userDiscoveries->pluck('last_used_date')->filter()->sortDesc()->first();
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">{{ $user->name }}</div>
                                <div class="text-muted small">{{ $user->email }}</div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $userDiscoveries->pluck('asset_id')->filter()->unique()->count() }}</div>
                                <div class="text-muted small">{{ $userDiscoveries->pluck('asset.asset_tag')->filter()->unique()->implode(', ') ?: 'No device tag' }}</div>
                            </td>
                            <td class="text-end pe-4">{{ $latestUse?->format('d-m-Y') ?? '-' }}</td>
                            <td class="text-end pe-4">
                                @foreach($userDiscoveries as $gap)
                                @php
                                    $hasOpenUninstall = $openUninstallDiscoveryIds->contains($gap->id);
                                @endphp
                                <div class="d-inline-flex gap-1 mb-1">
                                    @if($availableLicenses->isNotEmpty())
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#allocateGap{{ $gap->id }}">
                                        <i class="bi bi-person-check me-1"></i>Allocate
                                    </button>
                                    @endif
                                    @if($hasOpenUninstall)
                                        <span class="badge bg-warning align-self-center">Uninstall Open</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.software-compliance.uninstall-action', [$software, $gap]) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash3 me-1"></i>Uninstall
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                @if($availableLicenses->isNotEmpty())
                                <div class="modal fade" id="allocateGap{{ $gap->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('admin.software-compliance.assign-missing-license', $software) }}">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <div class="modal-content text-start">
                                                <div class="modal-header">
                                                    <div>
                                                        <h5 class="modal-title mb-0">Allocate License</h5>
                                                        <small class="text-muted">{{ $user->name }} - {{ $gap->asset?->asset_tag ?? 'No device' }}</small>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <label class="form-label">Available License <span class="req">*</span></label>
                                                    <select name="software_license_id" class="form-select mb-3" required>
                                                        <option value="">Select license pool</option>
                                                        @foreach($availableLicenses as $license)
                                                            <option value="{{ $license->id }}">
                                                                {{ $license->license_type_label }} - {{ $license->available_seats }} available
                                                                @if($license->expiry_date) - expires {{ $license->expiry_date->format('d-m-Y') }} @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label class="form-label">Assign Date <span class="req">*</span></label>
                                                    <input type="date" name="assigned_date" value="{{ now()->toDateString() }}" class="form-control mb-3" required>
                                                    <label class="form-label">Notes</label>
                                                    <textarea name="notes" rows="2" class="form-control">Allocated from software compliance review.</textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button class="btn btn-success">Allocate License</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @endif
                                @endforeach
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">No allocation gaps found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($missingUsers->hasPages())
                <div class="p-3 border-top">{{ $missingUsers->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-5">
        <div class="form-card h-100">
            <div class="form-card-header">
                <div class="icon-wrap icon-blue"><i class="bi bi-clipboard-check"></i></div>
                Record Remediation
            </div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('admin.software-compliance.actions.store', $software) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Action <span class="req">*</span></label>
                        <select name="action_type" class="form-select @error('action_type') is-invalid @enderror" required>
                            @foreach($actionTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('action_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('action_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" min="1" value="{{ old('quantity', $row['missing_seats'] ?: null) }}" class="form-control @error('quantity') is-invalid @enderror" placeholder="Seats">
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-control @error('due_date') is-invalid @enderror">
                            @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Owner</label>
                        <select name="owner_id" class="form-select @error('owner_id') is-invalid @enderror">
                            <option value="">No owner selected</option>
                            @foreach($owners as $owner)
                                <option value="{{ $owner->id }}" @selected(old('owner_id') == $owner->id)>
                                    {{ $owner->name }} - {{ $owner->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('owner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Decision Notes <span class="req">*</span></label>
                        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" required placeholder="Example: Purchase 5 seats this month or uninstall from inactive devices.">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary w-100 mt-3">
                        <i class="bi bi-plus-circle me-1"></i>Record Action
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="table-card h-100">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
                <h5 class="mb-1">Remediation Log</h5>
                <p class="text-muted small mb-0">Audit trail of compliance decisions and follow-up actions.</p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Action</th>
                            <th>Owner</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Close</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($actions as $action)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">{{ $action->action_type_label }}</div>
                                <div class="text-muted small">
                                    @if($action->quantity) {{ $action->quantity }} seat(s) &middot; @endif
                                    {{ Str::limit($action->notes, 70) }}
                                    @if($action->user || $action->asset)
                                        <br>
                                        Target:
                                        {{ $action->user?->name ?? 'No user' }}
                                        @if($action->asset) on {{ $action->asset->asset_tag }} @endif
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>{{ $action->owner?->name ?? 'Unassigned' }}</div>
                                <div class="text-muted small">{{ $action->createdBy?->name ? 'By '.$action->createdBy->name : '' }}</div>
                            </td>
                            <td>{{ $action->due_date?->format('d-m-Y') ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $action->status_badge }}">{{ ucfirst($action->status) }}</span>
                            </td>
                            <td class="text-end pe-4">
                                @if($action->status === 'open')
                                <form method="POST" action="{{ route('admin.software-compliance.actions.complete', [$software, $action]) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check2 me-1"></i>Done
                                    </button>
                                </form>
                                @else
                                    <span class="text-muted small">{{ $action->completed_at?->format('d-m-Y') }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                No remediation actions recorded yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($actions->hasPages())
                <div class="p-3 border-top">{{ $actions->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="table-card mb-3">
    <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
        <h5 class="mb-1">Discovery Evidence</h5>
        <p class="text-muted small mb-0">Mapped discovery records used to calculate required license count.</p>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">User</th>
                    <th>Device</th>
                    <th>Discovered Software</th>
                    <th>Usage</th>
                    <th class="text-end pe-4">Allocation</th>
                </tr>
            </thead>
            <tbody>
                @forelse($discoveriesPage as $discovery)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">{{ $discovery->user?->name ?? 'No user mapped' }}</div>
                        <div class="text-muted small">{{ $discovery->user?->email ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $discovery->asset?->asset_tag ?? 'No device' }}</div>
                        <div class="text-muted small">{{ $discovery->asset?->name ?? $discovery->asset?->serial_number ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $discovery->raw_name }}</div>
                        <div class="text-muted small">
                            {{ $discovery->raw_publisher ?: 'Unknown publisher' }}
                            @if($discovery->raw_version) &middot; v{{ $discovery->raw_version }} @endif
                        </div>
                    </td>
                    <td>
                        <div>{{ $discovery->last_used_date?->format('d-m-Y') ?? 'No usage date' }}</div>
                        <div class="text-muted small">{{ $discovery->usage_count ?? 0 }} launches</div>
                    </td>
                    <td class="text-end pe-4">
                        @if($discovery->user_id && $assignedUserIds->contains($discovery->user_id))
                            <span class="badge bg-success">Allocated</span>
                        @elseif($discovery->user_id)
                            <span class="badge bg-warning">Missing Allocation</span>
                        @else
                            <span class="badge bg-secondary">No User</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        No mapped discovery records found for this software.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($discoveriesPage->hasPages())
        <div class="p-3 border-top">{{ $discoveriesPage->links() }}</div>
    @endif
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
                <h5 class="mb-1">Valid License Pools</h5>
                <p class="text-muted small mb-0">Active, non-expired license seats counted for compliance.</p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">License</th>
                            <th>Seats</th>
                            <th>Expiry</th>
                            <th class="text-end pe-4">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($validLicenses as $license)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">{{ $license->license_type_label }}</div>
                                <div class="text-muted small">{{ $license->purchase_batch ?: $license->invoice_number ?: $license->po_number ?: 'No reference' }}</div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $license->seats }}</div>
                                <div class="text-muted small">{{ $license->used_seats }} used</div>
                            </td>
                            <td>{{ $license->expiry_date?->format('d-m-Y') ?? 'No expiry' }}</td>
                            <td class="text-end pe-4">Rs {{ number_format($license->total_cost, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">No valid active licenses found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="table-card h-100">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
                <h5 class="mb-1">Active Allocations</h5>
                <p class="text-muted small mb-0">Users with an active license assignment for this software.</p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Assigned</th>
                            <th class="text-end pe-4">Discovery</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">{{ $assignment->user?->name ?? 'Unknown user' }}</div>
                                <div class="text-muted small">{{ $assignment->user?->email ?? '-' }}</div>
                            </td>
                            <td>{{ $assignment->assigned_date?->format('d-m-Y') ?? '-' }}</td>
                            <td class="text-end pe-4">
                                @if($assignment->user_id && $discoveries->pluck('user_id')->contains($assignment->user_id))
                                    <span class="badge bg-success">Detected</span>
                                @else
                                    <span class="badge bg-info">Allocated Only</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-5">No active allocations found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($assignments->hasPages())
                <div class="p-3 border-top">{{ $assignments->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
