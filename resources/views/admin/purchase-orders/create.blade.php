@extends('layouts.app')
@section('title', 'Create Purchase Order')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.purchase-orders.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Purchase Orders</a>
    <h4>Create Purchase Order</h4>
    <p>Raise a new purchase order to a vendor for IT assets or services.</p>
</div>

<form action="{{ route('admin.purchase-orders.store') }}" method="POST" id="poForm">
@csrf
<div class="row g-4">

    {{-- LEFT --}}
    <div class="col-lg-8">

        {{-- PO Header --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-file-earmark-text"></i></span>
                Order Details
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">PO Number <span class="req">*</span></label>
                        <input type="text" name="po_number" class="form-control fw-semibold @error('po_number') is-invalid @enderror"
                               value="{{ old('po_number', $poNumber) }}" required>
                        @error('po_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supplier <span class="req">*</span></label>
                        <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required>
                            <option value="">— Select Supplier —</option>
                            @foreach($suppliers as $v)
                            <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected':'' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="draft"     {{ old('status','draft') == 'draft'     ? 'selected':'' }}>Draft</option>
                            <option value="sent"      {{ old('status') == 'sent'      ? 'selected':'' }}>Sent to Supplier</option>
                            <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected':'' }}>Confirmed</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Order Date <span class="req">*</span></label>
                        <input type="date" name="order_date" class="form-control @error('order_date') is-invalid @enderror"
                               value="{{ old('order_date', today()->format('Y-m-d')) }}" required>
                        @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Expected Delivery</label>
                        <input type="date" name="expected_delivery_date" class="form-control"
                               value="{{ old('expected_delivery_date') }}">
                    </div>
                    <div class="col-md-4">
                        {{-- spacer --}}
                    </div>
                    <div class="col-12">
                        <label class="form-label">Shipping Address</label>
                        <textarea name="shipping_address" class="form-control" rows="2"
                                  placeholder="Delivery address for this order">{{ old('shipping_address') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Internal notes about this purchase order">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="form-card">
            <div class="form-card-header" style="justify-content:space-between">
                <div style="display:flex;align-items:center;gap:.6rem">
                    <span class="icon-wrap icon-green"><i class="bi bi-list-ul"></i></span>
                    Line Items
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()">
                    <i class="bi bi-plus-lg me-1"></i>Add Item
                </button>
            </div>
            <div class="form-card-body" style="padding-top:.5rem">

                <div id="itemsContainer">
                    {{-- Row 0 --}}
                    <div class="item-row rounded-3 mb-2 p-3" id="item-0"
                         style="background:#f8fafc;border:1.5px solid #e2e8f0">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Item Name <span class="req">*</span></label>
                                <input type="text" name="items[0][item_name]" class="form-control" required placeholder="e.g. Dell Laptop">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Brand</label>
                                <input type="text" name="items[0][brand]" class="form-control" placeholder="Dell">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
                                <select name="items[0][category_id]" class="form-select">
                                    <option value="">None</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Qty <span class="req">*</span></label>
                                <input type="number" name="items[0][quantity]" class="form-control" value="1" min="1" required onchange="recalculate()">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Unit Price <span class="req">*</span></label>
                                <input type="number" name="items[0][unit_price]" class="form-control" value="0" step="0.01" min="0" required onchange="recalculate()">
                            </div>
                            <div class="col-md-1 d-flex align-items-end pb-1 justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(0)" title="Remove">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Totals --}}
                <div class="mt-3 pt-2" style="border-top:1.5px dashed #e2e8f0">
                    <div class="d-flex justify-content-end">
                        <table style="width:300px;font-size:.88rem">
                            <tr>
                                <td class="py-1 text-muted">Subtotal</td>
                                <td class="py-1 text-end fw-semibold" id="subtotalDisplay">&#8377; 0.00</td>
                            </tr>
                            <tr>
                                <td class="py-1 text-muted">Tax (&#8377;)</td>
                                <td class="py-1">
                                    <input type="number" name="tax_amount" id="tax"
                                           class="form-control form-control-sm text-end"
                                           value="0" step="0.01" min="0" onchange="recalculate()">
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 text-muted">Discount (&#8377;)</td>
                                <td class="py-1">
                                    <input type="number" name="discount_amount" id="discount"
                                           class="form-control form-control-sm text-end"
                                           value="0" step="0.01" min="0" onchange="recalculate()">
                                </td>
                            </tr>
                            <tr style="border-top:2px solid #e2e8f0">
                                <td class="pt-2 fw-bold">Total</td>
                                <td class="pt-2 text-end fw-bold text-primary" id="totalDisplay" style="font-size:1.1rem">&#8377; 0.00</td>
                            </tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- RIGHT --}}
    <div class="col-lg-4">

        {{-- Terms --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-purple"><i class="bi bi-file-text"></i></span>
                Terms & Conditions
            </div>
            <div class="form-card-body">
                <textarea name="terms_conditions" class="form-control" rows="8"
                          placeholder="Payment terms, delivery conditions, warranty clauses…">{{ old('terms_conditions') }}</textarea>
            </div>
        </div>

    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('admin.purchase-orders.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Create Purchase Order
    </button>
</div>
</form>
@endsection

@push('scripts')
<script>
let itemCount = 1;
const categories = @json($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name]));

function addItem() {
    const i = itemCount++;
    const row = document.createElement('div');
    row.className = 'item-row rounded-3 mb-2 p-3';
    row.id = `item-${i}`;
    row.style.cssText = 'background:#f8fafc;border:1.5px solid #e2e8f0';
    row.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Item Name <span class="req">*</span></label>
                <input type="text" name="items[${i}][item_name]" class="form-control" required placeholder="Item name">
            </div>
            <div class="col-md-2">
                <label class="form-label">Brand</label>
                <input type="text" name="items[${i}][brand]" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="items[${i}][category_id]" class="form-select">
                    <option value="">None</option>
                    ${categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">Qty</label>
                <input type="number" name="items[${i}][quantity]" class="form-control" value="1" min="1" required onchange="recalculate()">
            </div>
            <div class="col-md-2">
                <label class="form-label">Unit Price</label>
                <input type="number" name="items[${i}][unit_price]" class="form-control" value="0" step="0.01" min="0" required onchange="recalculate()">
            </div>
            <div class="col-md-1 d-flex align-items-end pb-1 justify-content-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${i})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    document.getElementById('itemsContainer').appendChild(row);
}

function removeItem(i) {
    const el = document.getElementById(`item-${i}`);
    if (el && document.querySelectorAll('.item-row').length > 1) {
        el.remove();
        recalculate();
    }
}

function recalculate() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('[name*="[quantity]"]')?.value  || 0);
        const price = parseFloat(row.querySelector('[name*="[unit_price]"]')?.value || 0);
        subtotal += qty * price;
    });
    const tax      = parseFloat(document.getElementById('tax').value      || 0);
    const discount = parseFloat(document.getElementById('discount').value  || 0);
    const total    = subtotal + tax - discount;
    const fmt = n => '\u20B9 ' + n.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    document.getElementById('subtotalDisplay').textContent = fmt(subtotal);
    document.getElementById('totalDisplay').textContent    = fmt(total);
}
</script>
@endpush
