@extends('layouts.app')

@section('title', 'My Leaves')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h4>My Leaves</h4>
            <p>Apply for leave and track approval status.</p>
        </div>
        <a href="{{ route('staff.leaves.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Apply Leave</a>
    </div>
</div>

@if($balances->count())
<div class="row g-3 mb-4">
    @foreach($balances as $balance)
        <div class="col-md-3">
            <div class="stat-card-gradient {{ $balance->available > 0 ? 'grad-green' : 'grad-orange' }}">
                <div class="card-body">
                    <div class="stat-label">{{ $balance->leaveType->name }}</div>
                    <div class="stat-number">{{ rtrim(rtrim(number_format($balance->available, 2), '0'), '.') }}</div>
                    <div class="stat-sub">
                        Used {{ rtrim(rtrim($balance->used, '0'), '.') }} of {{ rtrim(rtrim(((float)$balance->opening_balance + (float)$balance->credited), '0'), '.') }} days
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Leave</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Reviewed By</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $leave)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $leave->leave_type_label }}</div>
                            <div class="text-muted small">{{ $leave->reason }}</div>
                        </td>
                        <td>{{ $leave->from_date->format('d-m-Y') }} to {{ $leave->to_date->format('d-m-Y') }}</td>
                        <td>{{ rtrim(rtrim($leave->total_days, '0'), '.') }}</td>
                        <td><span class="badge bg-{{ $leave->status_badge }}">{{ ucfirst($leave->status) }}</span></td>
                        <td>{{ $leave->reviewer?->name ?? '—' }}</td>
                        <td class="text-end pe-4">
                            @if($leave->status === 'pending')
                                <form method="POST" action="{{ route('staff.leaves.cancel', $leave) }}" onsubmit="return confirm('Cancel this leave request?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No leave requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaves->hasPages())
        <div class="p-3 border-top">{{ $leaves->links() }}</div>
    @endif
</div>
@endsection
