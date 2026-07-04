@extends('layouts.app')

@section('title', 'AMC Contracts')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>AMC Contracts</h4>
        <p>Manage asset maintenance coverage, SLA and vendor contract details.</p>
    </div>
    <a href="{{ route('admin.asset-amc-contracts.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add AMC Contract
    </a>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr>
                    <th>Contract</th>
                    <th>Vendor</th>
                    <th>Coverage</th>
                    <th>Period</th>
                    <th>Assets</th>
                    <th>SLA</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contracts as $contract)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $contract->title }}</div>
                            <small class="text-muted">{{ $contract->contract_number ?: 'No contract number' }}</small>
                        </td>
                        <td>{{ $contract->vendor?->name ?? 'Not linked' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $contract->coverage_type)) }}</td>
                        <td>{{ $contract->start_date?->format('d-m-Y') ?? '—' }} to {{ $contract->end_date?->format('d-m-Y') ?? '—' }}</td>
                        <td>{{ $contract->assets->count() }}</td>
                        <td>
                            <div class="small">Response: {{ $contract->response_sla_hours ? $contract->response_sla_hours.'h' : '—' }}</div>
                            <div class="small text-muted">Resolution: {{ $contract->resolution_sla_hours ? $contract->resolution_sla_hours.'h' : '—' }}</div>
                        </td>
                        <td><span class="badge bg-{{ $contract->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($contract->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.asset-amc-contracts.edit', $contract) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No AMC contracts added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($contracts->hasPages())
        <div class="card-footer bg-white border-top">{{ $contracts->links() }}</div>
    @endif
</div>
@endsection
