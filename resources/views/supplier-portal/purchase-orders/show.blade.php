@extends('layouts.app')

@section('title', 'PO #' . $purchaseOrder->po_number)

@section('content')
<div class="mb-4">
    <a href="{{ route('supplier.purchase-orders.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i>Back to Purchase Orders
    </a>
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h4 class="page-title mb-0">{{ $purchaseOrder->po_number }}</h4>
            <p class="page-subtitle mb-0">{{ $purchaseOrder->organization?->name }}</p>
        </div>
        @php
            $statusColor = match($purchaseOrder->status) {
                'draft'              => 'secondary',
                'sent'               => 'info',
                'confirmed'          => 'primary',
                'partially_received' => 'warning',
                'received'           => 'success',
                'cancelled'          => 'danger',
                default              => 'secondary',
            };
        @endphp
        <span class="badge bg-{{ $statusColor }} fs-6 px-3 py-2">
            {{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}
        </span>
    </div>
</div>

<div class="row g-3">
    <!-- Order Details -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Order Details</div>
            <div class="card-body small">
                <div class="row g-2">
                    <div class="col-6 text-muted">Order Date</div>
                    <div class="col-6">{{ $purchaseOrder->order_date->format('d-m-Y') }}</div>

                    <div class="col-6 text-muted">Expected Delivery</div>
                    <div class="col-6">{{ $purchaseOrder->expected_delivery_date?->format('d-m-Y') ?? '—' }}</div>

                    <div class="col-6 text-muted">Actual Delivery</div>
                    <div class="col-6">{{ $purchaseOrder->actual_delivery_date?->format('d-m-Y') ?? '—' }}</div>

                    <div class="col-6 text-muted">Created By</div>
                    <div class="col-6">{{ $purchaseOrder->createdBy?->name ?? '—' }}</div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Financial Summary</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Subtotal</span>
                    <span>&#8377;{{ number_format($purchaseOrder->subtotal, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Tax</span>
                    <span>&#8377;{{ number_format($purchaseOrder->tax_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Discount</span>
                    <span>-&#8377;{{ number_format($purchaseOrder->discount_amount, 2) }}</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span class="text-primary">&#8377;{{ number_format($purchaseOrder->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="col-lg-8">
        <div class="table-card">
            <div class="card-header bg-white fw-semibold">Order Items</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th class="text-center">Qty Ordered</th>
                            <th class="text-center">Qty Received</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrder->items as $item)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $item->item_name }}</div>
                                <small class="text-muted">{{ $item->brand }} {{ $item->model }}</small>
                            </td>
                            <td>{{ $item->category?->name ?? '—' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-center">
                                <span class="{{ $item->received_quantity >= $item->quantity ? 'text-success' : 'text-warning' }}">
                                    {{ $item->received_quantity }}
                                </span>
                            </td>
                            <td class="text-end">&#8377;{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-semibold">&#8377;{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No items found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($purchaseOrder->notes)
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Notes</div>
            <div class="card-body small text-muted">{{ $purchaseOrder->notes }}</div>
        </div>
        @endif
    </div>
</div>
@endsection
