@extends('layouts.app')
@section('title', 'Edit Supplier — ' . $supplier->name)

@section('content')
<div class="page-header">
    <a href="{{ route('admin.suppliers.show', $supplier) }}" class="back-link"><i class="bi bi-arrow-left"></i> {{ $supplier->name }}</a>
    <h4>Edit Supplier</h4>
    <p>Update details for <strong>{{ $supplier->name }}</strong>.</p>
</div>

<form action="{{ route('admin.suppliers.update', $supplier) }}" method="POST" enctype="multipart/form-data">
@csrf @method('PUT')
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
                               value="{{ old('name', $supplier->name) }}" required placeholder="Company or supplier name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Supplier Code</label>
                        <input type="text" name="code" class="form-control"
                               value="{{ old('code', $supplier->code) }}" placeholder="e.g. DELL-001">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active"      {{ old('status', $supplier->status) == 'active'      ? 'selected':'' }}>Active</option>
                            <option value="inactive"    {{ old('status', $supplier->status) == 'inactive'    ? 'selected':'' }}>Inactive</option>
                            <option value="blacklisted" {{ old('status', $supplier->status) == 'blacklisted' ? 'selected':'' }}>Blacklisted</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', $supplier->email) }}" placeholder="vendor@company.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $supplier->phone) }}" placeholder="+63 2 8XXX XXXX">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control"
                               value="{{ old('website', $supplier->website) }}" placeholder="https://...">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tax Number</label>
                        <input type="text" name="tax_number" class="form-control"
                               value="{{ old('tax_number', $supplier->tax_number) }}" placeholder="VAT / TIN">
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
                               value="{{ old('contact_person', $supplier->contact_person) }}" placeholder="Full name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control"
                               value="{{ old('contact_phone', $supplier->contact_phone) }}" placeholder="+63 9XX XXX XXXX">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"
                                  placeholder="Street, building, barangay">{{ old('address', $supplier->address) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control"
                               value="{{ old('city', $supplier->city) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control"
                               value="{{ old('country', $supplier->country ?? 'Philippines') }}">
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
                                  placeholder="Bank name, account number, branch…">{{ old('bank_details', $supplier->bank_details) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Any additional notes about this vendor">{{ old('notes', $supplier->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="col-lg-4">

        {{-- Logo --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-teal"><i class="bi bi-image"></i></span>
                Logo
            </div>
            <div class="form-card-body text-center">
                @if($supplier->logo)
                <div class="mb-3">
                    <img src="{{ asset('storage/' . $supplier->logo) }}"
                         class="rounded-3 border" style="max-height:100px;max-width:100%;object-fit:contain">
                    <small class="d-block text-muted mt-1">Current logo</small>
                </div>
                @else
                <div id="logoPreviewWrap" class="mb-3" style="display:none">
                    <img id="logoPreview" class="rounded-3 border"
                         style="max-height:100px;max-width:100%;object-fit:contain">
                </div>
                @endif
                <div class="border rounded-3 p-3 bg-light"
                     style="border-style:dashed!important;border-color:#e2e8f0!important">
                    <i class="bi bi-cloud-upload fs-2 text-secondary d-block mb-2"></i>
                    <input type="file" name="logo" class="form-control form-control-sm"
                           accept="image/*" onchange="previewLogo(event)">
                    <small class="text-muted d-block mt-1">
                        {{ $supplier->logo ? 'Upload to replace current logo' : 'PNG, JPG up to 2MB' }}
                    </small>
                </div>
            </div>
        </div>

        {{-- Rating --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-star-fill"></i></span>
                Rating
            </div>
            <div class="form-card-body">
                <label class="form-label">Rating (0–5)</label>
                <input type="number" name="rating" class="form-control"
                       value="{{ old('rating', $supplier->rating ?? 0) }}" step="0.1" min="0" max="5">
                <div class="form-text">Based on vendor performance reviews.</div>
            </div>
        </div>

        {{-- Meta --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-slate"><i class="bi bi-clock-history"></i></span>
                Record Info
            </div>
            <div class="form-card-body">
                <div style="font-size:.82rem;color:#64748b;line-height:2">
                    <div><span style="font-weight:600;color:#334155">Created:</span>
                        {{ $supplier->created_at->format('d-m-Y') }}
                    </div>
                    <div><span style="font-weight:600;color:#334155">Updated:</span>
                        {{ $supplier->updated_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Save Changes
    </button>
</div>
</form>
@endsection

@push('scripts')
<script>
function previewLogo(e) {
    const file = e.target.files[0];
    if (!file) return;
    const wrap = document.getElementById('logoPreviewWrap');
    if (wrap) {
        wrap.style.display = 'block';
        document.getElementById('logoPreview').src = URL.createObjectURL(file);
    }
}
</script>
@endpush
