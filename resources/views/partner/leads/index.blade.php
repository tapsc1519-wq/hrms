@extends('layouts.app')
@section('title', 'My Leads')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-funnel me-2 text-primary"></i>My Leads</h4>
        <p>Submit and track opportunities shared with Niyantron.</p>
    </div>
    <a href="{{ route('partner.leads.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Lead</a>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label">Search</label><input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm"></div>
            <div class="col-md-3">
                <label class="form-label">Stage</label>
                <select name="stage" class="form-select form-select-sm">
                    <option value="">All Stages</option>
                    @foreach($stages as $value => $label)
                        <option value="{{ $value }}" {{ request('stage') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2"><button class="btn btn-primary btn-sm">Filter</button><a href="{{ route('partner.leads.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a></div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Lead</th><th>Product</th><th>Stage</th><th>Value</th><th>Close Date</th></tr></thead>
            <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td><strong>{{ $lead->company_name }}</strong><div class="text-muted small">{{ $lead->contact_person ?: '-' }} | {{ $lead->email ?: $lead->phone ?: '-' }}</div></td>
                        <td>{{ $lead->product?->name ?? '-' }}</td>
                        <td><span class="badge bg-{{ $lead->stage_badge }}">{{ $stages[$lead->stage] ?? ucfirst($lead->stage) }}</span></td>
                        <td>&#8377;{{ number_format((float) $lead->expected_monthly_value, 2) }}</td>
                        <td>{{ $lead->expected_close_date?->format('d-m-Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No leads submitted yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leads->hasPages())<div class="card-footer bg-white">{{ $leads->links() }}</div>@endif
</div>
@endsection
