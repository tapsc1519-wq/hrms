@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Purchase Orders</h4>
        <p class="page-subtitle mb-0">Orders placed with {{ $supplier->name }}</p>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr>
                    <th>PO Number</th>
                    <th>Organization</th>
                    <th>Order Date</th>
                    <th>Expected Delivery</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $po)
                @php
                    $statusColor = match($po->status) {
                        'draft'              => 'secondary',
                        'sent'               => 'info',
                        'confirmed'          => 'primary',
                        'partially_received' => 'warning',
                        'received'           => 'success',
                        'cancelled'          => 'danger',
                        default              => 'secondary',
                    };
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $po->po_number }}</td>
                    <td>{{ $po->organization?->name ?? '—' }}</td>
                    <td>{{ $po->order_date->format('d-m-Y') }}</td>
                    <td>{{ $po->expected_delivery_date?->format('d-m-Y') ?? '—' }}</td>
                    <td>&#8377;{{ number_format($po->total_amount, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $statusColor }}">
                            {{ ucwords(str_replace('_', ' ', $po->status)) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('supplier.purchase-orders.show', $po) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
                        No purchase orders yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div class="card-footer bg-white">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
