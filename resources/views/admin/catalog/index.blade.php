@extends('layouts.app')
@section('title', 'Asset Catalog')

@push('styles')
<style>
/* ── Tab Nav ── */
.catalog-tabs {
    display: flex;
    gap: .5rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: .4rem;
    margin-bottom: 1.5rem;
    width: fit-content;
}
.catalog-tab {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .55rem 1.2rem;
    border-radius: 10px;
    font-size: .82rem;
    font-weight: 600;
    color: #64748b;
    border: none;
    background: none;
    cursor: pointer;
    text-decoration: none;
    transition: all .18s;
}
.catalog-tab:hover { background: #f8fafc; color: #334155; }
.catalog-tab.active { background: #3b82f6; color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,.3); }
.catalog-tab .tab-count {
    background: rgba(255,255,255,.25);
    border-radius: 20px;
    padding: .05rem .45rem;
    font-size: .7rem;
    font-weight: 700;
}
.catalog-tab:not(.active) .tab-count {
    background: #f1f5f9;
    color: #94a3b8;
}

/* ── Cards ── */
.catalog-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    transition: transform .2s, box-shadow .2s;
    overflow: hidden;
    position: relative;
}
.catalog-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(15,23,42,.1); }
.catalog-card-actions {
    position: absolute;
    top: .75rem; right: .75rem;
    display: flex;
    gap: .35rem;
    opacity: 0;
    transition: opacity .15s;
}
.catalog-card:hover .catalog-card-actions { opacity: 1; }
.catalog-action-btn {
    width: 28px; height: 28px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem;
    cursor: pointer;
    color: #64748b;
    transition: all .15s;
}
.catalog-action-btn:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
.catalog-action-btn.del:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }

/* ── Brand logo ── */
.brand-logo-wrap {
    width: 64px; height: 64px;
    border-radius: 14px;
    border: 1.5px solid #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
    background: #f8fafc;
    flex-shrink: 0;
}
.brand-logo-wrap img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }

/* ── Category icon ── */
.cat-icon-wrap {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    margin-bottom: .75rem;
}

/* ── Spec preview chips ── */
.spec-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    background: #f1f5f9; border-radius: 6px;
    padding: .2rem .55rem;
    font-size: .68rem; font-weight: 600; color: #475569;
}

/* ── Add card ── */
.add-card {
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
    border-radius: 16px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    min-height: 160px;
    cursor: pointer;
    transition: all .2s;
    color: #94a3b8;
    text-decoration: none;
}
.add-card:hover { border-color: #3b82f6; background: #eff6ff; color: #3b82f6; }
.add-card i { font-size: 1.8rem; margin-bottom: .4rem; }
.add-card span { font-size: .82rem; font-weight: 700; }

/* ── Icon picker ── */
.icon-picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(38px, 1fr));
    gap: .35rem;
    max-height: 200px;
    overflow-y: auto;
    padding: .5rem;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}
.icon-picker-btn {
    width: 38px; height: 38px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    color: #475569;
    transition: all .15s;
}
.icon-picker-btn:hover, .icon-picker-btn.selected {
    border-color: #3b82f6; background: #eff6ff; color: #3b82f6;
}

/* ── Models table ── */
.models-table { font-size: .855rem; }
.models-table th {
    background: #f8fafc;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    color: #64748b; padding: .75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}
.models-table td { padding: .75rem 1rem; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
.models-table tr:last-child td { border-bottom: none; }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-grid-1x2-fill me-2 text-primary"></i>Asset Catalog</h4>
        <p>Manage categories, brands, and models for your IT assets.</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Tabs --}}
<div class="catalog-tabs">
    <a href="{{ route('admin.catalog.index', ['tab'=>'categories']) }}"
       class="catalog-tab {{ $activeTab === 'categories' ? 'active' : '' }}">
        <i class="bi bi-tag-fill"></i> Categories
        <span class="tab-count">{{ $categories->count() }}</span>
    </a>
    <a href="{{ route('admin.catalog.index', ['tab'=>'brands']) }}"
       class="catalog-tab {{ $activeTab === 'brands' ? 'active' : '' }}">
        <i class="bi bi-building-fill"></i> Brands
        <span class="tab-count">{{ $brands->count() }}</span>
    </a>
    <a href="{{ route('admin.catalog.index', ['tab'=>'models']) }}"
       class="catalog-tab {{ $activeTab === 'models' ? 'active' : '' }}">
        <i class="bi bi-cpu-fill"></i> Models
        <span class="tab-count">{{ $models->count() }}</span>
    </a>
</div>

{{-- ══════════════════════════ CATEGORIES TAB ══════════════════════════ --}}
@if($activeTab === 'categories')
<div class="row g-3">

    {{-- Add new card --}}
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <div class="add-card" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-circle-fill"></i>
            <span>New Category</span>
        </div>
    </div>

    @foreach($categories as $cat)
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <div class="catalog-card p-3 text-center" style="min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center">
            <div class="catalog-card-actions">
                <button class="catalog-action-btn" title="Edit"
                    onclick='openEditCategory(@json($cat))'><i class="bi bi-pencil"></i></button>
                <form action="{{ route('admin.catalog.categories.destroy', $cat) }}" method="POST"
                      onsubmit="return confirm('Delete category? Assets in this category will become uncategorised.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="catalog-action-btn del" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </div>
            <div class="cat-icon-wrap" style="background:{{ ['#eff6ff','#f0fdf4','#fffbeb','#fdf4ff','#fff1f2','#f0fdfa'][crc32($cat->name)%6] }}">
                <i class="bi {{ $cat->icon ?? 'bi-box' }}" style="color:{{ ['#3b82f6','#10b981','#f59e0b','#a855f7','#ef4444','#14b8a6'][crc32($cat->name)%6] }}"></i>
            </div>
            <div style="font-weight:700;font-size:.85rem;color:#1e293b;margin-bottom:.2rem">{{ $cat->name }}</div>
            <div style="font-size:.72rem;color:#94a3b8">{{ $cat->assets_count }} asset{{ $cat->assets_count != 1 ? 's':'' }}</div>
            <div style="font-size:.68rem;color:#cbd5e1;margin-top:.2rem">{{ $cat->depreciation_years }}yr dep.</div>
        </div>
    </div>
    @endforeach

</div>
@endif

{{-- ══════════════════════════ BRANDS TAB ══════════════════════════ --}}
@if($activeTab === 'brands')
<div class="row g-3">

    <div class="col-6 col-md-4 col-lg-3">
        <div class="add-card" data-bs-toggle="modal" data-bs-target="#addBrandModal">
            <i class="bi bi-plus-circle-fill"></i>
            <span>New Brand</span>
        </div>
    </div>

    @foreach($brands as $brand)
    <div class="col-6 col-md-4 col-lg-3">
        <div class="catalog-card p-3">
            <div class="catalog-card-actions">
                <button class="catalog-action-btn" onclick='openEditBrand(@json($brand))'><i class="bi bi-pencil"></i></button>
                <form action="{{ route('admin.catalog.brands.destroy', $brand) }}" method="POST"
                      onsubmit="return confirm('Delete this brand?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="catalog-action-btn del"><i class="bi bi-trash"></i></button>
                </form>
            </div>
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="brand-logo-wrap">
                    @if($brand->logo)
                        <img src="{{ Storage::url($brand->logo) }}" alt="{{ $brand->name }}">
                    @else
                        <span style="font-size:1.4rem;font-weight:900;color:#94a3b8">
                            {{ strtoupper(substr($brand->name,0,1)) }}
                        </span>
                    @endif
                </div>
                <div>
                    <div style="font-weight:700;font-size:.9rem;color:#1e293b">{{ $brand->name }}</div>
                    @if($brand->website)
                    <a href="{{ $brand->website }}" target="_blank" style="font-size:.7rem;color:#3b82f6;text-decoration:none">
                        <i class="bi bi-link-45deg"></i> Website
                    </a>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 mt-2">
                <div style="flex:1;background:#f8fafc;border-radius:8px;padding:.4rem .6rem;text-align:center">
                    <div style="font-size:1.1rem;font-weight:800;color:#1e293b">{{ $brand->models_count }}</div>
                    <div style="font-size:.65rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Models</div>
                </div>
                <div style="flex:1;background:#f8fafc;border-radius:8px;padding:.4rem .6rem;text-align:center">
                    <div style="font-size:1.1rem;font-weight:800;color:#1e293b">{{ $brand->assets_count }}</div>
                    <div style="font-size:.65rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Assets</div>
                </div>
            </div>
            <div class="mt-2">
                <span class="pill {{ $brand->is_active ? 'pill-green' : 'pill-gray' }}"
                      style="font-size:.65rem;padding:.15rem .55rem">
                    {{ $brand->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endif

{{-- ══════════════════════════ MODELS TAB ══════════════════════════ --}}
@if($activeTab === 'models')
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden">
    <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid #f8fafc">
        <div style="font-size:.85rem;font-weight:700;color:#0f172a">
            <i class="bi bi-cpu-fill me-2 text-primary"></i>{{ $models->count() }} Model{{ $models->count()!=1?'s':'' }}
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModelModal">
            <i class="bi bi-plus-lg me-1"></i>Add Model
        </button>
    </div>

    @if($models->isEmpty())
    <div style="padding:3rem;text-align:center;color:#94a3b8">
        <i class="bi bi-cpu" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
        <div style="font-size:.85rem;font-weight:600">No models yet — add your first model above</div>
    </div>
    @else
    <div class="table-responsive">
        <table class="models-table w-100">
            <thead>
                <tr>
                    <th>Model</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Specifications</th>
                    <th class="text-center">Assets</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($models as $m)
            <tr>
                <td>
                    <div style="font-weight:700;color:#1e293b">{{ $m->name }}</div>
                </td>
                <td>
                    @if($m->brand)
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:26px;height:26px;border-radius:7px;border:1px solid #f1f5f9;background:#f8fafc;
                                    display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                            @if($m->brand->logo)
                                <img src="{{ Storage::url($m->brand->logo) }}" style="width:100%;height:100%;object-fit:contain;padding:2px">
                            @else
                                <span style="font-size:.65rem;font-weight:900;color:#94a3b8">{{ strtoupper(substr($m->brand->name,0,1)) }}</span>
                            @endif
                        </div>
                        <span style="color:#334155;font-weight:600">{{ $m->brand->name }}</span>
                    </div>
                    @else
                    <span style="color:#cbd5e1">—</span>
                    @endif
                </td>
                <td>
                    @if($m->category)
                        <span class="spec-chip"><i class="bi {{ $m->category->icon ?? 'bi-box' }} me-1"></i>{{ $m->category->name }}</span>
                    @else
                        <span style="color:#cbd5e1">—</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        @if(!empty($m->default_specs))
                            @foreach(array_slice($m->default_specs, 0, 3) as $k => $v)
                                <span class="spec-chip">{{ $v }}</span>
                            @endforeach
                            @if(count($m->default_specs) > 3)
                                <span class="spec-chip">+{{ count($m->default_specs)-3 }} more</span>
                            @endif
                        @else
                            <span style="color:#cbd5e1;font-size:.75rem">No specs</span>
                        @endif
                    </div>
                </td>
                <td class="text-center">
                    <span style="font-weight:700;color:#1e293b">{{ $m->assets_count }}</span>
                </td>
                <td class="text-center">
                    <span class="pill {{ $m->is_active ? 'pill-green':'pill-gray' }}" style="font-size:.65rem;padding:.15rem .55rem">
                        {{ $m->is_active ? 'Active':'Inactive' }}
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="catalog-action-btn" onclick='openEditModel(@json($m))'
                                style="opacity:1"><i class="bi bi-pencil"></i></button>
                        <form action="{{ route('admin.catalog.models.destroy', $m) }}" method="POST"
                              onsubmit="return confirm('Delete this model?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="catalog-action-btn del" style="opacity:1"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endif

{{-- ══════ MODALS ══════ --}}

{{-- Add Category --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <form action="{{ route('admin.catalog.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;padding:1.2rem 1.5rem">
                    <h6 class="modal-title fw-700"><i class="bi bi-tag-fill me-2 text-primary"></i>New Category</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem">
                    @include('admin.catalog._category_fields')
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:1rem 1.5rem">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Category --}}
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <form id="editCategoryForm" method="POST">
                @csrf @method('PATCH')
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;padding:1.2rem 1.5rem">
                    <h6 class="modal-title fw-700"><i class="bi bi-pencil me-2 text-warning"></i>Edit Category</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem">
                    @include('admin.catalog._category_fields', ['edit' => true])
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:1rem 1.5rem">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Brand --}}
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <form action="{{ route('admin.catalog.brands.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;padding:1.2rem 1.5rem">
                    <h6 class="modal-title fw-700"><i class="bi bi-building-fill me-2 text-primary"></i>New Brand</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem">
                    @include('admin.catalog._brand_fields')
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:1rem 1.5rem">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Brand --}}
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <form id="editBrandForm" method="POST" enctype="multipart/form-data">
                @csrf @method('PATCH')
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;padding:1.2rem 1.5rem">
                    <h6 class="modal-title fw-700"><i class="bi bi-pencil me-2 text-warning"></i>Edit Brand</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem">
                    @include('admin.catalog._brand_fields', ['edit' => true])
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:1rem 1.5rem">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Model --}}
<div class="modal fade" id="addModelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;border:none">
            <form action="{{ route('admin.catalog.models.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;padding:1.2rem 1.5rem">
                    <h6 class="modal-title fw-700"><i class="bi bi-cpu-fill me-2 text-primary"></i>New Model</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem">
                    @include('admin.catalog._model_fields', ['brands' => $brands, 'categories' => $categories])
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:1rem 1.5rem">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save Model</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Model --}}
<div class="modal fade" id="editModelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;border:none">
            <form id="editModelForm" method="POST">
                @csrf @method('PATCH')
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;padding:1.2rem 1.5rem">
                    <h6 class="modal-title fw-700"><i class="bi bi-pencil me-2 text-warning"></i>Edit Model</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem">
                    @include('admin.catalog._model_fields', ['brands' => $brands, 'categories' => $categories, 'edit' => true])
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:1rem 1.5rem">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ══════════════════════════════════════════════
// SPEC TAG INPUT
// ══════════════════════════════════════════════
function handleSpecTagKey(e, suffix) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        var val = e.target.value.trim().replace(/,$/, '');
        if (val) addSpecTag(val, suffix);
        e.target.value = '';
    } else if (e.key === 'Backspace' && e.target.value === '') {
        var wrap = document.getElementById('specTagsWrap' + suffix);
        var tags = wrap.querySelectorAll('.spec-tag');
        if (tags.length) removeSpecTag(tags[tags.length-1].querySelector('button'));
    }
}
function addSpecTag(label, suffix) {
    var wrap   = document.getElementById('specTagsWrap' + suffix);
    var input  = document.getElementById('specTagInput' + suffix);
    var hidden = document.getElementById('specFieldsHidden' + suffix);
    var tag = document.createElement('span');
    tag.className = 'spec-tag';
    tag.style.cssText = 'display:inline-flex;align-items:center;gap:.3rem;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:6px;padding:.2rem .55rem;font-size:.75rem;font-weight:600';
    tag.innerHTML = label + ' <button type="button" onclick="removeSpecTag(this)" style="background:none;border:none;color:#93c5fd;cursor:pointer;padding:0;font-size:.7rem;line-height:1">✕</button>';
    wrap.insertBefore(tag, input);
    updateSpecHidden(suffix);
}
function removeSpecTag(btn) {
    var tag = btn.closest('.spec-tag');
    var suffix = '';
    // Determine suffix from parent wrap id
    var wrap = tag.closest('[id^=specTagsWrap]');
    if (wrap) suffix = wrap.id.replace('specTagsWrap','');
    tag.remove();
    updateSpecHidden(suffix);
}
function updateSpecHidden(suffix) {
    var wrap   = document.getElementById('specTagsWrap' + suffix);
    var hidden = document.getElementById('specFieldsHidden' + suffix);
    var labels = Array.from(wrap.querySelectorAll('.spec-tag')).map(t => t.textContent.trim().replace(' ✕','').trim());
    hidden.value = labels.join(',');
}
function loadSpecPreset(suffix) {
    var sel = document.getElementById('specPreset' + suffix);
    if (!sel.value) return;
    // Clear existing tags
    var wrap = document.getElementById('specTagsWrap' + suffix);
    wrap.querySelectorAll('.spec-tag').forEach(t => t.remove());
    // Add preset fields
    sel.value.split(',').forEach(f => { if (f.trim()) addSpecTag(f.trim(), suffix); });
    updateSpecHidden(suffix);
}

// ══════════════════════════════════════════════
// ICON PICKER
// ══════════════════════════════════════════════
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[class^=icon-picker-btn]');
    if (!btn) return;
    var suffix = btn.classList[0].replace('icon-picker-btn','');
    var body = btn.closest('.modal-body');
    if (!body) return;
    body.querySelectorAll('[class^=icon-picker-btn]').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    var hidden  = body.querySelector('[name=icon]');
    var preview = body.querySelector('[class^=icon-preview]');
    if (hidden)  hidden.value     = btn.dataset.icon;
    if (preview) preview.className = 'bi ' + btn.dataset.icon + ' icon-preview' + suffix;
});

// ══════════════════════════════════════════════
// CATEGORY EDIT MODAL
// ══════════════════════════════════════════════
function openEditCategory(cat) {
    var f = document.getElementById('editCategoryForm');
    f.action = '/admin/catalog/categories/' + cat.id;
    f.querySelector('[name=name]').value               = cat.name || '';
    f.querySelector('[name=description]').value        = cat.description || '';
    f.querySelector('[name=depreciation_years]').value = cat.depreciation_years || 3;
    // Icon
    var hiddenIcon = f.querySelector('[name=icon]');
    hiddenIcon.value = cat.icon || 'bi-box';
    f.querySelectorAll('[class^=icon-picker-btn]').forEach(btn => {
        btn.classList.toggle('selected', btn.dataset.icon === hiddenIcon.value);
    });
    // Spec tags
    var wrap   = document.getElementById('specTagsWrap_edit');
    var input  = document.getElementById('specTagInput_edit');
    var hidden = document.getElementById('specFieldsHidden_edit');
    wrap.querySelectorAll('.spec-tag').forEach(t => t.remove());
    var fields = cat.spec_template || [];
    fields.forEach(f2 => addSpecTag(f2, '_edit'));
    updateSpecHidden('_edit');
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

// ══════════════════════════════════════════════
// BRAND EDIT MODAL
// ══════════════════════════════════════════════
function openEditBrand(brand) {
    var f = document.getElementById('editBrandForm');
    f.action = '/admin/catalog/brands/' + brand.id;
    f.querySelector('[name=name]').value    = brand.name    || '';
    f.querySelector('[name=website]').value = brand.website || '';
    f.querySelector('[name=is_active]').checked = !!brand.is_active;
    new bootstrap.Modal(document.getElementById('editBrandModal')).show();
}

// ══════════════════════════════════════════════
// MODEL SPEC FIELDS (dynamic by category)
// ══════════════════════════════════════════════
function loadModelSpecFields(sel, prefillValues, showSection) {
    var section = sel.closest('form').querySelector('#modelSpecSection') ||
                  sel.closest('.modal-body').querySelector('#modelSpecSection');
    var container = sel.closest('form').querySelector('#modelSpecFields') ||
                    sel.closest('.modal-body').querySelector('#modelSpecFields');
    if (!sel.value) { section.style.display = 'none'; container.innerHTML = ''; return; }
    var opt    = sel.options[sel.selectedIndex];
    var fields = [];
    try { fields = JSON.parse(opt.dataset.fields || '[]'); } catch(e) {}
    if (!fields.length) { section.style.display = 'none'; container.innerHTML = ''; return; }

    var hasPrefill = prefillValues && Object.keys(prefillValues).length > 0;
    section.style.display = (showSection || hasPrefill) ? '' : 'none';
    container.innerHTML = '';
    fields.forEach(function(label) {
        var key  = 'spec_' + label.toLowerCase().replace(/[^a-z0-9]+/g,'_');
        var val  = (prefillValues && prefillValues[key]) ? prefillValues[key] : '';
        var html = '<div class="col-md-6">'
                 + '<label style="font-size:.72rem;font-weight:600;color:#475569">' + label + '</label>'
                 + '<input type="text" name="' + key + '" class="form-control form-control-sm"'
                 + ' value="' + val.replace(/"/g,'&quot;') + '" placeholder="' + label + '…">'
                 + '</div>';
        container.insertAdjacentHTML('beforeend', html);
    });
}

function toggleModelDefaultSpecs(button) {
    var form = button.closest('form');
    var catSel = form.querySelector('[name=category_id]');
    if (!catSel.value) {
        alert('Please select category first.');
        return;
    }

    loadModelSpecFields(catSel, {}, true);
}

// ══════════════════════════════════════════════
// MODEL EDIT MODAL
// ══════════════════════════════════════════════
function openEditModel(m) {
    var f = document.getElementById('editModelForm');
    f.action = '/admin/catalog/models/' + m.id;
    f.querySelector('[name=name]').value        = m.name     || '';
    f.querySelector('[name=brand_id]').value    = m.brand_id || '';
    f.querySelector('[name=is_active]').checked = !!m.is_active;

    var catSel = f.querySelector('[name=category_id]');
    catSel.value = m.category_id || '';
    // Trigger spec fields render only when this model already has saved defaults.
    loadModelSpecFields(catSel, m.default_specs || {}, Object.keys(m.default_specs || {}).length > 0);

    new bootstrap.Modal(document.getElementById('editModelModal')).show();
}

// Init — if add model modal already has a category preselected (after validation error)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#modelCategorySelect').forEach(function(sel) {
        if (sel.value) loadModelSpecFields(sel, {}, false);
    });
});
</script>
@endpush
