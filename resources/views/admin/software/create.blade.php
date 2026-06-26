@extends('layouts.app')
@section('title', 'Add Software')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.software.index') }}" class="back-link">
        <i class="bi bi-arrow-left"></i> Software Catalog
    </a>
    <h4>Add Software Title</h4>
    <p>Add a new application to your software catalog.</p>
</div>

<form method="POST" action="{{ route('admin.software.store') }}" enctype="multipart/form-data">
@csrf
<div class="row g-4">
    <div class="col-lg-8">

        {{-- Basic Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-blue"><i class="bi bi-display-fill"></i></div>
                Software Information
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Software Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Microsoft Office 365" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Version</label>
                        <input type="text" name="version" value="{{ old('version') }}"
                               class="form-control @error('version') is-invalid @enderror"
                               placeholder="e.g. 2024">
                        @error('version')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Edition / Plan</label>
                        <input type="text" name="edition" value="{{ old('edition') }}"
                               class="form-control @error('edition') is-invalid @enderror"
                               placeholder="e.g. Pro, Enterprise">
                        @error('edition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Publisher</label>
                        <input type="text" name="vendor" value="{{ old('vendor') }}"
                               class="form-control @error('vendor') is-invalid @enderror"
                               placeholder="e.g. Microsoft Corporation">
                        @error('vendor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category <span class="req">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">Select category…</option>
                            @foreach([
                                'productivity'    => 'Productivity',
                                'security'        => 'Security',
                                'design'          => 'Design',
                                'development'     => 'Development',
                                'communication'   => 'Communication',
                                'database'        => 'Database',
                                'erp'             => 'ERP / Business',
                                'operating_system'=> 'Operating System',
                                'other'           => 'Other',
                            ] as $val => $label)
                                <option value="{{ $val }}" @selected(old('category') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Software Type <span class="req">*</span></label>
                        <select name="software_type" class="form-select @error('software_type') is-invalid @enderror" required>
                            @foreach(['commercial' => 'Commercial', 'saas' => 'SaaS', 'open_source' => 'Open Source', 'freeware' => 'Freeware', 'os' => 'Operating System'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('software_type', 'commercial') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('software_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">License Required <span class="req">*</span></label>
                        <select name="license_required" class="form-select @error('license_required') is-invalid @enderror" required>
                            <option value="1" @selected(old('license_required', '1') === '1')>Yes</option>
                            <option value="0" @selected(old('license_required') === '0')>No</option>
                        </select>
                        @error('license_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Criticality <span class="req">*</span></label>
                        <select name="criticality" class="form-select @error('criticality') is-invalid @enderror" required>
                            @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('criticality', 'medium') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('criticality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License Metric <span class="req">*</span></label>
                        <select name="license_metric" class="form-select @error('license_metric') is-invalid @enderror" required>
                            @foreach(['per_user' => 'Per User', 'per_device' => 'Per Device', 'concurrent' => 'Concurrent', 'site' => 'Site', 'enterprise' => 'Enterprise', 'usage_based' => 'Usage Based'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('license_metric', 'per_user') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('license_metric')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 w-100" style="cursor:pointer;background:#f8fafc">
                            <input type="checkbox" name="trusted_publisher" value="1" class="form-check-input m-0" @checked(old('trusted_publisher'))>
                            <span class="fw-bold">Trusted publisher</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Publisher Website</label>
                        <input type="url" name="publisher_website" value="{{ old('publisher_website') }}"
                               class="form-control @error('publisher_website') is-invalid @enderror"
                               placeholder="https://…">
                        @error('publisher_website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Brief description of what this software does…">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="col-lg-4">

        {{-- Icon Upload --}}
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-purple"><i class="bi bi-image-fill"></i></div>
                Software Icon
            </div>
            <div class="form-card-body text-center">
                <div id="iconPreviewWrap" class="mb-3" style="display:none">
                    <img id="iconPreview" src="#" alt="Preview"
                         style="width:80px;height:80px;border-radius:14px;object-fit:cover;border:2px solid #e2e8f0">
                </div>
                <div id="iconPlaceholder" class="mb-3">
                    <div style="width:80px;height:80px;border-radius:14px;background:linear-gradient(135deg,#3b82f6,#6366f1);
                                display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;margin:0 auto">
                        <i class="bi bi-display-fill"></i>
                    </div>
                </div>
                <label for="iconInput" class="btn btn-outline-primary btn-sm w-100" style="cursor:pointer">
                    <i class="bi bi-upload me-1"></i>Upload Icon
                </label>
                <input type="file" id="iconInput" name="icon" accept="image/*" class="d-none"
                       onchange="previewIcon(this)">
                <div class="form-text mt-2">PNG, JPG up to 2 MB</div>
                @error('icon')<div class="text-danger mt-1" style="font-size:.76rem">{{ $message }}</div>@enderror
            </div>
        </div>

    </div>
</div>

<div class="form-actions" style="background:#f8fafc;border:1px solid #e9ecef;border-radius:0 0 16px 16px;border-top:none">
    <a href="{{ route('admin.software.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Add to Catalog
    </button>
</div>
</form>
@endsection

@push('scripts')
<script>
function previewIcon(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('iconPreview').src = e.target.result;
            document.getElementById('iconPreviewWrap').style.display = 'block';
            document.getElementById('iconPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
