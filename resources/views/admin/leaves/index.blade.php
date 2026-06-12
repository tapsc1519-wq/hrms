@extends('layouts.app')

@section('title', 'Leaves')

@section('content')
<div class="page-header">
    <h4>Leave Requests</h4>
    <p>Review and approve employee leave applications.</p>
</div>

<div class="table-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All status</option>
                    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string)request('department_id') === (string)$department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Employee name or email">
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
                    <th>Leave</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Review</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $leave)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $leave->user?->name }}</div>
                            <div class="text-muted small">{{ $leave->user?->department?->name ?? 'No department' }}</div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $leave->leave_type_label }}</div>
                            <div class="text-muted small">{{ $leave->reason }}</div>
                        </td>
                        <td>{{ $leave->from_date->format('d-m-Y') }} to {{ $leave->to_date->format('d-m-Y') }}</td>
                        <td>{{ rtrim(rtrim($leave->total_days, '0'), '.') }}</td>
                        <td>
                            <span class="badge bg-{{ $leave->status_badge }}">{{ ucfirst($leave->status) }}</span>
                            @if($leave->reviewer)
                                <div class="text-muted small mt-1">By {{ $leave->reviewer->name }}</div>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if($leave->status === 'pending')
                                <div class="d-flex gap-2 justify-content-end">
                                    <form method="POST" action="{{ route('admin.leaves.approve', $leave) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.leaves.reject', $leave) }}" onsubmit="return confirm('Reject this leave request?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No leave requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaves->hasPages())
        <div class="p-3 border-top">{{ $leaves->links() }}</div>
    @endif
</div>
@endsection
