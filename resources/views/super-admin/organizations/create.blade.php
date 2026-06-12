@extends('layouts.app')
@section('title', 'Add Organization')

@section('content')
<div class="page-header">
    <a href="{{ route('super-admin.organizations.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Organizations</a>
    <h4>Add New Organization</h4>
    <p>Register a new tenant organisation on the platform.</p>
</div>

<form action="{{ route('super-admin.organizations.store') }}" method="POST" enctype="multipart/form-data">
@csrf
<div class="row g-4">

    {{-- LEFT --}}
    <div class="col-lg-8">

        {{-- Company Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-building"></i></span>
                Company Information
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Organization Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required placeholder="Company or group name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active"    {{ old('status','active') == 'active'    ? 'selected':'' }}>Active</option>
                            <option value="inactive"  {{ old('status') == 'inactive'  ? 'selected':'' }}>Inactive</option>
                            <option value="suspended" {{ old('status') == 'suspended' ? 'selected':'' }}>Suspended</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        {{-- spacer --}}
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" placeholder="contact@company.com">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone') }}" placeholder="+63 2 8XXX XXXX">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tax Number</label>
                        <input type="text" name="tax_number" class="form-control @error('tax_number') is-invalid @enderror"
                               value="{{ old('tax_number') }}" placeholder="VAT / TIN">
                        @error('tax_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control @error('website') is-invalid @enderror"
                               value="{{ old('website') }}" placeholder="https://company.com">
                        @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-geo-alt"></i></span>
                Address
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Street / Office Address</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror"
                                  rows="2" placeholder="Building, street, barangay">{{ old('address') }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                               value="{{ old('city') }}" placeholder="e.g. Makati">
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
                               value="{{ old('country', 'Philippines') }}">
                        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT --}}
    <div class="col-lg-4">

        {{-- Logo --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-teal"><i class="bi bi-image"></i></span>
                Logo
            </div>
            <div class="form-card-body text-center">
                <div id="logoPreviewWrap" class="mb-3" style="display:none">
                    <img id="logoPreview" class="rounded-3 border"
                         style="max-height:100px;max-width:100%;object-fit:contain">
                </div>
                <div class="border rounded-3 p-3 bg-light"
                     style="border-style:dashed!important;border-color:#e2e8f0!important">
                    <i class="bi bi-cloud-upload fs-2 text-secondary d-block mb-2"></i>
                    <input type="file" name="logo" class="form-control form-control-sm"
                           accept="image/*" onchange="previewLogo(event)">
                    <small class="text-muted d-block mt-1">PNG, JPG up to 2MB</small>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('super-admin.organizations.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-building-add"></i> Create Organization
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
