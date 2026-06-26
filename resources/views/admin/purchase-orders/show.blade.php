@extends('layouts.app')

@section('title', 'Purchase Order ' . $purchaseOrder->po_number)

@section('content')
@php
    $statusTransitions = [
        'draft' => ['sent', 'confirmed', 'cancelled'],
        'sent' => ['confirmed', 'cancelled'],
        'confirmed' => ['cancelled'],
    ];
    $availableStatuses = $statusTransitions[$purchaseOrder->status] ?? [];
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">{{ $purchaseOrder->po_number }}</h4>
        <p class="page-subtitle mb-0">
            <a href="{{ route('admin.purchase-orders.index') }}" class="text-decoration-none">Purchase Orders</a>
            / {{ $purchaseOrder->po_number }}
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        @if(in_array($purchaseOrder->status, ['sent', 'confirmed', 'partially_received']) && $purchaseOrder->items->contains(fn($item) => $item->pending_quantity > 0))
        <a href="{{ route('admin.purchase-orders.receive', $purchaseOrder) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-box-arrow-in-down"></i> Receive Items
        </a>
        @endif
        <span class="badge bg-{{ $purchaseOrder->status_badge }} fs-6">{{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}</span>
        @if($availableStatuses)
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                Update Status
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($availableStatuses as $s)
                <li>
                    <form action="{{ route('admin.purchase-orders.status', $purchaseOrder) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $s }}">
                        <button class="dropdown-item">{{ ucwords(str_replace('_', ' ', $s)) }}</button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <!-- PO Details -->
        <div class="table-card mb-3">
            <div class="card-header"><span class="fw-600">Order Information</span></div>
            <div class="card-body">
                <div class="row g-3" style="font-size:.875rem">
                    <div class="col-md-6">
                        <dl class="mb-0">
                            <dt class="text-muted">Supplier</dt>
                            <dd class="fw-500 fs-6">{{ $purchaseOrder->supplier->name }}</dd>
                            <dt class="text-muted">Contact Person</dt>
                            <dd>{{ $purchaseOrder->supplier->contact_person ?? '—' }}</dd>
                            <dt class="text-muted">Supplier Email</dt>
                            <dd>{{ $purchaseOrder->supplier->email ?? '—' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="mb-0">
                            <dt class="text-muted">Order Date</dt>
                            <dd>{{ $purchaseOrder->order_date->format('d-m-Y') }}</dd>
                            <dt class="text-muted">Expected Delivery</dt>
                            <dd>{{ $purchaseOrder->expected_delivery_date?->format('d-m-Y') ?? '—' }}</dd>
                            <dt class="text-muted">Created By</dt>
                            <dd>{{ $purchaseOrder->createdBy->name }}</dd>
                        </dl>
                    </div>
                    @if($purchaseOrder->notes)
                    <div class="col-12">
                        <dt class="text-muted">Notes</dt>
                        <dd>{{ $purchaseOrder->notes }}</dd>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="table-card mb-3">
            <div class="card-header"><span class="fw-600">Line Items</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.875rem">
                    <thead class="table-light">
                        <tr><th>#</th><th>Item</th><th>Category</th><th>Qty</th><th>Received</th><th>Unit Price</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $item->item_name }}</strong>
                                @if($item->brand || $item->model)
                                <br><small class="text-muted">{{ $item->brand }} {{ $item->model }}</small>
                                @endif
                            </td>
                            <td><small>{{ $item->category?->name ?? '—' }}</small></td>
                            <td>{{ $item->quantity }}</td>
                            <td>
                                <span class="{{ $item->received_quantity < $item->quantity ? 'text-warning' : 'text-success' }}">
                                    {{ $item->received_quantity }}
                                </span>
                            </td>
                            <td>&#8377;{{ number_format($item->unit_price, 2) }}</td>
                            <td class="fw-500">&#8377;{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr><td colspan="6" class="text-end">Subtotal</td><td class="fw-500">&#8377;{{ number_format($purchaseOrder->subtotal, 2) }}</td></tr>
                        <tr><td colspan="6" class="text-end text-muted">Tax</td><td>&#8377;{{ number_format($purchaseOrder->tax_amount, 2) }}</td></tr>
                        <tr><td colspan="6" class="text-end text-muted">Discount</td><td>-&#8377;{{ number_format($purchaseOrder->discount_amount, 2) }}</td></tr>
                        <tr><td colspan="6" class="text-end fw-700">Total</td><td class="fw-700 text-primary fs-5">&#8377;{{ number_format($purchaseOrder->total_amount, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="table-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-600">Goods Receipts</span>
                <span class="badge bg-light text-dark">{{ $purchaseOrder->goodsReceipts->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.82rem">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Receipt</th>
                            <th>Date</th>
                            <th>Invoice / Delivery Note</th>
                            <th>Received By</th>
                            <th class="text-end pe-3">Received / Rejected</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrder->goodsReceipts->sortByDesc('received_date') as $receipt)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $receipt->receipt_number }}</td>
                                <td>{{ $receipt->received_date->format('d-m-Y') }}</td>
                                <td>
                                    <div>{{ $receipt->invoice_number ?: 'No invoice' }}</div>
                                    <small class="text-muted">{{ $receipt->delivery_note_number ?: 'No delivery note' }}</small>
                                </td>
                                <td>{{ $receipt->receivedBy->name }}</td>
                                <td class="text-end pe-3">
                                    <span class="text-success">{{ $receipt->items->sum('received_quantity') }}</span>
                                    /
                                    <span class="text-danger">{{ $receipt->items->sum('rejected_quantity') }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No receipts recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Invoice -->
        <div class="table-card mb-3">
            <div class="card-header"><span class="fw-600">Invoice</span></div>
            <div class="card-body" style="font-size:.875rem">
                @if($purchaseOrder->invoice)
                <dl class="mb-0">
                    <dt class="text-muted">Invoice #</dt>
                    <dd>{{ $purchaseOrder->invoice->invoice_number }}</dd>
                    <dt class="text-muted">Amount</dt>
                    <dd class="fw-500">&#8377;{{ number_format($purchaseOrder->invoice->total_amount, 2) }}</dd>
                    <dt class="text-muted">Status</dt>
                    <dd><span class="badge bg-{{ $purchaseOrder->invoice->status_badge }}">{{ ucfirst($purchaseOrder->invoice->status) }}</span></dd>
                </dl>
                @else
                <p class="text-muted mb-0">No invoice linked to this PO.</p>
                @endif
            </div>
        </div>

        @if($purchaseOrder->shipping_address)
        <div class="table-card mb-3">
            <div class="card-header"><span class="fw-600">Shipping Address</span></div>
            <div class="card-body" style="font-size:.875rem">{{ $purchaseOrder->shipping_address }}</div>
        </div>
        @endif

        @if($purchaseOrder->terms_conditions)
        <div class="table-card">
            <div class="card-header"><span class="fw-600">Terms & Conditions</span></div>
            <div class="card-body" style="font-size:.875rem">{{ $purchaseOrder->terms_conditions }}</div>
        </div>
        @endif
    </div>
</div>
@endsection
