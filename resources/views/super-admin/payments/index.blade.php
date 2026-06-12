@extends('layouts.app')
@section('title', 'Payments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Payments</h4>
        <p>Review subscription payments recorded for all organizations.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('super-admin.payments.export', request()->query()) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
        <a href="{{ route('super-admin.organizations.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-buildings me-1"></i>Organizations
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.74rem;font-weight:700;text-transform:uppercase">Total Collected</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#1e293b">&#8377;{{ number_format($totalCollected, 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.74rem;font-weight:700;text-transform:uppercase">Payments</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#1e293b">{{ number_format($paymentCount) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.74rem;font-weight:700;text-transform:uppercase">Latest Payment</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#1e293b">
                {{ $latestPayment ? \Carbon\Carbon::parse($latestPayment)->format('d-m-Y') : '-' }}
            </div>
        </div>
    </div>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Organization</label>
                <select name="organization_id" class="form-select form-select-sm">
                    <option value="">All Organizations</option>
                    @foreach($organizations as $organization)
                        <option value="{{ $organization->id }}" {{ (int) request('organization_id') === $organization->id ? 'selected' : '' }}>
                            {{ $organization->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Method</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">All Methods</option>
                    @foreach($methods as $value => $label)
                        <option value="{{ $value }}" {{ request('payment_method') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                @if(request()->hasAny(['organization_id', 'payment_method', 'date_from', 'date_to']))
                    <a href="{{ route('super-admin.payments.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Payment Date</th>
                    <th>Organization</th>
                    <th>Amount</th>
                    <th>Period</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Recorded By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                        <td>
                            <a href="{{ route('super-admin.organizations.show', $payment->organization) }}" class="text-decoration-none fw-semibold">
                                {{ $payment->organization?->name ?? '-' }}
                            </a>
                        </td>
                        <td><strong>&#8377;{{ number_format((float) $payment->amount, 2) }}</strong></td>
                        <td>
                            <span class="text-muted" style="font-size:.78rem">
                                {{ $payment->period_start->format('d-m-Y') }} to {{ $payment->period_end->format('d-m-Y') }}
                            </span>
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $methods[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</span></td>
                        <td>{{ $payment->reference_no ?? '-' }}</td>
                        <td>
                            <span class="text-muted">{{ $payment->recorder?->name ?? '-' }}</span>
                            @if($payment->notes)
                                <i class="bi bi-chat-left-text text-primary ms-1" title="{{ $payment->notes }}"></i>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('super-admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-printer me-1"></i>Receipt
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-receipt d-block mb-2" style="font-size:1.45rem"></i>
                            No payments recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
