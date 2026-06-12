@extends('layouts.app')
@section('title', 'Add Supplier')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.suppliers.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Suppliers</a>
    <h4>Add New Supplier</h4>
    <p>Register a supplier or service provider for your organisation.</p>
</div>

<form action="{{ route('admin.suppliers.store') }}" method="POST" enctype="multipart/form-data">
@csrf
<div class="row g-4">

    {{-- LEFT COLUMN --}}
    <div class="col-lg-8">

        {{-- Company Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-shop"></i></span>
                Company Information
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Supplier Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required placeholder="Company or supplier name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Supplier Code</label>
                        <input type="text" name="code" class="form-control"
                               value="{{ old('code') }}" placeholder="e.g. DELL-001">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active"      {{ old('status','active') == 'active'      ? 'selected':'' }}>Active</option>
                            <option value="inactive"    {{ old('status') == 'inactive'    ? 'selected':'' }}>Inactive</option>
                            <option value="blacklisted" {{ old('status') == 'blacklisted' ? 'selected':'' }}>Blacklisted</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email') }}" placeholder="vendor@company.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone') }}" placeholder="+63 2 8XXX XXXX">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control"
                               value="{{ old('website') }}" placeholder="https://...">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tax Number</label>
                        <input type="text" name="tax_number" class="form-control"
                               value="{{ old('tax_number') }}" placeholder="VAT / TIN">
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact Person --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-purple"><i class="bi bi-person-lines-fill"></i></span>
                Contact Person
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control"
                               value="{{ old('contact_person') }}" placeholder="Full name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control"
                               value="{{ old('contact_phone') }}" placeholder="+63 9XX XXX XXXX">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"
                                  placeholder="Street, building, barangay">{{ old('address') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control" value="{{ old('country','Philippines') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Financial --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-bank"></i></span>
                Financial & Notes
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Bank Details</label>
                        <textarea name="bank_details" class="form-control" rows="2"
                                  placeholder="Bank name, account number, branch…">{{ old('bank_details') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Any additional notes about this vendor">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-teal"><i class="bi bi-image"></i></span>
                Logo
            </div>
            <div class="form-card-body text-center">
                <div id="logoPreviewWrap" class="mb-3" style="display:none">
                    <img id="logoPreview" class="rounded-3 border" style="max-height:120px;max-width:100%;object-fit:contain">
                </div>
                <div class="border-2 border-dashed rounded-3 p-4 text-muted" style="border-style:dashed!important;border-color:#e2e8f0!important;background:#f8fafc">
                    <i class="bi bi-cloud-upload fs-2 d-block mb-2 text-secondary"></i>
                    <label class="form-label mb-2 d-block" style="text-transform:none;font-size:.8rem">
                        Click to upload vendor logo
                    </label>
                    <input type="file" name="logo" class="form-control form-control-sm"
                           accept="image/*" onchange="previewLogo(event)">
                    <small class="text-muted d-block mt-2">PNG, JPG up to 2MB</small>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-star-fill"></i></span>
                Rating
            </div>
            <div class="form-card-body">
                <label class="form-label">Initial Rating (0–5)</label>
                <input type="number" name="rating" class="form-control"
                       value="{{ old('rating', 0) }}" step="0.1" min="0" max="5">
                <div class="form-text">Can be updated after reviews.</div>
            </div>
        </div>
    </div>

</div>

{{-- Actions --}}
<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('admin.suppliers.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Add Supplier
    </button>
</div>
</form>
@endsection

@push('scripts')
<script>
function previewLogo(e) {
    const file = e.target.files[0];
    if (!file) return;
    document.getElementById('logoPreviewWrap').style.display = 'block';
    document.getElementById('logoPreview').src = URL.createObjectURL(file);
}
</script>
@endpush
