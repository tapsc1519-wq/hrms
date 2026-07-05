@extends('layouts.app')

@section('title', 'My Repairs')

@section('content')
<div class="page-header">
    <a href="{{ route('staff.my-assets.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> My Assets</a>
    <h4>My Repairs</h4>
    <p>Repair requests raised for your assigned assets.</p>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr>
                    <th>Repair</th>
                    <th>Asset</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Expected Return</th>
                    <th>Documents</th>
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
                        <td>{{ $repair->repair_type_label }}</td>
                        <td><span class="badge bg-{{ $repair->status_badge }}">{{ $repair->status_label }}</span></td>
                        <td>{{ $repair->expected_return_date?->format('d-m-Y') ?? 'Pending' }}</td>
                        <td>
                            @forelse($repair->attachments as $attachment)
                                <a href="{{ $attachment->url }}" target="_blank" class="btn btn-sm btn-outline-secondary mb-1">
                                    <i class="bi bi-paperclip"></i> {{ $attachment->type_label }}
                                </a>
                            @empty
                                <span class="text-muted small">No shared documents</span>
                            @endforelse
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" class="bg-light">
                            <div class="small">
                                <strong>Issue:</strong> {{ $repair->issue_summary }}
                                @if($repair->diagnosis)
                                    <span class="d-block mt-1"><strong>Diagnosis:</strong> {{ $repair->diagnosis }}</span>
                                @endif
                                @if($repair->qc_status)
                                    <span class="d-block mt-1"><strong>QC:</strong> {{ ucfirst($repair->qc_status) }}{{ $repair->qc_notes ? ' - '.$repair->qc_notes : '' }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-wrench-adjustable fs-1 d-block mb-2 opacity-25"></i>
                            No repair requests yet.
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
