@extends('layouts.app')
@section('title', 'Bulk Import Assets')

@push('styles')
<style>
.step-bar{display:flex;align-items:center;gap:0;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.4rem;margin-bottom:1.5rem;width:fit-content;}
.step-item{display:flex;align-items:center;gap:.55rem;padding:.55rem 1.2rem;border-radius:10px;font-size:.82rem;font-weight:600;color:#94a3b8;}
.step-item.active{background:#3b82f6;color:#fff;box-shadow:0 4px 12px rgba(59,130,246,.3);}
.step-item .step-num{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;background:rgba(255,255,255,.25);flex-shrink:0;}
.step-item:not(.active) .step-num{background:#f1f5f9;color:#94a3b8;}
.step-arrow{color:#e2e8f0;font-size:.8rem;padding:0 .2rem;}

.cat-pick-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.55rem;}
.cat-pick-card{border:2px solid #e2e8f0;border-radius:13px;padding:.8rem .7rem;text-align:center;cursor:pointer;transition:all .18s;background:#fff;position:relative;user-select:none;}
.cat-pick-card:hover{border-color:#93c5fd;background:#f8fbff;transform:translateY(-2px);}
.cat-pick-card.selected{border-color:#3b82f6;background:#eff6ff;box-shadow:0 0 0 3px rgba(59,130,246,.15);}
.cat-pick-card.selected::after{content:'✓';position:absolute;top:.35rem;right:.45rem;width:17px;height:17px;border-radius:50%;background:#3b82f6;color:#fff;font-size:.6rem;font-weight:900;display:flex;align-items:center;justify-content:center;line-height:17px;}
.cat-pick-icon{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;margin:0 auto .45rem;}
.cat-pick-name{font-size:.76rem;font-weight:700;color:#1e293b;line-height:1.25;}
.cat-pick-count{font-size:.62rem;color:#94a3b8;margin-top:.12rem;}

/* Column preview table */
.col-preview-table{width:100%;border-collapse:collapse;font-size:.75rem;}
.col-preview-table th{background:#f8fafc;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;padding:.45rem .65rem;border-bottom:1px solid #f1f5f9;white-space:nowrap;}
.col-preview-table td{padding:.42rem .65rem;border-bottom:1px solid #f8fafc;color:#475569;vertical-align:top;}
.col-preview-table tr:last-child td{border-bottom:none;}
.col-preview-table tr.spec-row td{background:#f5f3ff;}
.col-preview-table tr.spec-row td:first-child{color:#6d28d9;font-weight:700;}
.col-tag{display:inline-block;border-radius:5px;padding:.1rem .45rem;font-size:.65rem;font-weight:700;}
.col-tag-req{background:#fef2f2;color:#dc2626;}
.col-tag-opt{background:#f0fdf4;color:#16a34a;}
.col-tag-spec{background:#eef2ff;color:#4338ca;}

/* Upload zone */
.upload-zone{border:2px dashed #e2e8f0;border-radius:14px;background:#f8fafc;padding:1.8rem;text-align:center;cursor:pointer;transition:all .2s;position:relative;}
.upload-zone:hover,.upload-zone.drag-over{border-color:#3b82f6;background:#eff6ff;}
.upload-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}

/* Section card */
.import-section{background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;margin-bottom:1rem;}
.import-section-header{padding:.95rem 1.35rem;border-bottom:1px solid #f8fafc;display:flex;align-items:center;gap:.6rem;}
.import-section-num{width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:800;flex-shrink:0;}
.import-section-title{font-size:.875rem;font-weight:700;color:#0f172a;}
.import-section-sub{font-size:.72rem;color:#94a3b8;margin-left:.2rem;}
.import-section-body{padding:1.2rem 1.35rem;}
</style>
@endpush

@section('content')

<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-cloud-upload-fill me-2 text-primary"></i>Bulk Import Assets</h4>
        <p>Select a category, download the matching template, fill it in, then upload — specs are imported automatically.</p>
    </div>
    <a href="{{ route('admin.assets.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Assets
    </a>
</div>

{{-- Step indicator --}}
<div class="step-bar">
    <div class="step-item active"><div class="step-num">1</div> Choose Category</div>
    <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>
    <div class="step-item"><div class="step-num">2</div> Download Template</div>
    <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>
    <div class="step-item"><div class="step-num">3</div> Fill & Upload</div>
    <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>
    <div class="step-item"><div class="step-num">4</div> Preview & Import</div>
</div>

@if($errors->any())
<div class="alert alert-danger rounded-3 mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
</div>
@endif

{{-- Pass server-side preselected category to JS --}}
@php
    $preJson = $preselected ? ['id' => $preselected->id, 'name' => $preselected->name, 'fields' => $preselected->spec_template ?? []] : null;
    $baseColumns = ['name','asset_tag','serial_number','brand','model','status','condition','purchase_date','purchase_price','warranty_expiry_date','warranty_terms','supplier','location','description','notes'];
    $baseSamples = ['Dell Latitude 5530','ASSET-001','SN-123456','Dell','Latitude 5530','available','good','15-01-2024','75000','15-01-2027','3-year onsite','Dell Technologies','Server Room A','Finance dept laptop',''];
    $baseReqs    = ['req','opt','opt','opt','opt','opt','opt','opt','opt','opt','opt','opt','opt','opt','opt'];
    $baseDescs   = ['Display name','Auto-generated if blank','Device serial no.','Manufacturer','Model name','available/assigned/maintenance/repair/retired/disposed/lost','excellent/good/fair/poor','DD-MM-YYYY','Numbers only','DD-MM-YYYY','Coverage description','Must match supplier name','Must match location name','Short description','Internal notes'];
@endphp

<div class="row g-4">
<div class="col-lg-7">

{{-- STEP 1: Category --}}
<div class="import-section">
    <div class="import-section-header">
        <div class="import-section-num" style="background:#eff6ff;color:#3b82f6">1</div>
        <div>
            <div class="import-section-title">Select Asset Category</div>
            <div class="import-section-sub">— determines which specification columns appear in the template</div>
        </div>
    </div>
    <div class="import-section-body">
        @if($categories->isEmpty())
        <div style="text-align:center;padding:1.5rem;color:#94a3b8">
            <i class="bi bi-tag" style="font-size:1.5rem;display:block;margin-bottom:.4rem;opacity:.3"></i>
            <div style="font-size:.82rem;font-weight:600">No categories yet</div>
            <a href="{{ route('admin.catalog.index', ['tab'=>'categories']) }}" style="font-size:.75rem;color:#3b82f6">Create categories in Asset Catalog →</a>
        </div>
        @else
        <div class="cat-pick-grid" id="catGrid">
            @foreach($categories as $cat)
            @php
                $colors = [['#eff6ff','#3b82f6'],['#f0fdf4','#10b981'],['#fffbeb','#f59e0b'],['#fdf4ff','#a855f7'],['#fff1f2','#ef4444'],['#f0fdfa','#14b8a6'],['#fef9c3','#ca8a04'],['#fce7f3','#db2777'],['#f5f3ff','#7c3aed']];
                [$cbg,$cfg] = $colors[crc32($cat->name) % count($colors)];
            @endphp
            <div class="cat-pick-card {{ $preselected?->id == $cat->id ? 'selected' : '' }}"
                 data-id="{{ $cat->id }}"
                 data-name="{{ $cat->name }}"
                 data-fields='@json($cat->spec_template ?? [])'
                 onclick="selectCategory(this)">
                <div class="cat-pick-icon" style="background:{{ $cbg }}">
                    <i class="bi {{ $cat->icon ?? 'bi-box' }}" style="color:{{ $cfg }}"></i>
                </div>
                <div class="cat-pick-name">{{ $cat->name }}</div>
                <div class="cat-pick-count">{{ $cat->assets_count }} asset{{ $cat->assets_count != 1 ? 's':'' }}</div>
            </div>
            @endforeach
        </div>

        {{-- Selected category pill --}}
        <div id="catInfo" style="display:{{ $preselected ? 'block' : 'none' }};margin-top:.85rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:11px;padding:.75rem 1rem">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <div style="font-size:.8rem;font-weight:700;color:#1d4ed8">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Category: <span id="catInfoName">{{ $preselected?->name }}</span>
                </div>
                <button type="button" onclick="clearCategory()" style="background:none;border:none;color:#93c5fd;font-size:.72rem;cursor:pointer;font-weight:600">✕ Clear</button>
            </div>
            <div id="catSpecInfo" style="margin-top:.4rem;font-size:.72rem;color:#2563eb">
                @if($preselected && !empty($preselected->spec_template))
                    Spec columns: <span id="catSpecSummary">{{ implode(', ', $preselected->spec_template) }}</span>
                @elseif($preselected)
                    <span style="color:#60a5fa">No spec fields defined for this category.
                    <a href="{{ route('admin.catalog.index', ['tab'=>'categories']) }}" target="_blank" style="color:#2563eb;font-weight:600">Add in Catalog →</a></span>
                @else
                    Spec columns: <span id="catSpecSummary"></span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- STEP 2: Download Template --}}
<div class="import-section">
    <div class="import-section-header">
        <div class="import-section-num" style="background:#f0fdf4;color:#16a34a">2</div>
        <div>
            <div class="import-section-title">Download Template</div>
            <div class="import-section-sub">— category-specific CSV with the right columns</div>
        </div>
    </div>
    <div class="import-section-body">

        {{-- Live column preview table --}}
        <div style="margin-bottom:1.1rem;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden">
            <div style="padding:.55rem .85rem;background:#f8fafc;border-bottom:1px solid #f1f5f9;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px">
                <i class="bi bi-table me-1"></i>Template Preview — columns in the CSV
            </div>
            <div style="max-height:260px;overflow-y:auto">
                <table class="col-preview-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Column Header</th><th>Required</th><th>Notes / Accepted Values</th>
                        </tr>
                    </thead>
                    <tbody id="colPreviewBody">
                        {{-- Base columns (always present) --}}
                        @foreach($baseColumns as $i => $col)
                        <tr>
                            <td style="color:#cbd5e1;font-size:.68rem">{{ $i+1 }}</td>
                            <td><code style="background:#f1f5f9;color:#334155;border-radius:4px;padding:.1rem .4rem;font-size:.72rem">{{ $col }}</code></td>
                            <td><span class="col-tag col-tag-{{ $baseReqs[$i] }}">{{ strtoupper($baseReqs[$i]) }}</span></td>
                            <td style="font-size:.72rem;color:#64748b">{{ $baseDescs[$i] }}</td>
                        </tr>
                        @endforeach
                        {{-- Spec columns (dynamic) --}}
                        @if($preselected && !empty($preselected->spec_template))
                            @foreach($preselected->spec_template as $j => $field)
                            <tr class="spec-row">
                                <td style="color:#a78bfa;font-size:.68rem">{{ count($baseColumns)+$j+1 }}</td>
                                <td><code style="background:#eef2ff;color:#4338ca;border-radius:4px;padding:.1rem .4rem;font-size:.72rem">{{ $field }}</code></td>
                                <td><span class="col-tag col-tag-spec">SPEC</span></td>
                                <td style="font-size:.72rem;color:#6d28d9">{{ $preselected->name }} specification field</td>
                            </tr>
                            @endforeach
                        @else
                            <tr id="noSpecRow">
                                <td colspan="4" style="text-align:center;padding:.85rem;color:#94a3b8;font-size:.75rem;font-style:italic">
                                    <i class="bi bi-arrow-up me-1"></i>Select a category above to add specification columns
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <a id="templateBtn"
               href="{{ $preselected ? route('admin.bulk-import.template', ['category_id' => $preselected->id]) : '#' }}"
               class="btn btn-success {{ $preselected ? '' : 'disabled' }}"
               style="border-radius:10px;font-weight:700;font-size:.85rem;padding:.6rem 1.2rem">
                <i class="bi bi-download me-2"></i>
                <span id="templateBtnText">
                    {{ $preselected ? 'Download '.$preselected->name.' Template' : 'Select Category First' }}
                </span>
            </a>
            <div style="font-size:.75rem;color:#94a3b8;line-height:1.4">
                Opens Excel-compatible CSV.<br>Fill it in and upload below.
            </div>
        </div>
    </div>
</div>

{{-- STEP 3: Upload --}}
<div class="import-section">
    <div class="import-section-header">
        <div class="import-section-num" style="background:#fffbeb;color:#d97706">3</div>
        <div>
            <div class="import-section-title">Upload Filled CSV</div>
            <div class="import-section-sub">— spec columns are detected automatically</div>
        </div>
    </div>
    <div class="import-section-body">
        <form action="{{ route('admin.bulk-import.preview') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <input type="hidden" name="category_id" id="categoryIdInput" value="{{ $preselected?->id }}">

            {{-- Category badge --}}
            <div id="uploadCatBadge" style="display:{{ $preselected ? 'flex' : 'none' }};align-items:center;gap:.6rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:.65rem .9rem;margin-bottom:.85rem">
                <i class="bi bi-tag-fill" style="color:#16a34a;flex-shrink:0"></i>
                <div style="font-size:.78rem;color:#15803d;font-weight:600">
                    Importing as: <strong id="uploadCatName">{{ $preselected?->name }}</strong>
                    @if($preselected && !empty($preselected->spec_template))
                        — spec columns (<span id="uploadSpecCount">{{ count($preselected->spec_template) }}</span>) will be parsed
                    @endif
                </div>
            </div>
            <div id="uploadNoCatNote" style="display:{{ $preselected ? 'none' : 'flex' }};align-items:center;gap:.6rem;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:.65rem .9rem;margin-bottom:.85rem">
                <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;flex-shrink:0"></i>
                <div style="font-size:.78rem;color:#92400e">Select a category first. The downloaded template and upload validation use that category's optional specification columns.</div>
            </div>

            <div class="upload-zone mb-3" id="uploadZone">
                <input type="file" name="file" id="fileInput" accept=".csv,.txt" onchange="onFileSelect(this)">
                <div id="uploadPlaceholder">
                    <i class="bi bi-cloud-upload" style="font-size:2rem;color:#94a3b8;display:block;margin-bottom:.6rem"></i>
                    <div style="font-weight:700;color:#334155;margin-bottom:.25rem">Drag & drop your filled CSV here</div>
                    <div style="font-size:.78rem;color:#94a3b8">or click to browse · CSV only · max 5 MB</div>
                </div>
                <div id="fileSelected" style="display:none">
                    <i class="bi bi-file-earmark-check-fill" style="font-size:2rem;color:#10b981;display:block;margin-bottom:.35rem"></i>
                    <div id="fileName" style="font-weight:700;color:#1e293b;margin-bottom:.15rem"></div>
                    <div id="fileSize" style="font-size:.75rem;color:#64748b"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="previewBtn"
                    style="border-radius:10px;padding:.7rem;font-weight:700" disabled>
                <i class="bi bi-search me-2"></i>Preview & Validate
            </button>
        </form>
    </div>
</div>

</div>

{{-- Right: Tips --}}
<div class="col-lg-5">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;position:sticky;top:80px">
        <div style="padding:1rem 1.35rem;border-bottom:1px solid #f8fafc;font-size:.85rem;font-weight:700;color:#0f172a">
            <i class="bi bi-lightbulb-fill me-2 text-warning"></i>How It Works
        </div>
        <div style="padding:1.2rem 1.35rem">

            {{-- Flow diagram --}}
            @php $steps = [
                ['bi-tag-fill','#3b82f6','#eff6ff','Select category','Laptops, Printers, Servers — each category has its own spec fields.'],
                ['bi-download','#16a34a','#f0fdf4','Download template','The CSV includes base columns + spec columns for that category only.'],
                ['bi-table','#d97706','#fffbeb','Fill in Excel / Sheets','Fill the template. Spec columns are optional — leave blank if not known.'],
                ['bi-upload','#8b5cf6','#f5f3ff','Save as CSV & upload','Save As → CSV. Upload here. We\'ll show you a preview before importing.'],
                ['bi-check-circle-fill','#10b981','#f0fdf4','Specs are saved automatically','Spec values are stored per asset, shown on the asset detail page.'],
            ]; @endphp

            <div class="d-flex flex-column gap-3">
                @foreach($steps as [$icon,$fg,$bg,$title,$desc])
                <div style="display:flex;align-items:flex-start;gap:.85rem">
                    <div style="width:34px;height:34px;border-radius:10px;background:{{ $bg }};color:{{ $fg }};display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0">
                        <i class="bi {{ $icon }}"></i>
                    </div>
                    <div>
                        <div style="font-size:.8rem;font-weight:700;color:#1e293b;margin-bottom:.1rem">{{ $title }}</div>
                        <div style="font-size:.73rem;color:#64748b;line-height:1.5">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            <div style="margin-top:1.25rem;padding-top:1.1rem;border-top:1px solid #f1f5f9">
                <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.6rem">Important Notes</div>
                <ul style="font-size:.75rem;color:#64748b;margin:0;padding-left:1.1rem;line-height:1.9">
                    <li><strong>Dates</strong> must be <strong>DD-MM-YYYY</strong> (e.g. 15-01-2024)</li>
                    <li><strong>Supplier</strong> and <strong>Location</strong> must exactly match names in the system</li>
                    <li><strong>Asset Tag</strong> — leave blank to auto-generate</li>
                    <li><strong>Spec columns are optional</strong> — leave blank if unknown</li>
                    <li>Rows with errors are highlighted — fix and re-import</li>
                    <li>Import is <strong>per category</strong> — run separate imports for different categories</li>
                </ul>
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
// ── Data from server ──────────────────────────────────────────────────────
var BASE_COLS    = @json($baseColumns);
var BASE_SAMPLES = @json($baseSamples);
var BASE_REQS    = @json($baseReqs);
var BASE_DESCS   = @json($baseDescs);
var preselected  = @json($preJson);

// ── State ─────────────────────────────────────────────────────────────────
var selectedId   = preselected ? preselected.id   : '';
var selectedName = preselected ? preselected.name  : '';
var selectedFields = preselected ? (preselected.fields || []) : [];

// ── Category selection ────────────────────────────────────────────────────
function selectCategory(card) {
    document.querySelectorAll('.cat-pick-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');

    selectedId     = card.dataset.id;
    selectedName   = card.dataset.name;
    try { selectedFields = JSON.parse(card.dataset.fields || '[]'); } catch(e) { selectedFields = []; }

    document.getElementById('categoryIdInput').value = selectedId;
    updateUI();

    // Persist selection in URL so it survives navigation away and back
    var url = new URL(window.location.href);
    url.searchParams.set('category_id', selectedId);
    window.history.replaceState({}, '', url.toString());
}

function clearCategory() {
    document.querySelectorAll('.cat-pick-card').forEach(c => c.classList.remove('selected'));
    selectedId = ''; selectedName = ''; selectedFields = [];
    document.getElementById('categoryIdInput').value = '';
    updateUI();
    var url = new URL(window.location.href);
    url.searchParams.delete('category_id');
    window.history.replaceState({}, '', url.toString());
}

function updateUI() {
    // Category info panel
    var hasSelection = !!selectedId;
    document.getElementById('catInfo').style.display = hasSelection ? 'block' : 'none';
    if (hasSelection) {
        document.getElementById('catInfoName').textContent = selectedName;
        document.getElementById('catSpecInfo').innerHTML = selectedFields.length
            ? 'Spec columns: <span id="catSpecSummary">' + selectedFields.join(', ') + '</span>'
            : '<span style="color:#60a5fa">No spec fields for this category. <a href="{{ route("admin.catalog.index", ["tab"=>"categories"]) }}" target="_blank" style="color:#2563eb;font-weight:600">Add in Catalog →</a></span>';
    }

    // Upload badges
    document.getElementById('uploadCatBadge').style.display  = hasSelection ? 'flex' : 'none';
    document.getElementById('uploadNoCatNote').style.display = hasSelection ? 'none' : 'flex';
    if (hasSelection) {
        document.getElementById('uploadCatName').textContent   = selectedName;
        var cntEl = document.getElementById('uploadSpecCount');
        if (cntEl) cntEl.textContent = selectedFields.length;
    }

    // Template button
    var templateBtn = document.getElementById('templateBtn');
    var route = '{{ route("admin.bulk-import.template") }}';
    templateBtn.href = hasSelection ? route + '?category_id=' + selectedId : '#';
    templateBtn.classList.toggle('disabled', !hasSelection);
    templateBtn.setAttribute('aria-disabled', hasSelection ? 'false' : 'true');
    document.getElementById('templateBtnText').textContent = hasSelection
        ? 'Download ' + selectedName + ' Template'
        : 'Select Category First';

    setPreviewButtonState();

    // Column preview table
    rebuildColumnPreview();
}

function rebuildColumnPreview() {
    var tbody = document.getElementById('colPreviewBody');
    var rows  = '';

    // Base columns
    BASE_COLS.forEach(function(col, i) {
        var req = BASE_REQS[i] === 'req'
            ? '<span class="col-tag col-tag-req">REQUIRED</span>'
            : '<span class="col-tag col-tag-opt">OPTIONAL</span>';
        rows += '<tr>'
            + '<td style="color:#cbd5e1;font-size:.68rem">' + (i+1) + '</td>'
            + '<td><code style="background:#f1f5f9;color:#334155;border-radius:4px;padding:.1rem .4rem;font-size:.72rem">' + col + '</code></td>'
            + '<td>' + req + '</td>'
            + '<td style="font-size:.72rem;color:#64748b">' + BASE_DESCS[i] + '</td>'
            + '</tr>';
    });

    // Spec columns
    if (selectedFields.length) {
        selectedFields.forEach(function(field, j) {
            rows += '<tr class="spec-row">'
                + '<td style="color:#a78bfa;font-size:.68rem">' + (BASE_COLS.length + j + 1) + '</td>'
                + '<td><code style="background:#eef2ff;color:#4338ca;border-radius:4px;padding:.1rem .4rem;font-size:.72rem">' + field + '</code></td>'
                + '<td><span class="col-tag col-tag-spec">SPEC</span></td>'
                + '<td style="font-size:.72rem;color:#6d28d9">' + (selectedName || 'Category') + ' — optional specification field</td>'
                + '</tr>';
        });
    } else {
        rows += '<tr id="noSpecRow">'
            + '<td colspan="4" style="text-align:center;padding:.85rem;color:#94a3b8;font-size:.75rem;font-style:italic">'
            + (selectedId
                ? '<i class="bi bi-info-circle me-1"></i>This category has no spec fields — <a href="{{ route("admin.catalog.index", ["tab"=>"categories"]) }}" target="_blank" style="color:#3b82f6;font-weight:600">add them in Catalog</a>'
                : '<i class="bi bi-arrow-up me-1"></i>Select a category above to add specification columns')
            + '</td></tr>';
    }

    tbody.innerHTML = rows;
}

// ── File select ────────────────────────────────────────────────────────────
function onFileSelect(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    document.getElementById('uploadPlaceholder').style.display = 'none';
    document.getElementById('fileSelected').style.display = 'block';
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = (file.size/1024).toFixed(1) + ' KB';
    setPreviewButtonState();
}

function setPreviewButtonState() {
    var input = document.getElementById('fileInput');
    var hasFile = !!(input && input.files && input.files.length);
    document.getElementById('previewBtn').disabled = !(selectedId && hasFile);
}

// Drag & drop
var zone = document.getElementById('uploadZone');
zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', function() { zone.classList.remove('drag-over'); });
zone.addEventListener('drop', function(e) {
    e.preventDefault(); zone.classList.remove('drag-over');
    var input = document.getElementById('fileInput');
    input.files = e.dataTransfer.files;
    onFileSelect(input);
});

// Loading state
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    if (!selectedId) {
        e.preventDefault();
        alert('Please select an asset category before uploading the CSV.');
        setPreviewButtonState();
        return;
    }
    var btn = document.getElementById('previewBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Parsing…';
    btn.disabled = true;
});

// ── Init ───────────────────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', function() {
    // Pre-select card if we have a server-side preselected category
    if (preselected) {
        var card = document.querySelector('.cat-pick-card[data-id="' + preselected.id + '"]');
        if (card) card.classList.add('selected');
        updateUI();
    } else {
        rebuildColumnPreview();
    }
});
</script>
@endpush
