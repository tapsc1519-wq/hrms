@extends('layouts.app')

@section('title', 'Partners')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-person-workspace me-2 text-primary"></i>Partners</h4>
        <p>Manage people and companies who onboard organizations and earn product commission.</p>
    </div>
    <a href="{{ route('super-admin.partners.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Partner
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Total Partners</div>
            <div class="fw-bold mt-1" style="font-size:1.35rem;color:#0f172a">{{ number_format($totalPartners) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Active Partners</div>
            <div class="fw-bold mt-1 text-success" style="font-size:1.35rem">{{ number_format($activePartners) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Linked Subscriptions</div>
            <div class="fw-bold mt-1 text-primary" style="font-size:1.35rem">{{ number_format($linkedSubscriptions) }}</div>
        </div>
    </div>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, company, email or phone">
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
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
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                @if(request()->hasAny(['search', 'type', 'status']))
                    <a href="{{ route('super-admin.partners.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
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
                    <th>Partner</th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Commission</th>
                    <th>Subscriptions</th>
                    <th>Monthly Value</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partners as $partner)
                    <tr>
                        <td>
                            <strong>{{ $partner->display_name }}</strong>
                            <div class="text-muted small">{{ $partner->company_name ? $partner->name : ($partner->contact_person ?: '-') }}</div>
                        </td>
                        <td>
                            <div>{{ $partner->email ?: '-' }}</div>
                            <div class="text-muted small">{{ $partner->phone ?: '-' }}</div>
                        </td>
                        <td>{{ $types[$partner->type] ?? ucfirst($partner->type) }}</td>
                        <td><span class="badge bg-{{ $partner->status_badge }}">{{ $statuses[$partner->status] ?? ucfirst($partner->status) }}</span></td>
                        <td><strong>{{ number_format((float) $partner->default_commission_percent, 2) }}%</strong></td>
                        <td>{{ number_format($partner->subscriptions_count) }}</td>
                        <td>&#8377;{{ number_format((float) ($partner->monthly_revenue ?? 0), 2) }}</td>
                        <td>
                            <a href="{{ route('super-admin.partners.edit', $partner) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-person-workspace d-block mb-2" style="font-size:1.45rem"></i>
                            No partners created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($partners->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $partners->links() }}</div>
    @endif
</div>
@endsection
