@extends('layouts.app')

@section('title', 'Partner Leads')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-funnel me-2 text-primary"></i>Partner Leads</h4>
        <p>Track partner-generated opportunities and convert qualified leads into product subscriptions.</p>
    </div>
    <a href="{{ route('super-admin.partner-leads.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Lead
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card p-3"><div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Total Leads</div><div class="fw-bold mt-1" style="font-size:1.35rem;color:#0f172a">{{ number_format($summary['total']) }}</div></div></div>
    <div class="col-md-3"><div class="stat-card p-3"><div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Open Pipeline</div><div class="fw-bold mt-1 text-primary" style="font-size:1.35rem">{{ number_format($summary['open']) }}</div></div></div>
    <div class="col-md-3"><div class="stat-card p-3"><div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Converted</div><div class="fw-bold mt-1 text-success" style="font-size:1.35rem">{{ number_format($summary['won']) }}</div></div></div>
    <div class="col-md-3"><div class="stat-card p-3"><div class="text-muted" style="font-size:.72rem;font-weight:800;text-transform:uppercase">Pipeline Value</div><div class="fw-bold mt-1" style="font-size:1.35rem;color:#0f172a">&#8377;{{ number_format((float) $summary['pipeline_value'], 2) }}</div></div></div>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Company, contact, email or phone">
            </div>
            <div class="col-md-3">
                <label class="form-label">Partner</label>
                <select name="partner_id" class="form-select form-select-sm">
                    <option value="">All Partners</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" {{ (int) request('partner_id') === $partner->id ? 'selected' : '' }}>{{ $partner->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Stage</label>
                <select name="stage" class="form-select form-select-sm">
                    <option value="">All Stages</option>
                    @foreach($stages as $value => $label)
                        <option value="{{ $value }}" {{ request('stage') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                @if(request()->hasAny(['search', 'partner_id', 'stage']))
                    <a href="{{ route('super-admin.partner-leads.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
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
                    <th>Lead</th>
                    <th>Partner</th>
                    <th>Product</th>
                    <th>Stage</th>
                    <th>Expected Value</th>
                    <th>Commission</th>
                    <th>Close Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td>
                            <strong>{{ $lead->company_name }}</strong>
                            <div class="text-muted small">{{ $lead->contact_person ?: '-' }} | {{ $lead->email ?: $lead->phone ?: '-' }}</div>
                        </td>
                        <td>{{ $lead->partner?->display_name ?? '-' }}</td>
                        <td>{{ $lead->product?->name ?? '-' }}</td>
                        <td><span class="badge bg-{{ $lead->stage_badge }}">{{ $stages[$lead->stage] ?? ucfirst($lead->stage) }}</span></td>
                        <td>&#8377;{{ number_format((float) $lead->expected_monthly_value, 2) }}</td>
                        <td>{{ number_format((float) ($lead->commission_percent ?? $lead->partner?->default_commission_percent ?? 0), 2) }}%</td>
                        <td>{{ $lead->expected_close_date?->format('d-m-Y') ?? '-' }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('super-admin.partner-leads.edit', $lead) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
                                @if(!$lead->converted_organization_id && !in_array($lead->stage, ['won', 'lost'], true))
                                    <form action="{{ route('super-admin.partner-leads.convert', $lead) }}" method="POST" onsubmit="return confirm('Convert this lead into an organization, product subscription, and first admin login?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-success"><i class="bi bi-arrow-right-circle me-1"></i>Convert</button>
                                    </form>
                                @elseif($lead->converted_organization_id)
                                    <a href="{{ route('super-admin.organizations.edit', $lead->converted_organization_id) }}" class="btn btn-sm btn-outline-success">Organization</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-funnel d-block mb-2" style="font-size:1.45rem"></i>No partner leads created yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($leads->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $leads->links() }}</div>
    @endif
</div>
@endsection
