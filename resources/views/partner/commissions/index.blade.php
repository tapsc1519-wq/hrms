@extends('layouts.app')
@section('title', 'Commissions')

@section('content')
<div class="page-header">
    <h4><i class="bi bi-cash-coin me-2 text-primary"></i>Commissions</h4>
    <p>Track pending, approved and paid commission entries.</p>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary btn-sm">Filter</button><a href="{{ route('partner.commissions.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a></div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Organization</th><th>Product</th><th>Payment</th><th>Commission</th><th>Period</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($commissions as $commission)
                    <tr>
                        <td>{{ $commission->organization?->name ?? 'Organization #' . $commission->organization_id }}</td>
                        <td>{{ $commission->product?->name ?? '-' }}</td>
                        <td>&#8377;{{ number_format((float) $commission->payment_amount, 2) }}</td>
                        <td><strong>&#8377;{{ number_format((float) $commission->commission_amount, 2) }}</strong><div class="text-muted small">{{ number_format((float) $commission->commission_percent, 2) }}%</div></td>
                        <td><span class="text-muted small">{{ $commission->period_start?->format('d-m-Y') ?? '-' }} to {{ $commission->period_end?->format('d-m-Y') ?? '-' }}</span></td>
                        <td><span class="badge bg-{{ $commission->status_badge }}">{{ $statuses[$commission->status] ?? ucfirst($commission->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No commission entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($commissions->hasPages())<div class="card-footer bg-white">{{ $commissions->links() }}</div>@endif
</div>
@endsection
