@extends('layouts.app')
@section('title', 'Edit Software')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.software.show', $software) }}" class="back-link">
        <i class="bi bi-arrow-left"></i> {{ $software->name }}
    </a>
    <h4>Edit Software</h4>
    <p>Update details for {{ $software->name }}</p>
</div>

<form method="POST" action="{{ route('admin.software.update', $software) }}" enctype="multipart/form-data">
@csrf @method('PUT')
@if($errors->any())
    <div class="alert alert-danger">
        <strong>Unable to save changes.</strong>
        Please review the highlighted fields and try again.
    </div>
@endif
<div class="row g-4">
    <div class="col-lg-8">

        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-blue"><i class="bi bi-display-fill"></i></div>
                Software Information
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Software Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $software->name) }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Version</label>
                        <input type="text" name="version" value="{{ old('version', $software->version) }}"
                               class="form-control @error('version') is-invalid @enderror">
                        @error('version')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Edition / Plan</label>
                        <input type="text" name="edition" value="{{ old('edition', $software->edition) }}"
                               class="form-control @error('edition') is-invalid @enderror">
                        @error('edition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Publisher</label>
                        <input type="text" name="vendor" value="{{ old('vendor', $software->vendor) }}"
                               class="form-control @error('vendor') is-invalid @enderror">
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
                                <option value="{{ $val }}" @selected(old('category', $software->category) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Software Type <span class="req">*</span></label>
                        <select name="software_type" class="form-select @error('software_type') is-invalid @enderror" required>
                            @foreach(['commercial' => 'Commercial', 'saas' => 'SaaS', 'open_source' => 'Open Source', 'freeware' => 'Freeware', 'os' => 'Operating System'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('software_type', $software->software_type) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('software_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">License Required <span class="req">*</span></label>
                        <select name="license_required" class="form-select @error('license_required') is-invalid @enderror" required>
                            <option value="1" @selected((string) old('license_required', (int) $software->license_required) === '1')>Yes</option>
                            <option value="0" @selected((string) old('license_required', (int) $software->license_required) === '0')>No</option>
                        </select>
                        @error('license_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Criticality <span class="req">*</span></label>
                        <select name="criticality" class="form-select @error('criticality') is-invalid @enderror" required>
                            @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('criticality', $software->criticality) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('criticality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License Metric <span class="req">*</span></label>
                        <select name="license_metric" class="form-select @error('license_metric') is-invalid @enderror" required>
                            @foreach(['per_user' => 'Per User', 'per_device' => 'Per Device', 'concurrent' => 'Concurrent', 'site' => 'Site', 'enterprise' => 'Enterprise', 'usage_based' => 'Usage Based'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('license_metric', $software->license_metric) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('license_metric')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 w-100" style="cursor:pointer;background:#f8fafc">
                            <input type="checkbox" name="trusted_publisher" value="1" class="form-check-input m-0" @checked(old('trusted_publisher', $software->trusted_publisher))>
                            <span class="fw-bold">Trusted publisher</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Publisher Website</label>
                        <input type="url" name="publisher_website"
                               value="{{ old('publisher_website', $software->publisher_website) }}"
                               class="form-control @error('publisher_website') is-invalid @enderror">
                        @error('publisher_website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $software->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">WinGet Package ID</label>
                        <input type="text" id="wingetPackageId" name="winget_package_id" value="{{ old('winget_package_id', $software->winget_package_id) }}"
                               class="form-control @error('winget_package_id') is-invalid @enderror"
                               placeholder="e.g. Microsoft.VisualStudioCode">
                        <div class="form-text">
                            Exact ID used by the agent for install/uninstall. Examples: Google.Chrome, Microsoft.VisualStudioCode, Mozilla.Firefox, 7zip.7zip.
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            <code class="small">winget search "{{ old('name', $software->name) ?: 'software name' }}"</code>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="copyWingetSearch">
                                <i class="bi bi-clipboard me-1"></i>Copy command
                            </button>
                        </div>
                        @error('winget_package_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5 d-flex align-items-end">
                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 w-100" style="cursor:pointer;background:#f8fafc">
                            <input type="checkbox" id="endpointManagementEnabled" name="endpoint_management_enabled" value="1" class="form-check-input m-0" @checked(old('endpoint_management_enabled', $software->endpoint_management_enabled))>
                            <span class="fw-bold">Allow endpoint deployment</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <div id="endpointDeploymentWarning" class="alert alert-warning small mb-0 d-none">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Enter a WinGet Package ID before enabling endpoint deployment.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="col-lg-4">

        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-purple"><i class="bi bi-image-fill"></i></div>
                Software Icon
            </div>
            <div class="form-card-body text-center">
                @if($software->icon)
                    <img id="iconPreview" src="{{ Storage::url($software->icon) }}" alt="icon"
                         style="width:80px;height:80px;border-radius:14px;object-fit:cover;border:2px solid #e2e8f0;margin-bottom:1rem">
                @else
                    <div id="iconPreview" class="mb-3"
                         style="width:80px;height:80px;border-radius:14px;background:linear-gradient(135deg,#3b82f6,#6366f1);
                                display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;margin:0 auto">
                        <i class="bi bi-display-fill"></i>
                    </div>
                @endif
                <label for="iconInput" class="btn btn-outline-primary btn-sm w-100" style="cursor:pointer">
                    <i class="bi bi-upload me-1"></i>{{ $software->icon ? 'Replace Icon' : 'Upload Icon' }}
                </label>
                <input type="file" id="iconInput" name="icon" accept="image/*" class="d-none"
                       onchange="previewIconEl(this)">
                <div class="form-text mt-2">PNG, JPG up to 2 MB</div>
                @error('icon')<div class="text-danger mt-1" style="font-size:.76rem">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Danger zone --}}
        <div class="form-card border-danger" style="border-color:#fecaca!important">
            <div class="form-card-header" style="background:#fef2f2;border-bottom-color:#fecaca">
                <div class="icon-wrap icon-red"><i class="bi bi-trash-fill"></i></div>
                <span style="color:#dc2626">Danger Zone</span>
            </div>
            <div class="form-card-body">
                <p class="text-muted small mb-3">Permanently delete this software and all associated licenses and assignments.</p>
                <button type="submit" form="deleteSoftwareForm" class="btn btn-danger btn-sm w-100"
                        onclick="return confirm('Delete {{ addslashes($software->name) }}? This cannot be undone.')">
                    <i class="bi bi-trash me-1"></i>Delete Software
                </button>
            </div>
        </div>

    </div>
</div>

<div class="form-actions">
    <a href="{{ route('admin.software.show', $software) }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Save Changes
    </button>
</div>
</form>

<form id="deleteSoftwareForm" method="POST" action="{{ route('admin.software.destroy', $software) }}" class="d-none">
    @csrf @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function previewIconEl(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var el = document.getElementById('iconPreview');
            if (el.tagName === 'IMG') {
                el.src = e.target.result;
            } else {
                var img = document.createElement('img');
                img.id = 'iconPreview';
                img.src = e.target.result;
                img.style.cssText = 'width:80px;height:80px;border-radius:14px;object-fit:cover;border:2px solid #e2e8f0;margin-bottom:1rem';
                el.replaceWith(img);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var endpointCheckbox = document.getElementById('endpointManagementEnabled');
    var packageInput = document.getElementById('wingetPackageId');
    var warning = document.getElementById('endpointDeploymentWarning');
    var copyButton = document.getElementById('copyWingetSearch');
    var softwareNameInput = document.querySelector('input[name="name"]');

    function refreshEndpointWarning() {
        if (!endpointCheckbox || !packageInput || !warning) return;
        warning.classList.toggle('d-none', !(endpointCheckbox.checked && packageInput.value.trim() === ''));
    }

    endpointCheckbox?.addEventListener('change', refreshEndpointWarning);
    packageInput?.addEventListener('input', refreshEndpointWarning);
    refreshEndpointWarning();

    copyButton?.addEventListener('click', function () {
        var softwareName = softwareNameInput?.value.trim() || 'software name';
        navigator.clipboard?.writeText('winget search "' + softwareName + '"');
        copyButton.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied';
        setTimeout(function () {
            copyButton.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy command';
        }, 1600);
    });
});
</script>
@endpush
