<div class="row g-3">
    <div class="col-md-7">
        <label class="form-label fw-600" style="font-size:.78rem">Brand Name <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" required
               value="{{ old('name', $brand->name ?? '') }}"
               placeholder="e.g. Dell, HP, Cisco, Apple…">
    </div>
    <div class="col-md-5 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="brandActive{{ isset($edit) ? '_edit' : '' }}"
                   {{ old('is_active', $brand->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="brandActive{{ isset($edit) ? '_edit' : '' }}" style="font-size:.82rem;font-weight:600">Active</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label fw-600" style="font-size:.78rem">Website</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-globe2 text-muted"></i></span>
            <input type="url" name="website" class="form-control"
                   value="{{ old('website', $brand->website ?? '') }}"
                   placeholder="https://www.dell.com">
        </div>
    </div>
    <div class="col-12">
        <label class="form-label fw-600" style="font-size:.78rem">Brand Logo</label>
        @if(isset($brand) && $brand->logo)
        <div class="mb-2">
            <img src="{{ Storage::url($brand->logo) }}" style="height:40px;object-fit:contain;border-radius:8px;border:1px solid #e2e8f0;padding:4px">
        </div>
        @endif
        <input type="file" name="logo" class="form-control" accept="image/*">
        <div class="form-text">PNG, SVG or WebP · Max 1 MB · Recommended: transparent background</div>
    </div>
</div>
