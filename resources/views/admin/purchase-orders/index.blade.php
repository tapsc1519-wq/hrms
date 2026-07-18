@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Purchase Orders</h4>
        <p class="page-subtitle mb-0">{{ $orders->total() }} orders total</p>
    </div>
    <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New PO
    </a>
</div>

<!-- Filters -->
<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search PO number..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach(['draft','sent','confirmed','partially_received','received','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="vendor_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $v)
                        <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th>Expected Delivery</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th width="80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $po)
                <tr>
                    <td>
                        <a href="{{ route('admin.purchase-orders.show', $po) }}" class="text-decoration-none fw-600 text-primary">
                            {{ $po->po_number }}
                        </a>
                    </td>
                    <td>{{ $po->supplier->name }}</td>
                    <td>{{ $po->order_date->format('d-m-Y') }}</td>
                    <td>
                        @if($po->expected_delivery_date)
                            <span class="{{ $po->expected_delivery_date->isPast() && !in_array($po->status, ['received', 'cancelled']) ? 'text-danger' : '' }}">
                                {{ $po->expected_delivery_date->format('d-m-Y') }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="fw-600">&#8377;{{ number_format($po->total_amount, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $po->status_badge }}">
                            {{ ucwords(str_replace('_', ' ', $po->status)) }}
                        </span>
                    </td>
                    <td>{{ $po->createdBy->name }}</td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('admin.purchase-orders.show', $po) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                @if(in_array($po->status, ['draft', 'cancelled']))
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('admin.purchase-orders.destroy', $po) }}" method="POST"
                                          onsubmit="return confirm('Delete this purchase order?')">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                    </form>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        @include('partials._empty_state', [
                            'icon' => 'bi-file-earmark-text',
                            'title' => request()->hasAny(['search', 'status', 'vendor_id']) ? 'No purchase orders match these filters' : 'Create your first purchase order',
                            'message' => request()->hasAny(['search', 'status', 'vendor_id'])
                                ? 'Clear filters or search by PO number, supplier, or creator.'
                                : 'Use purchase orders to track procurement, goods receipt, assets and software licenses.',
                            'actionRoute' => route('admin.purchase-orders.create'),
                            'actionLabel' => 'Create Purchase Order',
                            'secondaryRoute' => request()->hasAny(['search', 'status', 'vendor_id']) ? route('admin.purchase-orders.index') : null,
                        ])
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="card-footer bg-white border-top-0">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
