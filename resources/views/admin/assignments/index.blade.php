@extends('layouts.app')

@section('title', 'Asset Assignments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Asset Assignments</h4>
        <p class="page-subtitle mb-0">{{ $assignments->total() }} total records</p>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->hasPermission('assignments.create'))
        <a href="{{ route('admin.assignments.bulk') }}" class="btn btn-outline-primary">
            <i class="bi bi-filetype-csv me-1"></i>Bulk Assign CSV
        </a>
        <a href="{{ route('admin.assignments.create') }}" class="btn btn-primary">
            <i class="bi bi-person-check me-1"></i>Assign Asset
        </a>
        @endif
    </div>
</div>

@if($pendingHandovers->isNotEmpty())
<div class="table-card mb-3" style="padding:0;overflow:hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background:#fffbeb">
        <div>
            <div class="fw-700" style="color:#b45309"><i class="bi bi-hourglass-split me-2"></i>Handover Requests Pending IT Approval</div>
            <div class="text-muted small">Approve employee-to-employee handovers before the recipient can accept the asset.</div>
        </div>
        <span class="badge bg-warning text-dark">{{ $pendingHandovers->count() }} pending</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.84rem">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Asset</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Date</th>
                    <th>Condition</th>
                    <th class="text-end pe-3">Decision</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingHandovers as $handover)
                <tr>
                    <td class="ps-3">
                        <div class="fw-700">{{ $handover->asset->name }}</div>
                        <div class="text-muted small">{{ $handover->asset->asset_tag }}</div>
                    </td>
                    <td>{{ $handover->fromUser->name }}</td>
                    <td>{{ $handover->toUser->name }}</td>
                    <td>{{ $handover->handover_date->format('d-m-Y') }}</td>
                    <td><span class="badge bg-light text-dark border">{{ ucfirst($handover->condition_in) }}</span></td>
                    <td class="text-end pe-3">
                        <div class="d-inline-flex gap-2">
                            <form method="POST" action="{{ route('admin.assignment-handovers.approve', $handover) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Approve</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectAdminHandover{{ $handover->id }}">
                                <i class="bi bi-x-circle me-1"></i>Reject
                            </button>
                        </div>
                    </td>
                </tr>
                <div class="modal fade" id="rejectAdminHandover{{ $handover->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('admin.assignment-handovers.reject', $handover) }}">
                            @csrf @method('PATCH')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reject Handover Request</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted">Reject handover of <strong>{{ $handover->asset->name }}</strong> from {{ $handover->fromUser->name }} to {{ $handover->toUser->name }}.</p>
                                    <label class="form-label small fw-700">Reason / Notes</label>
                                    <textarea name="approval_notes" class="form-control" rows="3" placeholder="Optional reason"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-danger">Reject Request</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search asset or employee..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Returned</option>
                    <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr>
                    <th>Asset</th>
                    <th>Assigned To</th>
                    <th>Department</th>
                    <th>Assigned Date</th>
                    <th>Expected Return</th>
                    <th>Condition Out</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $a)
                <tr>
                    <td>
                        <a href="{{ route('admin.assets.show', $a->asset) }}" class="text-decoration-none fw-500">
                            {{ $a->asset->name }}
                        </a>
                        <br><small class="text-muted">{{ $a->asset->asset_tag }}</small>
                    </td>
                    <td>{{ $a->user?->name ?? '-' }}</td>
                    <td>{{ $a->department?->name ?? '-' }}</td>
                    <td>{{ $a->assigned_date->format('d-m-Y') }}</td>
                    <td>
                        @if($a->expected_return_date)
                            <span class="{{ $a->expected_return_date->isPast() && $a->status === 'active' ? 'text-danger fw-500' : '' }}">
                                {{ $a->expected_return_date->format('d-m-Y') }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td><span class="badge bg-secondary">{{ ucfirst($a->condition_out) }}</span></td>
                    <td>
                        <span class="badge bg-{{ $a->status === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($a->status) }}
                        </span>
                    </td>
                    <td>
                        @if($a->status === 'active')
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#handoverModal{{ $a->id }}">
                                <i class="bi bi-arrow-left-right"></i> Request Handover
                            </button>
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#returnModal{{ $a->id }}">
                                <i class="bi bi-arrow-return-left"></i> Mark Returned
                            </button>
                        </div>
                        @else
                            <span class="text-muted small">{{ $a->actual_return_date?->format('d-m-Y') }}</span>
                        @endif
                    </td>
                </tr>

                @if($a->status === 'active')
                <div class="modal fade" id="handoverModal{{ $a->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <form action="{{ route('admin.assignments.handover', $a) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h5 class="modal-title mb-0">Request Handover</h5>
                                        <small class="text-muted">{{ $a->asset->name }} - {{ $a->asset->asset_tag }}</small>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-3">Current assignee: <strong>{{ $a->user?->name ?? '-' }}</strong></p>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="handover-option d-block border rounded-3 p-3 h-100">
                                                <input type="radio" name="handover_type" value="staff" class="form-check-input me-2 handover-type" checked>
                                                <span class="fw-700">Request handover to another employee</span>
                                                <div class="text-muted small mt-1">Recipient must accept before custody moves to their My Assets list.</div>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="handover-option d-block border rounded-3 p-3 h-100">
                                                <input type="radio" name="handover_type" value="it_admin" class="form-check-input me-2 handover-type">
                                                <span class="fw-700">Mark returned to IT Support / Admin</span>
                                                <div class="text-muted small mt-1">Asset returns to IT stock and becomes available.</div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3 staff-target">
                                        <label class="form-label small fw-500">Receiving Employee <span class="text-danger">*</span></label>
                                        <select name="new_user_id" class="form-select handover-staff-select" required>
                                            <option value="">Select employee</option>
                                            @foreach($staffUsers as $staff)
                                                @if($staff->id !== $a->user_id)
                                                <option value="{{ $staff->id }}">{{ $staff->name }}{{ $staff->department?->name ? ' - '.$staff->department->name : '' }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-500">Handover Date <span class="text-danger">*</span></label>
                                            <input type="date" name="handover_date" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-500">Condition at Handover <span class="text-danger">*</span></label>
                                            <select name="condition_in" class="form-select" required>
                                                <option value="excellent">Excellent</option>
                                                <option value="good" selected>Good</option>
                                                <option value="fair">Fair</option>
                                                <option value="poor">Poor</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-500">Notes</label>
                                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional handover remarks"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Send Handover Request</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="returnModal{{ $a->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('admin.assignments.return', $a) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Mark Asset Returned</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Use this only after IT/Admin has physically received <strong>{{ $a->asset->name }}</strong> from <strong>{{ $a->user?->name }}</strong>.</p>
                                    <div class="mb-3">
                                        <label class="form-label small fw-500">Return Date <span class="text-danger">*</span></label>
                                        <input type="date" name="actual_return_date" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-500">Condition on Return <span class="text-danger">*</span></label>
                                        <select name="condition_in" class="form-select" required>
                                            <option value="excellent">Excellent</option>
                                            <option value="good" selected>Good</option>
                                            <option value="fair">Fair</option>
                                            <option value="poor">Poor</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label small fw-500">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-check me-1"></i>Mark Returned</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-person-check fs-1 d-block mb-2"></i>
                        No assignments yet. <a href="{{ route('admin.assignments.create') }}">Assign an asset</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assignments->hasPages())
    <div class="card-footer bg-white border-top-0">{{ $assignments->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('change', function (event) {
    if (!event.target.classList.contains('handover-type')) return;

    var modal = event.target.closest('.modal');
    if (!modal) return;

    var staffTarget = modal.querySelector('.staff-target');
    var staffSelect = modal.querySelector('.handover-staff-select');
    var isStaffHandover = event.target.value === 'staff';

    if (staffTarget) staffTarget.style.display = isStaffHandover ? '' : 'none';
    if (staffSelect) {
        staffSelect.required = isStaffHandover;
        if (!isStaffHandover) staffSelect.value = '';
    }
});

document.addEventListener('shown.bs.modal', function (event) {
    var modal = event.target;
    if (!modal.id || !modal.id.startsWith('handoverModal')) return;

    var checked = modal.querySelector('.handover-type:checked');
    if (checked) checked.dispatchEvent(new Event('change', { bubbles: true }));
});
</script>
@endpush
