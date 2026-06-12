@extends('layouts.app')

@section('title', 'Attendance Regularizations')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h4>Attendance Regularizations</h4>
        <p>Review employee attendance correction requests before they update attendance records.</p>
    </div>
    <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-clock-history me-1"></i> Attendance Records
    </a>
</div>

<div class="table-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['pending', 'approved', 'rejected', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Employee name or email">
            </div>
            <div class="col-md-4">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Employee</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Requested Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Review</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $request)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $request->user?->name }}</div>
                            <div class="text-muted small">{{ $request->user?->department?->name ?? 'No department' }}</div>
                        </td>
                        <td>{{ $request->attendance_date->format('d-m-Y') }}</td>
                        <td>{{ $request->request_type_label }}</td>
                        <td class="small">
                            In: {{ $request->requested_sign_in_at?->format('h:i A') ?? '-' }}<br>
                            Out: {{ $request->requested_sign_out_at?->format('h:i A') ?? '-' }}
                        </td>
                        <td style="max-width:260px">{{ $request->reason }}</td>
                        <td>
                            <span class="badge bg-{{ $request->status_badge }}">{{ ucfirst($request->status) }}</span>
                            @if($request->reviewed_at)
                                <div class="text-muted small mt-1">{{ $request->reviewed_at->format('d-m-Y h:i A') }}</div>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if($request->status === 'pending')
                                <div class="d-flex justify-content-end gap-2">
                                    <form method="POST" action="{{ route('admin.attendance.regularizations.approve', $request) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i> Approve</button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectRegularization{{ $request->id }}">
                                        <i class="bi bi-x-lg me-1"></i> Reject
                                    </button>
                                </div>
                            @else
                                <div class="small text-muted">{{ $request->review_notes ?: 'Reviewed' }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No attendance regularization requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
        <div class="p-3 border-top">{{ $requests->links() }}</div>
    @endif
</div>

@foreach($requests->where('status', 'pending') as $request)
    <div class="modal fade" id="rejectRegularization{{ $request->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.attendance.regularizations.reject', $request) }}" class="modal-content border-0" style="border-radius:16px">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Reject Regularization</h5>
                        <div class="text-muted small">Add a reason so the employee understands the decision.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Review Notes <span class="req">*</span></label>
                    <textarea name="review_notes" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger"><i class="bi bi-x-lg me-1"></i> Reject</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
