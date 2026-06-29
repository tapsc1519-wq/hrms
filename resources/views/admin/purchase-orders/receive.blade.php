@extends('layouts.app')

@section('title', 'Receive ' . $purchaseOrder->po_number)

@section('content')
<div class="page-header">
    <a href="{{ route('admin.purchase-orders.show', $purchaseOrder) }}" class="back-link">
        <i class="bi bi-arrow-left"></i> {{ $purchaseOrder->po_number }}
    </a>
    <h4>Receive Purchase Order</h4>
    <p>Record delivered assets or software seats and create traceable inventory records.</p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Receipt could not be recorded.</strong>
        <div>{{ $errors->first() }}</div>
    </div>
@endif

<form method="POST" action="{{ route('admin.purchase-orders.receipts.store', $purchaseOrder) }}">
    @csrf

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="icon-wrap icon-blue"><i class="bi bi-box-arrow-in-down"></i></span>
                    Items to Receive
                </div>
                <div class="form-card-body">
                    <div class="alert alert-info py-2 small">
                        Asset lines create one asset per unit. Software lines create license seats and automatically allocate them to linked approved requests.
                    </div>

                    @foreach($purchaseOrder->items as $item)
                        @continue($item->pending_quantity === 0)
                        <div class="border rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between gap-3 mb-3">
                                <div>
                                    <div class="fw-bold">{{ $item->item_name }} <span class="badge bg-light text-dark ms-1">{{ ucfirst($item->item_type) }}</span></div>
                                    <div class="text-muted small">
                                        {{ trim($item->brand . ' ' . $item->model) ?: ($item->category?->name ?? 'Uncategorized') }}
                                    </div>
                                </div>
                                <div class="text-end small">
                                    <div><span class="text-muted">Ordered:</span> {{ $item->quantity }}</div>
                                    <div><span class="text-muted">Already received:</span> {{ $item->received_quantity }}</div>
                                    <div class="fw-bold text-primary">Pending: {{ $item->pending_quantity }}</div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Received Quantity</label>
                                    <input type="number"
                                           name="items[{{ $item->id }}][quantity]"
                                           class="form-control @error('items.' . $item->id . '.quantity') is-invalid @enderror"
                                           value="{{ old('items.' . $item->id . '.quantity', 0) }}"
                                           min="0"
                                           max="{{ $item->pending_quantity }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Rejected Quantity</label>
                                    <input type="number"
                                           name="items[{{ $item->id }}][rejected_quantity]"
                                           class="form-control"
                                           value="{{ old('items.' . $item->id . '.rejected_quantity', 0) }}"
                                           min="0"
                                           max="{{ $item->pending_quantity }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Line Notes</label>
                                    <input type="text"
                                           name="items[{{ $item->id }}][notes]"
                                           class="form-control"
                                           value="{{ old('items.' . $item->id . '.notes') }}"
                                           placeholder="Damage, shortage, packaging condition...">
                                </div>
                                @if($item->item_type === 'asset')
                                <div class="col-md-6">
                                    <label class="form-label">Serial Numbers</label>
                                    <textarea name="items[{{ $item->id }}][serial_numbers]"
                                              class="form-control @error('items.' . $item->id . '.serial_numbers') is-invalid @enderror"
                                              rows="4"
                                              placeholder="One serial number per received unit">{{ old('items.' . $item->id . '.serial_numbers') }}</textarea>
                                    @error('items.' . $item->id . '.serial_numbers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                @else
                                <div class="col-md-6">
                                    <label class="form-label">License Key / Subscription ID</label>
                                    <input type="text" name="items[{{ $item->id }}][license_key]" class="form-control" value="{{ old('items.' . $item->id . '.license_key') }}" placeholder="Optional license or tenant reference">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="items[{{ $item->id }}][expiry_date]" class="form-control" value="{{ old('items.' . $item->id . '.expiry_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Renewal Date</label>
                                    <input type="date" name="items[{{ $item->id }}][renewal_date]" class="form-control" value="{{ old('items.' . $item->id . '.renewal_date') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Agreement Number</label>
                                    <input type="text" name="items[{{ $item->id }}][agreement_number]" class="form-control" value="{{ old('items.' . $item->id . '.agreement_number') }}" placeholder="Contract or agreement reference">
                                </div>
                                @if($item->softwareRequests->isNotEmpty())
                                <div class="col-12">
                                    <div class="border rounded p-2 bg-light">
                                        <div class="small text-primary mb-1"><i class="bi bi-people me-1"></i>{{ $item->open_software_request_count }} open employee request(s) are linked to this line.</div>
                                        <div class="d-flex gap-2 flex-wrap small">
                                            <span class="badge bg-light text-dark border">{{ $item->linked_software_request_count }} linked</span>
                                            <span class="badge bg-success">{{ $item->fulfilled_software_request_count }} fulfilled</span>
                                            <span class="badge bg-{{ $item->unfulfilled_software_request_count > 0 ? 'warning text-dark' : 'light text-dark border' }}">{{ $item->unfulfilled_software_request_count }} waiting</span>
                                            <span class="badge bg-primary">{{ $item->received_software_seat_count }} seats already received</span>
                                        </div>
                                        @if($item->softwareRequests->where('status', 'approved')->isNotEmpty())
                                            <div class="text-muted small mt-2">
                                                Receiving seats allocates first to the oldest approved requests by needed-by date.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                @endif
                                <div class="col-md-6">
                                    <label class="form-label">Asset Tags <span class="text-muted">(optional)</span></label>
                                    <textarea name="items[{{ $item->id }}][asset_tags]"
                                              class="form-control @error('items.' . $item->id . '.asset_tags') is-invalid @enderror"
                                              rows="4"
                                              placeholder="One tag per unit, or leave blank to auto-generate">{{ old('items.' . $item->id . '.asset_tags') }}</textarea>
                                    @error('items.' . $item->id . '.asset_tags')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="icon-wrap icon-green"><i class="bi bi-receipt"></i></span>
                    Receipt Details
                </div>
                <div class="form-card-body">
                    <div class="mb-3">
                        <label class="form-label">Received Date <span class="req">*</span></label>
                        <input type="date" name="received_date" class="form-control" value="{{ old('received_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Invoice Number</label>
                        <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery Note Number</label>
                        <input type="text" name="delivery_note_number" class="form-control" value="{{ old('delivery_note_number') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock Location</label>
                        <select name="location_id" class="form-select">
                            <option value="">Select location</option>
                            @foreach($facilities as $facility)
                                @if($facility->activeLocations->isNotEmpty())
                                    <optgroup label="{{ $facility->name }}">
                                        @foreach($facility->activeLocations as $location)
                                            <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition <span class="req">*</span></label>
                        <select name="condition" class="form-select" required>
                            @foreach(['excellent', 'good', 'fair', 'poor'] as $condition)
                                <option value="{{ $condition }}" @selected(old('condition', 'good') === $condition)>{{ ucfirst($condition) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warranty Expiry</label>
                        <input type="date" name="warranty_expiry_date" class="form-control" value="{{ old('warranty_expiry_date') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warranty Terms</label>
                        <input type="text" name="warranty_terms" class="form-control" value="{{ old('warranty_terms') }}" placeholder="3-year onsite warranty">
                    </div>
                    <div>
                        <label class="form-label">Receipt Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
        <a href="{{ route('admin.purchase-orders.show', $purchaseOrder) }}" class="btn-cancel">Cancel</a>
        <button class="btn btn-primary btn-save">
            <i class="bi bi-box-arrow-in-down"></i> Record Receipt
        </button>
    </div>
</form>
@endsection
