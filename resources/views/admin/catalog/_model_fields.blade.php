<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label fw-600" style="font-size:.78rem">Model Name <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" required
               value="{{ old('name', $assetModel->name ?? '') }}"
               placeholder="e.g. Latitude 5530, LaserJet Pro M404n…">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-600" style="font-size:.78rem">Brand</label>
        <select name="brand_id" class="form-select">
            <option value="">— Select Brand —</option>
            @foreach($brands as $b)
            <option value="{{ $b->id }}" {{ old('brand_id', $assetModel->brand_id ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 d-flex align-items-end pb-1">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   {{ old('is_active', $assetModel->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-600" style="font-size:.82rem">Active</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label fw-600" style="font-size:.78rem">Category</label>
        <select name="category_id" class="form-select" id="modelCategorySelect" onchange="loadModelSpecFields(this)">
            <option value="">— Select Category —</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}"
                    data-fields='@json($cat->spec_template ?? [])'
                    {{ old('category_id', $assetModel->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                {{ $cat->name }}
            </option>
            @endforeach
        </select>
        <div class="form-text">Selecting a category loads its specification fields below.</div>
    </div>

    {{-- Dynamic spec fields --}}
    <div class="col-12" id="modelSpecSection" style="{{ ($assetModel->category_id ?? null) || old('category_id') ? '' : 'display:none' }}">
        <div style="display:flex;align-items:center;gap:.75rem;margin:.1rem 0 .75rem">
            <div style="flex:1;height:1px;background:#f1f5f9"></div>
            <span style="font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;white-space:nowrap">
                <i class="bi bi-cpu me-1"></i>Default Specifications
            </span>
            <div style="flex:1;height:1px;background:#f1f5f9"></div>
        </div>
        <div class="form-text mb-2">These values will auto-fill when this model is selected on an asset.</div>
        <div class="row g-2" id="modelSpecFields">
            {{-- Rendered by JS --}}
            @if(isset($assetModel) && $assetModel->category_id && !empty($assetModel->category->spec_template))
                @foreach($assetModel->category->spec_template as $field)
                @php $key = 'spec_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($field)); @endphp
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:600;color:#475569">{{ $field }}</label>
                    <input type="text" name="{{ $key }}" class="form-control form-control-sm"
                           value="{{ old($key, $assetModel->default_specs[$key] ?? '') }}"
                           placeholder="{{ $field }}…">
                </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
