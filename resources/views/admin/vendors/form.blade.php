@extends('layouts.app')

@section('title', $vendor->exists ? 'Edit Vendor' : 'Add Vendor')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.vendors.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Vendors</a>
    <h4>{{ $vendor->exists ? 'Edit Vendor' : 'Add Vendor' }}</h4>
    <p>Register repair, AMC, service, or support vendors separately from procurement suppliers.</p>
</div>

<form action="{{ $vendor->exists ? route('admin.vendors.update', $vendor) : route('admin.vendors.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($vendor->exists)
        @method('PUT')
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-tools"></i></span>Vendor Information</div>
                <div class="form-card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Vendor Name <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $vendor->name) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vendor Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $vendor->code) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach(['active','inactive','blacklisted'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $vendor->status ?: 'active') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $vendor->email) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $vendor->phone) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Website</label>
                            <input type="url" name="website" class="form-control" value="{{ old('website', $vendor->website) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $vendor->contact_person) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $vendor->contact_phone) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $vendor->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $vendor->city) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="{{ old('country', $vendor->country) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax Number</label>
                            <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number', $vendor->tax_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rating</label>
                            <input type="number" name="rating" class="form-control" value="{{ old('rating', $vendor->rating ?? 0) }}" step="0.1" min="0" max="5">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bank Details</label>
                            <textarea name="bank_details" class="form-control" rows="2">{{ old('bank_details', $vendor->bank_details) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Service capabilities, repair categories, AMC terms, or escalation notes.">{{ old('notes', $vendor->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-card">
                <div class="form-card-header"><span class="icon-wrap icon-teal"><i class="bi bi-image"></i></span>Logo</div>
                <div class="form-card-body">
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <div class="form-text">PNG or JPG up to 2MB.</div>
                </div>
            </div>
            <div class="form-card">
                <div class="form-actions">
                    <a href="{{ route('admin.vendors.index') }}" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-save"><i class="bi bi-check2-circle"></i>Save Vendor</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
