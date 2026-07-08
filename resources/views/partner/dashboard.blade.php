@extends('layouts.app')
@section('title', 'Partner Dashboard')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-person-workspace me-2 text-primary"></i>Partner Dashboard</h4>
        <p>{{ $partner->display_name }} pipeline, subscriptions and commission status.</p>
    </div>
    <a href="{{ route('partner.leads.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Lead
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card p-3"><div class="text-muted small fw-bold text-uppercase">Open Leads</div><div class="fw-bold mt-1" style="font-size:1.35rem">{{ $stats['open_leads'] }}</div></div></div>
    <div class="col-md-3"><div class="stat-card p-3"><div class="text-muted small fw-bold text-uppercase">Converted</div><div class="fw-bold mt-1 text-success" style="font-size:1.35rem">{{ $stats['converted_leads'] }}</div></div></div>
    <div class="col-md-3"><div class="stat-card p-3"><div class="text-muted small fw-bold text-uppercase">Subscriptions</div><div class="fw-bold mt-1 text-primary" style="font-size:1.35rem">{{ $stats['subscriptions'] }}</div></div></div>
    <div class="col-md-3"><div class="stat-card p-3"><div class="text-muted small fw-bold text-uppercase">Pending Commission</div><div class="fw-bold mt-1" style="font-size:1.35rem">&#8377;{{ number_format((float) $stats['pending_commission'], 2) }}</div></div></div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Recent Leads</strong>
                <a href="{{ route('partner.leads.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Company</th><th>Product</th><th>Stage</th><th>Value</th></tr></thead>
                    <tbody>
                        @forelse($recentLeads as $lead)
                            <tr>
                                <td><strong>{{ $lead->company_name }}</strong><div class="text-muted small">{{ $lead->email ?: $lead->phone ?: '-' }}</div></td>
                                <td>{{ $lead->product?->name ?? '-' }}</td>
                                <td><span class="badge bg-{{ $lead->stage_badge }}">{{ str($lead->stage)->headline() }}</span></td>
                                <td>&#8377;{{ number_format((float) $lead->expected_monthly_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No leads yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Commission Snapshot</strong>
                <a href="{{ route('partner.commissions.index') }}" class="btn btn-outline-primary btn-sm">Ledger</a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted">Pending</span><strong class="text-warning">&#8377;{{ number_format((float) $stats['pending_commission'], 2) }}</strong></div>
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted">Approved</span><strong class="text-primary">&#8377;{{ number_format((float) $stats['approved_commission'], 2) }}</strong></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Paid</span><strong class="text-success">&#8377;{{ number_format((float) $stats['paid_commission'], 2) }}</strong></div>
            </div>
        </div>
    </div>
</div>
@endsection
