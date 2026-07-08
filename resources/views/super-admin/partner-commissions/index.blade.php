@extends('layouts.app')

@section('title', 'Partner Commissions')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-cash-coin me-2 text-primary"></i>Partner Commissions</h4>
        <p>Track commission generated from organization subscription payments.</p>
    </div>
    <a href="{{ route('super-admin.partners.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-person-workspace me-1"></i>Partners
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Pending</div>
            <div class="fw-bold mt-1 text-warning" style="font-size:1.35rem">&#8377;{{ number_format((float) $pendingAmount, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Approved</div>
            <div class="fw-bold mt-1 text-primary" style="font-size:1.35rem">&#8377;{{ number_format((float) $approvedAmount, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Paid</div>
            <div class="fw-bold mt-1 text-success" style="font-size:1.35rem">&#8377;{{ number_format((float) $paidAmount, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Total</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#0f172a">&#8377;{{ number_format((float) $totalAmount, 2) }}</div>
        </div>
    </div>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Partner</label>
                <select name="partner_id" class="form-select form-select-sm">
                    <option value="">All Partners</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" {{ (int) request('partner_id') === $partner->id ? 'selected' : '' }}>{{ $partner->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ (int) request('product_id') === $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Organization</label>
                <select name="organization_id" class="form-select form-select-sm">
                    <option value="">All Organizations</option>
                    @foreach($organizations as $organization)
                        <option value="{{ $organization->id }}" {{ (int) request('organization_id') === $organization->id ? 'selected' : '' }}>{{ $organization->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-1">
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                @if(request()->hasAny(['partner_id', 'product_id', 'organization_id', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('super-admin.partner-commissions.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
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
                    <th>Partner</th>
                    <th>Organization</th>
                    <th>Product</th>
                    <th>Payment</th>
                    <th>Commission</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($commissions as $commission)
                    <tr>
                        <td>{{ $commission->payment_date?->format('d-m-Y') ?? '-' }}</td>
                        <td>
                            <strong>{{ $commission->partner?->display_name ?? 'Partner #' . $commission->partner_id }}</strong>
                        </td>
                        <td>
                            @if($commission->organization)
                                <a href="{{ route('super-admin.organizations.show', $commission->organization) }}" class="text-decoration-none fw-semibold">{{ $commission->organization->name }}</a>
                            @else
                                Organization #{{ $commission->organization_id }}
                            @endif
                        </td>
                        <td>{{ $commission->product?->name ?? 'Product #' . $commission->product_id }}</td>
                        <td>&#8377;{{ number_format((float) $commission->payment_amount, 2) }}</td>
                        <td>
                            <strong>&#8377;{{ number_format((float) $commission->commission_amount, 2) }}</strong>
                            <div class="text-muted small">{{ number_format((float) $commission->commission_percent, 2) }}%</div>
                        </td>
                        <td>
                            <span class="text-muted small">
                                {{ $commission->period_start?->format('d-m-Y') ?? '-' }} to {{ $commission->period_end?->format('d-m-Y') ?? '-' }}
                            </span>
                        </td>
                        <td><span class="badge bg-{{ $commission->status_badge }}">{{ $statuses[$commission->status] ?? ucfirst($commission->status) }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @if($commission->status === 'pending')
                                    <form method="POST" action="{{ route('super-admin.partner-commissions.approve', $commission) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary">Approve</button>
                                    </form>
                                @endif
                                @if(in_array($commission->status, ['pending', 'approved'], true))
                                    <form method="POST" action="{{ route('super-admin.partner-commissions.paid', $commission) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Paid</button>
                                    </form>
                                    <form method="POST" action="{{ route('super-admin.partner-commissions.cancel', $commission) }}" onsubmit="return confirm('Cancel this commission?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary">Cancel</button>
                                    </form>
                                @else
                                    <span class="text-muted small">No action</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-cash-coin d-block mb-2" style="font-size:1.45rem"></i>
                            No commission records yet. A record will be created when a payment is recorded for a partner-linked subscription.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($commissions->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $commissions->links() }}</div>
    @endif
</div>
@endsection
