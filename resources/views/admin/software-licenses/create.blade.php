@extends('layouts.app')
@section('title', 'Add License')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.software-licenses.index') }}" class="back-link">
        <i class="bi bi-arrow-left"></i> Licenses
    </a>
    <h4>Add License Record</h4>
    <p>Record a software license purchase for your organization.</p>
</div>

<form method="POST" action="{{ route('admin.software-licenses.store') }}">
@csrf
<div class="row g-4">
    <div class="col-lg-8">

        {{-- Core License Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-blue"><i class="bi bi-key-fill"></i></div>
                License Details
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Software Title <span class="req">*</span></label>
                        <select name="software_id" id="softwareSelect"
                                class="form-select @error('software_id') is-invalid @enderror" required>
                            <option value="">Select software…</option>
                            @foreach($software as $sw)
                                <option value="{{ $sw->id }}"
                                        @selected(old('software_id', $selectedSoftware) == $sw->id)>
                                    {{ $sw->name }}
                                    @if($sw->vendor) — {{ $sw->vendor }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('software_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">License Type <span class="req">*</span></label>
                        <select name="license_type" class="form-select @error('license_type') is-invalid @enderror" required>
                            <option value="">Select type…</option>
                            @foreach([
                                'per_seat'    => 'Per Seat',
                                'perpetual'   => 'Perpetual',
                                'subscription'=> 'Subscription',
                                'concurrent'  => 'Concurrent',
                                'per_device'  => 'Per Device',
                                'volume'      => 'Volume',
                                'oem'         => 'OEM',
                                'open_source' => 'Open Source',
                                'freeware'    => 'Freeware',
                            ] as $val => $label)
                                <option value="{{ $val }}" @selected(old('license_type') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('license_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Number of Seats <span class="req">*</span></label>
                        <input type="number" name="seats" value="{{ old('seats', 1) }}" min="1" max="99999"
                               class="form-control @error('seats') is-invalid @enderror" required>
                        @error('seats')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">License Key</label>
                        <input type="text" name="license_key" value="{{ old('license_key') }}"
                               class="form-control @error('license_key') is-invalid @enderror"
                               placeholder="XXXX-XXXX-XXXX-XXXX" style="font-family:monospace">
                        @error('license_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="Additional notes about this license…">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Purchase Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-green"><i class="bi bi-receipt-cutoff"></i></div>
                Purchase Information
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Supplier</label>
                        <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
                            <option value="">No vendor selected</option>
                            @foreach($suppliers as $v)
                                <option value="{{ $v->id }}" @selected(old('vendor_id') == $v->id)>{{ $v->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PO Number</label>
                        <input type="text" name="po_number" value="{{ old('po_number') }}"
                               class="form-control @error('po_number') is-invalid @enderror"
                               placeholder="e.g. PO-2024-0042">
                        @error('po_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" value="{{ old('purchase_date') }}"
                               class="form-control @error('purchase_date') is-invalid @enderror">
                        @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" value="{{ old('expiry_date') }}"
                               class="form-control @error('expiry_date') is-invalid @enderror">
                        @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Price</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-right:0;border-radius:9px 0 0 9px">$</span>
                            <input type="number" name="purchase_price" value="{{ old('purchase_price') }}"
                                   step="0.01" min="0"
                                   class="form-control @error('purchase_price') is-invalid @enderror"
                                   placeholder="0.00" style="border-radius:0 9px 9px 0;border-left:0">
                        </div>
                        @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="col-lg-4">

        {{-- Help Card --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-amber"><i class="bi bi-info-circle-fill"></i></div>
                Quick Tips
            </div>
            <div class="form-card-body">
                <ul class="list-unstyled mb-0" style="font-size:.82rem;color:#64748b;line-height:1.8">
                    <li class="mb-2"><i class="bi bi-dot text-primary me-1"></i><strong>Seats</strong> = number of users who can use this license simultaneously or in total.</li>
                    <li class="mb-2"><i class="bi bi-dot text-primary me-1"></i>Leave <strong>Expiry Date</strong> empty for perpetual licenses.</li>
                    <li class="mb-2"><i class="bi bi-dot text-primary me-1"></i>Licenses expiring within 30 days will appear as warnings in the compliance overview.</li>
                    <li><i class="bi bi-dot text-primary me-1"></i>After adding a license, go to its detail page to assign it to employees.</li>
                </ul>
            </div>
        </div>

    </div>
</div>

<div class="form-actions">
    <a href="{{ route('admin.software-licenses.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Save License
    </button>
</div>
</form>
@endsection
