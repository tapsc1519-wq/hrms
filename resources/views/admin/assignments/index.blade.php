@extends('layouts.app')

@section('title', 'Asset Assignments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Asset Assignments</h4>
        <p class="page-subtitle mb-0">{{ $assignments->total() }} total records</p>
    </div>
    <a href="{{ route('admin.assignments.create') }}" class="btn btn-primary">
        <i class="bi bi-person-check me-1"></i>Assign Asset
    </a>
</div>

<!-- Filters -->
<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search asset or user..." value="{{ request('search') }}">
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
                    <td>{{ $a->user?->name ?? '—' }}</td>
                    <td>{{ $a->department?->name ?? '—' }}</td>
                    <td>{{ $a->assigned_date->format('d-m-Y') }}</td>
                    <td>
                        @if($a->expected_return_date)
                            <span class="{{ $a->expected_return_date->isPast() && $a->status === 'active' ? 'text-danger fw-500' : '' }}">
                                {{ $a->expected_return_date->format('d-m-Y') }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
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
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#returnModal{{ $a->id }}">
                            <i class="bi bi-arrow-return-left"></i> Return
                        </button>
                        @else
                            <span class="text-muted small">{{ $a->actual_return_date?->format('d-m-Y') }}</span>
                        @endif
                    </td>
                </tr>

                <!-- Return Modal -->
                @if($a->status === 'active')
                <div class="modal fade" id="handoverModal{{ $a->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <form action="{{ route('admin.assignments.handover', $a) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h5 class="modal-title mb-0">Handover Asset</h5>
                                        <small class="text-muted">{{ $a->asset->name }} · {{ $a->asset->asset_tag }}</small>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-3">Current assignee: <strong>{{ $a->user?->name ?? '—' }}</strong></p>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="handover-option d-block border rounded-3 p-3 h-100">
                                                <input type="radio" name="handover_type" value="staff" class="form-check-input me-2 handover-type" data-target="#staffTarget{{ $a->id }}" checked>
                                                <span class="fw-700">Handover to another Staff Member</span>
                                                <div class="text-muted small mt-1">Current assignment will close as transferred and a new active assignment will be created.</div>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="handover-option d-block border rounded-3 p-3 h-100">
                                                <input type="radio" name="handover_type" value="it_admin" class="form-check-input me-2 handover-type" data-target="#staffTarget{{ $a->id }}">
                                                <span class="fw-700">Handover to IT Support / Admin</span>
                                                <div class="text-muted small mt-1">Asset will return to IT stock and become available for future assignment.</div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3 staff-target" id="staffTarget{{ $a->id }}">
                                        <label class="form-label small fw-500">New Staff Member <span class="text-danger">*</span></label>
                                        <select name="new_user_id" class="form-select handover-staff-select" required>
                                            <option value="">Select staff member</option>
                                            @foreach($staffUsers as $staff)
                                                @if($staff->id !== $a->user_id)
                                                <option value="{{ $staff->id }}">{{ $staff->name }}{{ $staff->department?->name ? ' · '.$staff->department->name : '' }}</option>
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
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check me-1"></i>Confirm Handover</button>
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
                                    <h5 class="modal-title">Return Asset</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Returning: <strong>{{ $a->asset->name }}</strong> from <strong>{{ $a->user?->name }}</strong></p>
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
                                    <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-check me-1"></i>Confirm Return</button>
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
