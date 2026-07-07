@extends('layouts.app')

@section('title', 'Repair Jobs')

@section('content')
<div class="page-header">
    <h4>Repair Jobs</h4>
    <p>Repair work assigned to {{ $vendor->name }}.</p>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search repair no, asset..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach(['assigned_to_vendor','sent_for_repair','diagnosis_pending','estimate_received','estimate_approved','repair_in_progress','repaired','qc_pending','ready_to_return','closed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('supplier.repairs.index') }}" class="btn btn-light btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr>
                    <th>Repair</th>
                    <th>Asset</th>
                    <th>Organization</th>
                    <th>Type</th>
                    <th>Expected Return</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($repairs as $repair)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $repair->repair_number }}</div>
                            <small class="text-muted">{{ $repair->requested_date?->format('d-m-Y') }} - {{ ucfirst($repair->priority) }}</small>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $repair->asset?->name }}</div>
                            <small class="text-muted">{{ $repair->asset?->asset_tag }}</small>
                        </td>
                        <td>{{ $repair->asset?->organization?->name ?? $vendor->organization?->name }}</td>
                        <td>{{ $repair->repair_type_label }}</td>
                        <td>{{ $repair->expected_return_date?->format('d-m-Y') ?? 'Pending' }}</td>
                        <td><span class="badge bg-{{ $repair->status_badge }}">{{ $repair->status_label }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('supplier.repairs.show', $repair) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-wrench-adjustable fs-1 d-block mb-2 opacity-25"></i>
                            No repair jobs assigned yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($repairs->hasPages())
        <div class="card-footer bg-white">{{ $repairs->links() }}</div>
    @endif
</div>
@endsection
