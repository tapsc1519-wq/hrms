@extends('layouts.app')
@section('title', 'Create Purchase Order')

@section('content')
@php
    $initialItems = old('items');
    if ($initialItems === null) {
        $initialItems = $softwareDemand->isNotEmpty()
            ? $softwareDemand->map(fn ($demand) => array_merge($demand, [
                'item_type' => 'software',
                'license_type' => 'subscription',
                'subscription_period' => 'annual',
                'unit_price' => 0,
            ]))->all()
            : [['item_type' => 'asset', 'item_name' => '', 'quantity' => 1, 'unit_price' => 0]];
    }
@endphp

<div class="page-header">
    <a href="{{ route('admin.purchase-orders.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Purchase Orders</a>
    <h4>Create Purchase Order</h4>
    <p>Purchase physical assets or software license seats with a traceable receiving record.</p>
</div>

@if($softwareDemand->isNotEmpty())
<div class="alert alert-info d-flex gap-2 align-items-start">
    <i class="bi bi-person-check-fill mt-1"></i>
    <div><strong>Approved software demand has been added.</strong> This PO opened with software line items because it was started from approved software requests. For hardware purchase, add another item and keep Item Type as Asset.</div>
</div>
@endif

@if($categories->isEmpty())
<div class="alert alert-warning d-flex gap-2 align-items-start">
    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
    <div>
        <strong>No physical asset categories are available for purchase orders.</strong>
        Create categories such as Laptop, Desktop, Printer, Network Switch or Mobile in Asset Catalog first. Software should be purchased using Item Type = Software, not as an asset category.
        <div class="mt-2">
            <a href="{{ route('admin.catalog.index', ['tab' => 'categories']) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-box-arrow-up-right me-1"></i>Open Asset Catalog
            </a>
        </div>
    </div>
</div>
@endif

<form action="{{ route('admin.purchase-orders.store') }}" method="POST" id="poForm">
@csrf
<div class="row g-4">
    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-file-earmark-text"></i></span>Order Details</div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">PO Number <span class="req">*</span></label><input type="text" name="po_number" class="form-control fw-semibold @error('po_number') is-invalid @enderror" value="{{ old('po_number', $poNumber) }}" required>@error('po_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label">Supplier <span class="req">*</span></label><select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required><option value="">Select supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('vendor_id') == $supplier->id)>{{ $supplier->name }}</option>@endforeach</select>@error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label">Status <span class="req">*</span></label><select name="status" class="form-select" required>@foreach(['draft' => 'Draft', 'sent' => 'Sent to Supplier', 'confirmed' => 'Confirmed'] as $value => $label)<option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Order Date <span class="req">*</span></label><input type="date" name="order_date" class="form-control" value="{{ old('order_date', today()->toDateString()) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Expected Delivery</label><input type="date" name="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date') }}"></div>
                    <div class="col-12"><label class="form-label">Shipping Address</label><textarea name="shipping_address" class="form-control" rows="2" placeholder="Delivery or billing address">{{ old('shipping_address') }}</textarea></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Internal notes about this purchase">{{ old('notes') }}</textarea></div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header d-flex justify-content-between">
                <div class="d-flex align-items-center gap-2"><span class="icon-wrap icon-green"><i class="bi bi-list-ul"></i></span>Line Items</div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()"><i class="bi bi-plus-lg me-1"></i>Add Item</button>
            </div>
            <div class="form-card-body pt-2">
                <div id="itemsContainer">
                    @foreach($initialItems as $index => $item)
                    @php
                        $type = $item['item_type'] ?? 'asset';
                        $requestIds = $item['software_request_ids'] ?? [];
                        $employeeNames = $item['employee_names'] ?? [];
                    @endphp
                    <div class="item-row rounded-3 mb-3 p-3" id="item-{{ $index }}" style="background:#f8fafc;border:1.5px solid #e2e8f0" data-index="{{ $index }}">
                        @foreach($requestIds as $requestId)<input type="hidden" name="items[{{ $index }}][software_request_ids][]" value="{{ $requestId }}">@endforeach
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2"><label class="form-label">Item Type</label><select name="items[{{ $index }}][item_type]" class="form-select item-type" onchange="updateItemType(this)"><option value="asset" @selected($type === 'asset')>Asset</option><option value="software" @selected($type === 'software')>Software</option></select></div>
                            <div class="col-md-4"><label class="form-label">Item Name <span class="req">*</span></label><input type="text" name="items[{{ $index }}][item_name]" class="form-control" value="{{ $item['item_name'] ?? '' }}" required placeholder="Product or license name"></div>
                            <div class="col-md-3 asset-field {{ $type === 'software' ? 'd-none' : '' }}"><label class="form-label">Physical Asset Category</label><select name="items[{{ $index }}][category_id]" class="form-select"><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(($item['category_id'] ?? null) == $category->id)>{{ $category->name }}</option>@endforeach</select><div class="form-text">Laptop, Desktop, Printer, Mobile, Network device etc.</div></div>
                            <div class="col-md-3 software-field {{ $type === 'asset' ? 'd-none' : '' }}"><label class="form-label">Software <span class="req">*</span></label><select name="items[{{ $index }}][software_id]" class="form-select software-select"><option value="">Select software</option>@foreach($softwareList as $software)<option value="{{ $software->id }}" @selected(($item['software_id'] ?? null) == $software->id)>{{ $software->name }}</option>@endforeach</select></div>
                            <div class="col-md-1"><label class="form-label">Qty</label><input type="number" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $item['quantity'] ?? 1 }}" min="{{ max(1, count($requestIds)) }}" required oninput="recalculate()"></div>
                            <div class="col-md-2"><label class="form-label">Unit Price</label><input type="number" name="items[{{ $index }}][unit_price]" class="form-control" value="{{ $item['unit_price'] ?? 0 }}" step="0.01" min="0" required oninput="recalculate()"></div>
                        </div>
                        <div class="row g-2 align-items-end mt-1">
                            <div class="col-md-3"><label class="form-label">Brand / Publisher</label><input type="text" name="items[{{ $index }}][brand]" class="form-control" value="{{ $item['brand'] ?? '' }}"></div>
                            <div class="col-md-3 asset-field {{ $type === 'software' ? 'd-none' : '' }}"><label class="form-label">Model</label><input type="text" name="items[{{ $index }}][model]" class="form-control" value="{{ $item['model'] ?? '' }}"></div>
                            <div class="col-md-3 software-field {{ $type === 'asset' ? 'd-none' : '' }}"><label class="form-label">License Type</label><select name="items[{{ $index }}][license_type]" class="form-select">@foreach(['subscription' => 'Subscription', 'per_seat' => 'Per Seat', 'perpetual' => 'Perpetual', 'concurrent' => 'Concurrent', 'per_device' => 'Per Device', 'volume' => 'Volume'] as $value => $label)<option value="{{ $value }}" @selected(($item['license_type'] ?? 'subscription') === $value)>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-3 software-field {{ $type === 'asset' ? 'd-none' : '' }}"><label class="form-label">Term</label><select name="items[{{ $index }}][subscription_period]" class="form-select">@foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annual' => 'Annual', 'multi_year' => 'Multi-year', 'perpetual' => 'Perpetual'] as $value => $label)<option value="{{ $value }}" @selected(($item['subscription_period'] ?? 'annual') === $value)>{{ $label }}</option>@endforeach</select></div>
                            <div class="col"><label class="form-label">Description</label><input type="text" name="items[{{ $index }}][description]" class="form-control" value="{{ $item['description'] ?? '' }}" placeholder="Specifications or coverage"></div>
                            <div class="col-auto"><button type="button" class="btn btn-outline-danger" onclick="removeItem({{ $index }})" title="Remove"><i class="bi bi-trash"></i></button></div>
                        </div>
                        @if($employeeNames)<div class="small text-primary mt-2"><i class="bi bi-people me-1"></i>Approved demand for {{ implode(', ', $employeeNames) }}</div>@endif
                    </div>
                    @endforeach
                </div>

                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-end"><table style="width:320px;font-size:.88rem"><tr><td class="py-1 text-muted">Subtotal</td><td class="text-end fw-semibold" id="subtotalDisplay">Rs 0.00</td></tr><tr><td class="py-1 text-muted">Tax</td><td><input type="number" name="tax_amount" id="tax" class="form-control form-control-sm text-end" value="{{ old('tax_amount', 0) }}" step="0.01" min="0" oninput="recalculate()"></td></tr><tr><td class="py-1 text-muted">Discount</td><td><input type="number" name="discount_amount" id="discount" class="form-control form-control-sm text-end" value="{{ old('discount_amount', 0) }}" step="0.01" min="0" oninput="recalculate()"></td></tr><tr class="border-top"><td class="pt-2 fw-bold">Total</td><td class="pt-2 text-end fw-bold text-primary" id="totalDisplay">Rs 0.00</td></tr></table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card"><div class="form-card-header"><span class="icon-wrap icon-purple"><i class="bi bi-file-text"></i></span>Terms & Conditions</div><div class="form-card-body"><textarea name="terms_conditions" class="form-control" rows="8" placeholder="Payment terms, delivery conditions, warranty or subscription clauses">{{ old('terms_conditions') }}</textarea></div></div>
    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem"><a href="{{ route('admin.purchase-orders.index') }}" class="btn-cancel">Cancel</a><button type="submit" class="btn btn-primary btn-save"><i class="bi bi-check-lg"></i>Create Purchase Order</button></div>
</form>
@endsection

@push('scripts')
<script>
let itemCount = {{ $initialItems ? max(array_map('intval', array_keys($initialItems))) + 1 : 0 }};
const poCategories = @json($categories->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values());
const poSoftware = @json($softwareList->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values());
const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const optionsFor = items => items.map(item => `<option value="${item.id}">${escapeHtml(item.name)}</option>`).join('');

function addItem() {
    const i = itemCount++;
    const row = document.createElement('div');
    row.className = 'item-row rounded-3 mb-3 p-3';
    row.id = `item-${i}`;
    row.dataset.index = i;
    row.style.cssText = 'background:#f8fafc;border:1.5px solid #e2e8f0';
    row.innerHTML = `<div class="row g-2 align-items-end"><div class="col-md-2"><label class="form-label">Item Type</label><select name="items[${i}][item_type]" class="form-select item-type" onchange="updateItemType(this)"><option value="asset">Asset</option><option value="software">Software</option></select></div><div class="col-md-4"><label class="form-label">Item Name *</label><input name="items[${i}][item_name]" class="form-control" required></div><div class="col-md-3 asset-field"><label class="form-label">Physical Asset Category</label><select name="items[${i}][category_id]" class="form-select"><option value="">Select category</option>${optionsFor(poCategories)}</select><div class="form-text">Laptop, Desktop, Printer, Mobile, Network device etc.</div></div><div class="col-md-3 software-field d-none"><label class="form-label">Software *</label><select name="items[${i}][software_id]" class="form-select software-select"><option value="">Select software</option>${optionsFor(poSoftware)}</select></div><div class="col-md-1"><label class="form-label">Qty</label><input type="number" name="items[${i}][quantity]" class="form-control" value="1" min="1" required oninput="recalculate()"></div><div class="col-md-2"><label class="form-label">Unit Price</label><input type="number" name="items[${i}][unit_price]" class="form-control" value="0" min="0" step="0.01" required oninput="recalculate()"></div></div><div class="row g-2 align-items-end mt-1"><div class="col-md-3"><label class="form-label">Brand / Publisher</label><input name="items[${i}][brand]" class="form-control"></div><div class="col-md-3 asset-field"><label class="form-label">Model</label><input name="items[${i}][model]" class="form-control"></div><div class="col-md-3 software-field d-none"><label class="form-label">License Type</label><select name="items[${i}][license_type]" class="form-select"><option value="subscription">Subscription</option><option value="per_seat">Per Seat</option><option value="perpetual">Perpetual</option><option value="concurrent">Concurrent</option><option value="per_device">Per Device</option><option value="volume">Volume</option></select></div><div class="col-md-3 software-field d-none"><label class="form-label">Term</label><select name="items[${i}][subscription_period]" class="form-select"><option value="annual">Annual</option><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="multi_year">Multi-year</option><option value="perpetual">Perpetual</option></select></div><div class="col"><label class="form-label">Description</label><input name="items[${i}][description]" class="form-control"></div><div class="col-auto"><button type="button" class="btn btn-outline-danger" onclick="removeItem(${i})"><i class="bi bi-trash"></i></button></div></div>`;
    document.getElementById('itemsContainer').appendChild(row);
}

function updateItemType(select) {
    const row = select.closest('.item-row');
    const software = select.value === 'software';
    row.querySelectorAll('.software-field').forEach(field => field.classList.toggle('d-none', !software));
    row.querySelectorAll('.asset-field').forEach(field => field.classList.toggle('d-none', software));
    const softwareSelect = row.querySelector('.software-select');
    if (softwareSelect) softwareSelect.required = software;
}

function removeItem(i) {
    if (document.querySelectorAll('.item-row').length > 1) document.getElementById(`item-${i}`)?.remove();
    recalculate();
}

function recalculate() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => subtotal += Number(row.querySelector('[name*="[quantity]"]')?.value || 0) * Number(row.querySelector('[name*="[unit_price]"]')?.value || 0));
    const total = subtotal + Number(document.getElementById('tax').value || 0) - Number(document.getElementById('discount').value || 0);
    const format = value => 'Rs ' + value.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('subtotalDisplay').textContent = format(subtotal);
    document.getElementById('totalDisplay').textContent = format(total);
}

document.querySelectorAll('.item-type').forEach(updateItemType);
recalculate();
</script>
@endpush
