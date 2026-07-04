@extends('layouts.app')
@section('title', 'My Assets')

@section('content')

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>My Assets</h4>
        <p>Assets currently assigned to you</p>
    </div>
    <a href="{{ route('staff.requests.create') }}" class="btn btn-primary">
        <i class="bi bi-clipboard-plus me-1"></i>Request an Asset
    </a>
</div>

<div class="table-card mb-4" style="padding:0;overflow:hidden">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between" style="background:#eff6ff">
        <div>
            <div class="fw-700" style="color:#1d4ed8"><i class="bi bi-inbox-fill me-2"></i>Incoming Handover Requests</div>
            <div class="text-muted small">Review assets another employee wants to hand over to you.</div>
        </div>
        <span class="badge bg-primary">{{ $incomingHandovers->count() }} pending</span>
    </div>
    <div class="p-3">
        @if($incomingHandovers->isEmpty())
        <div class="text-center py-3">
            <i class="bi bi-inbox text-muted d-block mb-2" style="font-size:1.5rem;opacity:.45"></i>
            <div class="fw-600" style="color:#334155;font-size:.9rem">No incoming handover requests</div>
            <div class="text-muted small">When another employee sends an asset handover to you, it will appear here for accept or reject.</div>
        </div>
        @else
        <div class="row g-3">
            @foreach($incomingHandovers as $handover)
            <div class="col-lg-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;background:#f0f9ff;color:#0284c7">
                            <i class="bi {{ $handover->asset->category?->icon ?? 'bi-box-seam' }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-700">{{ $handover->asset->name }}</div>
                            <div class="text-muted small">{{ $handover->asset->asset_tag }} · From {{ $handover->fromUser->name }}</div>
                        </div>
                    </div>
                    <div class="small text-muted mb-3">
                        Condition: <strong>{{ ucfirst($handover->condition_in) }}</strong>
                        · Date: <strong>{{ $handover->handover_date->format('d-m-Y') }}</strong>
                        @if($handover->notes)
                            <div class="mt-1">Note: {{ $handover->notes }}</div>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <form action="{{ route('staff.my-assets.handovers.accept', $handover) }}" method="POST" class="flex-fill">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-check2-circle me-1"></i>Accept
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-danger btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#rejectHandoverModal{{ $handover->id }}">
                            <i class="bi bi-x-circle me-1"></i>Reject
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="rejectHandoverModal{{ $handover->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('staff.my-assets.handovers.reject', $handover) }}" method="POST">
                        @csrf @method('PATCH')
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Reject Handover</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted">Reject handover for <strong>{{ $handover->asset->name }}</strong> from <strong>{{ $handover->fromUser->name }}</strong>.</p>
                                <label class="form-label small fw-700">Reason / Notes</label>
                                <textarea name="response_notes" class="form-control" rows="3" placeholder="Optional reason"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Reject Handover</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

@if($outgoingHandovers->isNotEmpty())
<div class="alert alert-warning border rounded-3 mb-4">
    <div class="fw-700 mb-1"><i class="bi bi-hourglass-split me-1"></i>Pending handover requests sent</div>
    <div class="small">
        @foreach($outgoingHandovers as $handover)
            <div>{{ $handover->asset->name }} is waiting for {{ $handover->toUser->name }} to accept or reject.</div>
        @endforeach
    </div>
</div>
@endif

@if($assignments->isEmpty())
<div class="table-card text-center py-5">
    <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-25"></i>
    <h5 class="fw-600 mb-1" style="color:#334155">No assets assigned yet</h5>
    <p class="text-muted mb-3" style="font-size:.875rem">You have no active asset assignments at this time.</p>
    <a href="{{ route('staff.requests.create') }}" class="btn btn-primary">
        <i class="bi bi-clipboard-plus me-1"></i>Request an Asset
    </a>
</div>
@else
<div class="row g-3">
    @foreach($assignments as $assignment)
    @php
        $asset = $assignment->asset;
        $openIssue = $openIssues->get($assignment->id);
        $openRepair = $openRepairs->get($assignment->id);
    @endphp
    <div class="col-md-6 col-xl-4">
        <div class="table-card h-100" style="padding:0;overflow:hidden">

            {{-- Card Top Accent --}}
            <div style="height:4px;background:linear-gradient(90deg,#3b82f6,#6366f1)"></div>

            <div class="p-4">
                {{-- Header row --}}
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:#eff6ff">
                        <i class="bi {{ $asset->category?->icon ?? 'bi-box-seam' }} text-primary" style="font-size:1.3rem"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-700 text-truncate" style="color:#0f172a">{{ $asset->name }}</div>
                        <div class="text-muted" style="font-size:.78rem">{{ $asset->category?->name ?? 'Uncategorised' }}</div>
                    </div>
                    <span class="badge bg-success flex-shrink-0">Active</span>
                </div>

                {{-- Details grid --}}
                <div style="font-size:.8rem;display:grid;grid-template-columns:1fr 1fr;gap:.4rem .8rem">
                    <div>
                        <div style="color:#94a3b8;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px">Asset Tag</div>
                        <code style="background:#f1f5f9;color:#3b82f6;padding:.15rem .35rem;border-radius:4px;font-size:.78rem">
                            {{ $asset->asset_tag ?? '—' }}
                        </code>
                    </div>
                    <div>
                        <div style="color:#94a3b8;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px">Brand</div>
                        <div style="font-weight:600;color:#334155">{{ $asset->brand ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="color:#94a3b8;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px">Serial No.</div>
                        <div style="font-weight:600;color:#334155">{{ $asset->serial_number ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="color:#94a3b8;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px">Location</div>
                        <div style="font-weight:600;color:#334155">{{ $asset->location?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="color:#94a3b8;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px">Assigned</div>
                        <div style="font-weight:600;color:#334155">{{ $assignment->assigned_date->format('d-m-Y') }}</div>
                    </div>
                    @if($assignment->expected_return_date)
                    <div>
                        <div style="color:#94a3b8;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px">Return By</div>
                        <div class="fw-600 {{ $assignment->expected_return_date->isPast() ? 'text-danger' : '' }}" style="{{ !$assignment->expected_return_date->isPast() ? 'color:#334155' : '' }}">
                            {{ $assignment->expected_return_date->format('d-m-Y') }}
                            @if($assignment->expected_return_date->isPast())
                            <i class="bi bi-exclamation-circle ms-1"></i>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Warranty bar --}}
                @if($asset->warranty_expiry_date)
                <div class="mt-3 pt-3 border-top">
                    @php
                        $expiry = \Carbon\Carbon::parse($asset->warranty_expiry_date);
                        $expired = $expiry->isPast();
                    @endphp
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-{{ $expired ? 'x text-danger' : 'check text-success' }}" style="font-size:.9rem"></i>
                        <div style="font-size:.78rem">
                            <span class="fw-600">Warranty</span>
                            <span class="text-muted ms-1">{{ $expired ? 'expired' : 'until' }} {{ $expiry->format('d-m-Y') }}</span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-3 pt-3 border-top">
                    @if($openIssue)
                    <div class="alert alert-warning py-2 px-3 mb-2" style="font-size:.78rem">
                        <div class="fw-700"><i class="bi bi-exclamation-triangle me-1"></i>Issue reported</div>
                        <div>{{ $openIssue->issue_type_label }} is {{ str_replace('_', ' ', $openIssue->status) }}.</div>
                    </div>
                    @endif
                    @if($openRepair)
                    <div class="alert alert-info py-2 px-3 mb-2" style="font-size:.78rem">
                        <div class="fw-700"><i class="bi bi-wrench-adjustable me-1"></i>Repair requested</div>
                        <div>{{ $openRepair->repair_number }} is {{ str_replace('_', ' ', $openRepair->status) }}.</div>
                    </div>
                    @endif
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-warning flex-fill" data-bs-toggle="modal" data-bs-target="#repairModal{{ $assignment->id }}" {{ $openRepair ? 'disabled' : '' }}>
                            <i class="bi bi-wrench-adjustable me-1"></i>Repair
                        </button>
                        <button type="button" class="btn btn-outline-danger flex-fill" data-bs-toggle="modal" data-bs-target="#issueModal{{ $assignment->id }}" {{ $openIssue ? 'disabled' : '' }}>
                            <i class="bi bi-exclamation-circle me-1"></i>Report Issue
                        </button>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-primary flex-fill" data-bs-toggle="modal" data-bs-target="#handoverModal{{ $assignment->id }}">
                            <i class="bi bi-arrow-left-right me-1"></i>Handover
                        </button>
                    </div>
                </div>
            </div>

            @if($assignment->purpose)
            <div class="px-4 py-2 border-top" style="background:#f8fafc;font-size:.78rem;color:#64748b">
                <i class="bi bi-info-circle me-1"></i>{{ $assignment->purpose }}
            </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="issueModal{{ $assignment->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('staff.my-assets.issue-report', $assignment) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Report Asset Issue</h5>
                            <small class="text-muted">{{ $asset->name }} · {{ $asset->asset_tag }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border rounded-3 mb-3" style="font-size:.82rem">
                            Report damaged, lost, stolen or unusable assets here. Admin/IT will review the issue and raise a disposal request only if required.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-700">Issue Type <span class="text-danger">*</span></label>
                                <select name="issue_type" class="form-select" required>
                                    <option value="damaged">Damaged</option>
                                    <option value="not_working">Not Working</option>
                                    <option value="lost">Lost</option>
                                    <option value="stolen">Stolen</option>
                                    <option value="obsolete">Obsolete</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-700">Severity <span class="text-danger">*</span></label>
                                <select name="severity" class="form-select" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-700">Reported Date <span class="text-danger">*</span></label>
                                <input type="date" name="reported_date" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-700">What happened? <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="4" maxlength="2000" required placeholder="Describe the issue, when it happened and whether the asset is usable."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-send me-1"></i>Submit Issue
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="repairModal{{ $assignment->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('staff.my-assets.repair-request', $assignment) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Request Asset Repair</h5>
                            <small class="text-muted">{{ $asset->name }} · {{ $asset->asset_tag }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border rounded-3 mb-3" style="font-size:.82rem">
                            Use repair request when the assigned asset is faulty and needs Admin/IT support to inspect, repair, send to vendor, or return after quality check.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-700">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-700">Request Date <span class="text-danger">*</span></label>
                                <input type="date" name="requested_date" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-700">What needs repair? <span class="text-danger">*</span></label>
                                <textarea name="issue_summary" class="form-control" rows="4" maxlength="2000" required placeholder="Describe the fault, error, damage, or performance issue."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send me-1"></i>Submit Repair Request
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="handoverModal{{ $assignment->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('staff.my-assets.handover', $assignment) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Handover Asset</h5>
                            <small class="text-muted">{{ $asset->name }} · {{ $asset->asset_tag }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border rounded-3 mb-3" style="font-size:.82rem">
                            Choose where this asset is going. If you select another employee, they must accept the request before the asset moves to their My Assets list. If you hand it to IT/Admin, it returns to available stock immediately.
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="d-block border rounded-3 p-3 h-100" style="cursor:pointer">
                                    <input type="radio" name="handover_type" value="staff" class="form-check-input me-2 staff-handover-type" checked>
                                    <span class="fw-700">Handover to another Staff Member</span>
                                    <div class="text-muted small mt-1">Select the employee who will receive this asset.</div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="d-block border rounded-3 p-3 h-100" style="cursor:pointer">
                                    <input type="radio" name="handover_type" value="it_admin" class="form-check-input me-2 staff-handover-type">
                                    <span class="fw-700">Handover to IT Support / Admin</span>
                                    <div class="text-muted small mt-1">Return the asset to IT/Admin for future allocation.</div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3 staff-target">
                            <label class="form-label small fw-700">Receiving Staff Member <span class="text-danger">*</span></label>
                            <select name="new_user_id" class="form-select staff-recipient-select" required>
                                <option value="">Select staff member</option>
                                @foreach($staffUsers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}{{ $staff->department?->name ? ' · '.$staff->department->name : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-700">Handover Date <span class="text-danger">*</span></label>
                                <input type="date" name="handover_date" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-700">Asset Condition <span class="text-danger">*</span></label>
                                <select name="condition_in" class="form-select" required>
                                    <option value="excellent">Excellent</option>
                                    <option value="good" selected>Good</option>
                                    <option value="fair">Fair</option>
                                    <option value="poor">Poor</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-700">Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Optional handover remarks"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Submit Handover
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-3">{{ $assignments->links() }}</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('change', function (event) {
    if (!event.target.classList.contains('staff-handover-type')) return;

    var modal = event.target.closest('.modal');
    var staffTarget = modal ? modal.querySelector('.staff-target') : null;
    var staffSelect = modal ? modal.querySelector('.staff-recipient-select') : null;
    var isStaff = event.target.value === 'staff';

    if (staffTarget) staffTarget.style.display = isStaff ? '' : 'none';
    if (staffSelect) {
        staffSelect.required = isStaff;
        if (!isStaff) staffSelect.value = '';
    }
});

document.addEventListener('shown.bs.modal', function (event) {
    if (!event.target.id || !event.target.id.startsWith('handoverModal')) return;
    var selected = event.target.querySelector('.staff-handover-type:checked');
    if (selected) selected.dispatchEvent(new Event('change', { bubbles: true }));
});
</script>
@endpush
