@extends('layouts.app')
@section('title', 'Edit - ' . $organization->name)

@section('content')
<div class="page-header">
    <a href="{{ route('super-admin.organizations.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Organizations</a>
    <h4>Edit Organization</h4>
    <p>Update details for <strong>{{ $organization->name }}</strong>.</p>
</div>

@php
    $enabledModuleCount = $organization->modules->where('is_enabled', true)->count();
    $monthlyAmount = (float) ($organization->monthly_amount ?? $organization->modules->where('is_enabled', true)->sum('monthly_price'));
@endphp

<form action="{{ route('super-admin.organizations.update', $organization) }}" method="POST" enctype="multipart/form-data">
@csrf @method('PUT')
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
                               value="{{ old('name', $organization->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', $organization->status) === 'active' ? 'selected':'' }}>Active</option>
                            <option value="inactive" {{ old('status', $organization->status) === 'inactive' ? 'selected':'' }}>Inactive</option>
                            <option value="suspended" {{ old('status', $organization->status) === 'suspended' ? 'selected':'' }}>Suspended</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2"></div>
                    <div class="col-md-5">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $organization->email) }}" placeholder="contact@company.com">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone', $organization->phone) }}" placeholder="+91 XXXXX XXXXX">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tax Number</label>
                        <input type="text" name="tax_number" class="form-control @error('tax_number') is-invalid @enderror"
                               value="{{ old('tax_number', $organization->tax_number) }}" placeholder="GST / PAN">
                        @error('tax_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control @error('website') is-invalid @enderror"
                               value="{{ old('website', $organization->website) }}" placeholder="https://company.com">
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
                                  rows="2" placeholder="Building, street, area">{{ old('address', $organization->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                               value="{{ old('city', $organization->city) }}" placeholder="e.g. Noida">
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
                               value="{{ old('country', $organization->country) }}">
                        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        @include('super-admin.organizations._product-provisioning')

    </div>

    {{-- RIGHT --}}
    <div class="col-lg-4">

        {{-- Module Access --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-purple"><i class="bi bi-grid-1x2"></i></span>
                Module Access
            </div>
            <div class="form-card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div style="font-size:.84rem;font-weight:700;color:#1e293b">Enabled Modules</div>
                        <div class="text-muted" style="font-size:.74rem">Control which products this organization can use.</div>
                    </div>
                    <span class="badge bg-primary" style="font-size:.72rem">{{ $enabledModuleCount }}</span>
                </div>
                <div class="border rounded-3 p-2 mb-2" style="background:#f8fafc;border-color:#e2e8f0!important">
                    <div class="d-flex justify-content-between" style="font-size:.78rem">
                        <span class="text-muted">Billing Status</span>
                        <span class="badge bg-{{ $organization->billing_status_badge ?? 'primary' }}" style="font-size:.68rem">
                            {{ ucfirst($organization->billing_status ?? 'trial') }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mt-2" style="font-size:.78rem">
                        <span class="text-muted">Trial Ends</span>
                        <strong>{{ $organization->trial_ends_at?->format('d-m-Y') ?? '-' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2" style="font-size:.78rem">
                        <span class="text-muted">Monthly Amount</span>
                        <strong>&#8377;{{ number_format($monthlyAmount, 2) }}</strong>
                    </div>
                </div>
                <a href="{{ route('super-admin.organizations.show', $organization) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-sliders me-1"></i> Manage Modules
                </a>
            </div>
        </div>

        {{-- Logo --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-teal"><i class="bi bi-image"></i></span>
                Logo
            </div>
            <div class="form-card-body text-center">
                @if($organization->logo)
                <div class="mb-3">
                    <img src="{{ asset('storage/' . $organization->logo) }}"
                         class="rounded-3 border" style="max-height:80px;max-width:100%;object-fit:contain">
                    <small class="d-block text-muted mt-1">Current logo</small>
                </div>
                @else
                <div id="logoPreviewWrap" class="mb-3" style="display:none">
                    <img id="logoPreview" class="rounded-3 border"
                         style="max-height:80px;max-width:100%;object-fit:contain">
                </div>
                @endif
                <div class="border rounded-3 p-3 bg-light"
                     style="border-style:dashed!important;border-color:#e2e8f0!important">
                    <i class="bi bi-cloud-upload text-secondary d-block mb-2" style="font-size:1.35rem"></i>
                    <input type="file" name="logo" class="form-control form-control-sm"
                           accept="image/*" onchange="previewLogo(event)">
                    <small class="text-muted d-block mt-1">
                        {{ $organization->logo ? 'Upload to replace current logo' : 'PNG, JPG up to 2MB' }}
                    </small>
                </div>
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
                        {{ $organization->created_at->format('d-m-Y') }}
                    </div>
                    <div><span style="font-weight:600;color:#334155">Updated:</span>
                        {{ $organization->updated_at->diffForHumans() }}
                    </div>
                    <div><span style="font-weight:600;color:#334155">Status:</span>
                        <span class="badge bg-{{ $organization->status === 'active' ? 'success' : ($organization->status === 'suspended' ? 'danger' : 'secondary') }}">
                            {{ ucfirst($organization->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('super-admin.organizations.index') }}" class="btn-cancel">Cancel</a>
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
