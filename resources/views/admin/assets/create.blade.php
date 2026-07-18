@extends('layouts.app')
@section('title', 'Add Asset')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.assets.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Assets</a>
    <h4>Register New Asset</h4>
    <p>Add a new IT asset to your organisation's inventory.</p>
</div>

<form action="{{ route('admin.assets.store') }}" method="POST" enctype="multipart/form-data">
@csrf
<div class="row g-4">

    {{-- LEFT --}}
    <div class="col-lg-8">

        {{-- Basic Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-info-circle"></i></span>
                Basic Information
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Asset Display Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required placeholder="e.g. IT Laptop 01, Reception Printer, CFO Laptop">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Use a simple internal name for search and lists. Brand, model and category are selected separately.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Brand</label>
                        <select name="asset_brand_id" id="assetBrandSelect" class="form-select" onchange="filterModels()">
                            <option value="">— Select Brand —</option>
                            @foreach($brands as $b)
                            <option value="{{ $b->id }}" data-name="{{ $b->name }}" {{ old('asset_brand_id') == $b->id ? 'selected':'' }}>{{ $b->name }}</option>
                            @endforeach
                            <option value="__custom__" {{ old('asset_brand_id') === '__custom__' ? 'selected':'' }}>Other (type below)</option>
                        </select>
                        <input type="text" name="brand" id="brandCustom" class="form-control mt-1"
                               value="{{ old('brand') }}" placeholder="Type brand name…"
                               style="display:{{ old('asset_brand_id') === '__custom__' ? 'block':'none' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Model</label>
                        <select name="asset_model_id" id="assetModelSelect" class="form-select" onchange="onModelSelect(this)">
                            <option value="">— Select Model —</option>
                            @foreach($models as $m)
                            <option value="{{ $m->id }}"
                                    data-brand="{{ $m->brand_id }}"
                                    data-specs='@json($m->default_specs ?? [])'
                                    {{ old('asset_model_id') == $m->id ? 'selected':'' }}>
                                {{ $m->name }}
                            </option>
                            @endforeach
                            <option value="__custom__" {{ old('asset_model_id') === '__custom__' ? 'selected':'' }}>Other (type below)</option>
                        </select>
                        <input type="text" name="model" id="modelCustom" class="form-control mt-1"
                               value="{{ old('model') }}" placeholder="Type model name…"
                               style="display:{{ old('asset_model_id') === '__custom__' ? 'block':'none' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Asset Tag</label>
                        <input type="text" name="asset_tag" class="form-control"
                               value="{{ old('asset_tag') }}" placeholder="Auto-generated if blank">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" class="form-control"
                               value="{{ old('serial_number') }}" placeholder="From device label">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="assetCategorySelect" class="form-select" onchange="onCategoryChange(this)">
                            <option value="">— Select —</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                    data-fields='@json($cat->spec_template ?? [])'
                                    {{ old('category_id') == $cat->id ? 'selected':'' }}>
                                {{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Dynamic specs (driven by category) --}}
                    <div class="col-12" id="assetSpecSection" style="{{ old('category_id') ? '' : 'display:none' }}">
                        <div style="display:flex;align-items:center;gap:.75rem;margin:.25rem 0 .75rem">
                            <div style="flex:1;height:1px;background:#f1f5f9"></div>
                            <span style="font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;white-space:nowrap">
                                <i class="bi bi-cpu me-1"></i>Technical Specifications
                            </span>
                            <div style="flex:1;height:1px;background:#f1f5f9"></div>
                        </div>
                        <div id="assetSpecFields" class="row g-2">
                            {{-- Rendered by JS on category change --}}
                        </div>
                    </div>
                    <div class="col-12">
                        <label style="font-size:.72rem;font-weight:600;color:#475569"><i class="bi bi-card-text me-1"></i>Additional Notes / Specs</label>
                        <textarea name="specifications" class="form-control form-control-sm" rows="2"
                                  placeholder="Any extra details not covered by the fields above…">{{ old('specifications') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description / Notes</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Purchase & Warranty --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-receipt"></i></span>
                Purchase & Warranty
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Acquisition Source <span class="req">*</span></label>
                        <select name="acquisition_source" class="form-select @error('acquisition_source') is-invalid @enderror" required>
                            <option value="">Select source</option>
                            @foreach([
                                'opening_balance' => 'Opening Balance / Legacy Asset',
                                'donation' => 'Donation',
                                'transfer' => 'Inter-company Transfer',
                                'lease' => 'Lease',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('acquisition_source') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('acquisition_source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Assets purchased through a PO must be created from Purchase Order > Receive Items.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($suppliers as $v)
                            <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected':'' }}>
                                {{ $v->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control"
                               value="{{ old('purchase_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Price (&#8377;)</label>
                        <input type="number" name="purchase_price" class="form-control"
                               value="{{ old('purchase_price') }}" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warranty Expiry</label>
                        <input type="date" name="warranty_expiry_date" class="form-control"
                               value="{{ old('warranty_expiry_date') }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Warranty Terms</label>
                        <input type="text" name="warranty_terms" class="form-control"
                               value="{{ old('warranty_terms') }}" placeholder="e.g. 3-year on-site warranty">
                    </div>
                </div>
            </div>
        </div>

        {{-- Depreciation --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-red"><i class="bi bi-graph-down-arrow"></i></span>
                Depreciation <span style="font-size:.7rem;font-weight:400;opacity:.7;margin-left:.4rem">Optional</span>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Method</label>
                        <select name="dep_method" class="form-select">
                            <option value="">— No Depreciation —</option>
                            <option value="straight_line"     {{ old('dep_method') == 'straight_line'     ? 'selected':'' }}>Straight Line</option>
                            <option value="declining_balance" {{ old('dep_method') == 'declining_balance' ? 'selected':'' }}>Declining Balance</option>
                            <option value="sum_of_years"      {{ old('dep_method') == 'sum_of_years'      ? 'selected':'' }}>Sum of Years</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Useful Life (Years)</label>
                        <input type="number" name="useful_life_years" class="form-control"
                               value="{{ old('useful_life_years', 3) }}" min="1" max="50">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Salvage Value (&#8377;)</label>
                        <input type="number" name="salvage_value" class="form-control"
                               value="{{ old('salvage_value', 0) }}" step="0.01" min="0">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT --}}
    <div class="col-lg-4">

        {{-- Status & Location --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-toggles"></i></span>
                Status & Location
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach(['available','assigned','maintenance','repair','retired','disposed','lost'] as $s)
                            <option value="{{ $s }}" {{ old('status','available') == $s ? 'selected':'' }}>
                                {{ ucfirst($s) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Condition <span class="req">*</span></label>
                        <select name="condition" class="form-select" required>
                            @foreach(['excellent','good','fair','poor'] as $c)
                            <option value="{{ $c }}" {{ old('condition','good') == $c ? 'selected':'' }}>
                                {{ ucfirst($c) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Work Location</label>
                        <select name="location_id" class="form-select">
                            <option value="">— Select Work Location —</option>
                            @foreach($facilities as $facility)
                                @if($facility->activeLocations->isNotEmpty())
                                <optgroup label="{{ $facility->name }}{{ $facility->state ? ' ('.$facility->state.')' : '' }}">
                                    @foreach($facility->activeLocations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected':'' }}>
                                        {{ $loc->name }}{{ $loc->floor ? ' — '.$loc->floor : '' }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Any additional notes…">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Image --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-teal"><i class="bi bi-image"></i></span>
                Asset Image
            </div>
            <div class="form-card-body text-center">
                <div id="imagePreview" class="mb-3" style="display:none">
                    <img id="previewImg" class="rounded-3 border" style="max-height:120px;max-width:100%;object-fit:contain">
                </div>
                <div class="border rounded-3 p-3 bg-light" style="border-style:dashed!important;border-color:#e2e8f0!important">
                    <i class="bi bi-cloud-upload fs-2 text-secondary d-block mb-2"></i>
                    <input type="file" name="image" class="form-control form-control-sm"
                           accept="image/*" onchange="previewImage(event)">
                    <small class="text-muted d-block mt-1">JPG, PNG, max 2MB</small>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('admin.assets.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Save Asset
    </button>
</div>
</form>
@endsection

@push('scripts')
<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('imagePreview').style.display = 'block';
        document.getElementById('previewImg').src = URL.createObjectURL(file);
    }
}

// Brand — show/hide custom input + filter model list
function filterModels() {
    const brandSel   = document.getElementById('assetBrandSelect');
    const customBrand = document.getElementById('brandCustom');
    customBrand.style.display = brandSel.value === '__custom__' ? 'block' : 'none';
    const selected  = brandSel.value;
    const modelSel  = document.getElementById('assetModelSelect');
    Array.from(modelSel.options).forEach(opt => {
        if (!opt.value || opt.value === '__custom__') return;
        opt.hidden = !!(selected && opt.dataset.brand && opt.dataset.brand !== selected);
    });
    const cur = modelSel.options[modelSel.selectedIndex];
    if (cur && cur.hidden) modelSel.value = '';
}

// Category — render dynamic spec fields
function onCategoryChange(sel) {
    const section   = document.getElementById('assetSpecSection');
    const container = document.getElementById('assetSpecFields');
    if (!sel.value) { section.style.display = 'none'; container.innerHTML = ''; return; }
    const opt    = sel.options[sel.selectedIndex];
    let fields   = [];
    try { fields = JSON.parse(opt.dataset.fields || '[]'); } catch(e) {}
    if (!fields.length) { section.style.display = 'none'; container.innerHTML = ''; return; }

    section.style.display = '';
    container.innerHTML = '';
    fields.forEach(label => {
        const key  = 'spec_' + label.toLowerCase().replace(/[^a-z0-9]+/g,'_');
        container.insertAdjacentHTML('beforeend',
            `<div class="col-md-6">
                <label style="font-size:.72rem;font-weight:600;color:#475569">${label}</label>
                <input type="text" name="${key}" id="${key}" class="form-control form-control-sm" placeholder="${label}…">
             </div>`);
    });
}

// Model — auto-fill spec fields from model's default_specs
function onModelSelect(sel) {
    document.getElementById('modelCustom').style.display = sel.value === '__custom__' ? 'block' : 'none';
    if (!sel.value || sel.value === '__custom__') return;
    const opt = sel.options[sel.selectedIndex];
    let specs = {};
    try { specs = JSON.parse(opt.dataset.specs || '{}'); } catch(e) {}
    Object.entries(specs).forEach(([k,v]) => {
        const el = document.getElementById(k);
        if (el) el.value = v;
    });
}

window.addEventListener('DOMContentLoaded', function() {
    filterModels();
    // Re-render spec fields for old() category value
    const catSel = document.getElementById('assetCategorySelect');
    if (catSel && catSel.value) onCategoryChange(catSel);
});
</script>
@endpush
