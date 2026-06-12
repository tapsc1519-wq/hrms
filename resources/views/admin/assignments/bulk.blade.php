@extends('layouts.app')
@section('title', 'Bulk Assign Assets')

@push('styles')
<style>
.assign-header {
    background:linear-gradient(135deg,#064e3b 0%,#065f46 50%,#047857 100%);
    border-radius:18px;padding:1.6rem 2rem;color:#fff;position:relative;overflow:hidden;margin-bottom:1.5rem;
}
.assign-header::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.07);pointer-events:none;}

.asset-checkbox-card {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
    padding:.75rem 1rem;display:flex;align-items:center;gap:.85rem;
    cursor:pointer;transition:all .15s;
}
.asset-checkbox-card:hover { border-color:#10b981; background:#f0fdf4; }
.asset-checkbox-card.selected { border-color:#10b981; background:#f0fdf4; box-shadow:0 0 0 3px rgba(16,185,129,.12); }
.asset-checkbox-card input[type=checkbox] { width:18px;height:18px;accent-color:#10b981;flex-shrink:0;cursor:pointer; }

.asset-icon-sm {
    width:36px;height:36px;border-radius:10px;
    background:#f1f5f9;color:#64748b;
    display:flex;align-items:center;justify-content:center;
    font-size:.9rem;flex-shrink:0;
}
.asset-name { font-size:.83rem;font-weight:700;color:#1e293b;line-height:1.2; }
.asset-meta { font-size:.7rem;color:#94a3b8;font-weight:500; }

/* Sticky action bar */
.bulk-action-bar {
    position:sticky;bottom:0;left:0;right:0;
    background:#fff;border-top:2px solid #10b981;
    padding:1rem 1.5rem;
    display:flex;align-items:center;gap:1rem;flex-wrap:wrap;
    box-shadow:0 -8px 24px rgba(0,0,0,.1);
    z-index:100;
    transition:transform .25s, opacity .25s;
    transform:translateY(100%);opacity:0;
}
.bulk-action-bar.visible { transform:translateY(0);opacity:1; }

.assign-panel {
    background:#fff;border:1px solid #e2e8f0;border-radius:18px;
    position:sticky;top:80px;
    overflow:hidden;
}
.assign-panel-header {
    padding:1.1rem 1.4rem;border-bottom:1px solid #f8fafc;
    font-size:.85rem;font-weight:700;color:#0f172a;
}
.assign-panel-body { padding:1.4rem; }

.selected-count-badge {
    background:#10b981;color:#fff;border-radius:8px;
    padding:.15rem .6rem;font-size:.78rem;font-weight:800;
    min-width:24px;text-align:center;
}
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="assign-header">
    <div class="d-flex align-items-start justify-content-between">
        <div>
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:rgba(167,243,208,.8);margin-bottom:.3rem">
                <i class="bi bi-person-check-fill me-1"></i>Bulk Assignment
            </div>
            <div style="font-size:1.6rem;font-weight:800;letter-spacing:-.3px;margin-bottom:.2rem">Assign Multiple Assets</div>
            <div style="font-size:.82rem;color:rgba(167,243,208,.8)">
                Select available assets, choose a user or department, and assign them all at once.
            </div>
        </div>
        <a href="{{ route('admin.assignments.index') }}" class="btn btn-sm"
           style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:9px;font-size:.8rem;font-weight:600;padding:.45rem 1rem">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger rounded-3 mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
</div>
@endif
@if(session('success'))
<div class="alert alert-success rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
</div>
@endif

<form action="{{ route('admin.assignments.bulk.store') }}" method="POST" id="bulkAssignForm">
@csrf

<div class="row g-4">

    {{-- Left: Asset selector --}}
    <div class="col-lg-8">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden">

            {{-- Filter bar --}}
            <div style="padding:1rem 1.2rem;border-bottom:1px solid #f8fafc">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    {{-- Select all --}}
                    <div class="d-flex align-items-center gap-2 me-1">
                        <input type="checkbox" id="selectAll" style="width:18px;height:18px;accent-color:#10b981;cursor:pointer"
                               onchange="toggleAll(this)">
                        <label for="selectAll" style="font-size:.8rem;font-weight:700;color:#334155;cursor:pointer;margin:0">
                            Select All
                        </label>
                        <span class="selected-count-badge" id="selectedCount">0</span>
                        <span style="font-size:.75rem;color:#94a3b8">selected</span>
                    </div>

                    <div class="vr" style="height:20px;opacity:.3"></div>

                    {{-- Search --}}
                    <div class="input-group input-group-sm" style="width:200px">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted" style="font-size:.75rem"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Name, tag…" value="{{ request('search') }}"
                               form="filterForm">
                    </div>

                    {{-- Category filter --}}
                    <form id="filterForm" action="{{ route('admin.assignments.bulk') }}" method="GET" class="d-flex gap-2">
                        @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                        <select name="category_id" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected':'' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:8px">
                            <i class="bi bi-search"></i>
                        </button>
                        @if(request()->anyFilled(['search','category_id']))
                        <a href="{{ route('admin.assignments.bulk') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
                            <i class="bi bi-x-lg"></i>
                        </a>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Asset list --}}
            <div style="padding:1rem;max-height:580px;overflow-y:auto">
                @if($assets->isEmpty())
                <div style="text-align:center;padding:3rem 1rem;color:#94a3b8">
                    <i class="bi bi-box-seam" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
                    <div style="font-size:.85rem;font-weight:600">No available assets found</div>
                    @if(request()->anyFilled(['search','category_id']))
                    <div style="font-size:.75rem;margin-top:.3rem">Try clearing your filters</div>
                    @endif
                </div>
                @else
                <div class="row g-2">
                    @foreach($assets as $asset)
                    <div class="col-md-6">
                        <label class="asset-checkbox-card w-100" id="card_{{ $asset->id }}">
                            <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}"
                                   onchange="onAssetToggle(this)">
                            <div class="asset-icon-sm">
                                @if($asset->image)
                                    <img src="{{ asset('storage/'.$asset->image) }}" style="width:100%;height:100%;object-fit:cover;border-radius:8px">
                                @else
                                    <i class="bi bi-box-seam"></i>
                                @endif
                            </div>
                            <div style="flex:1;min-width:0">
                                <div class="asset-name">{{ Str::limit($asset->name, 30) }}</div>
                                <div class="asset-meta d-flex gap-2 mt-1 flex-wrap">
                                    <span style="background:#f1f5f9;border-radius:4px;padding:1px 5px;font-family:monospace;font-size:.65rem">{{ $asset->asset_tag }}</span>
                                    @if($asset->category)<span>{{ $asset->category->name }}</span>@endif
                                    @if($asset->assetBrand)<span>{{ $asset->assetBrand->name }}</span>@endif
                                </div>
                            </div>
                            <span style="background:#f0fdf4;color:#16a34a;border-radius:6px;padding:.15rem .5rem;font-size:.68rem;font-weight:700;flex-shrink:0">
                                Available
                            </span>
                        </label>
                    </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($assets->hasPages())
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing {{ $assets->firstItem() }}–{{ $assets->lastItem() }} of {{ $assets->total() }} available assets
                    </small>
                    {{ $assets->appends(request()->only(['search','category_id']))->links() }}
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Right: Assign panel --}}
    <div class="col-lg-4">
        <div class="assign-panel">
            <div class="assign-panel-header">
                <i class="bi bi-person-check-fill me-2 text-success"></i>Assignment Details
            </div>
            <div class="assign-panel-body">
                <div class="mb-3 p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0">
                    <div style="font-size:.78rem;font-weight:700;color:#15803d">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        <span id="panelCount">0 assets</span> selected
                    </div>
                    <div style="font-size:.72rem;color:#4ade80;margin-top:.15rem">
                        Select assets from the list on the left
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:.78rem">Assign To (User)</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">— Select User —</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:.78rem">Or Assign To (Department)</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">— Select Department —</option>
                        @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Either a user or department is required</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:.78rem">Assignment Date <span class="req">*</span></label>
                    <input type="date" name="assigned_date" class="form-control form-control-sm"
                           value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:.78rem">Expected Return Date</label>
                    <input type="date" name="expected_return_date" class="form-control form-control-sm">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:.78rem">Condition at Assignment <span class="req">*</span></label>
                    <select name="condition_out" class="form-select form-select-sm" required>
                        <option value="excellent">Excellent</option>
                        <option value="good" selected>Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-600" style="font-size:.78rem">Purpose / Notes</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2"
                              placeholder="Reason for assignment…"></textarea>
                </div>

                <button type="submit" id="assignBtn" class="btn btn-success w-100" style="border-radius:10px" disabled>
                    <i class="bi bi-person-check-fill me-2"></i>Assign <span id="assignBtnCount">0</span> Asset<span id="assignBtnPlural">s</span>
                </button>
                <div class="text-center mt-2" style="font-size:.72rem;color:#94a3b8">
                    Assets will be marked as "Assigned" automatically
                </div>
            </div>
        </div>
    </div>

</div>
</form>

@endsection

@push('scripts')
<script>
var selectedIds = new Set();

function onAssetToggle(checkbox) {
    const card = checkbox.closest('.asset-checkbox-card');
    if (checkbox.checked) {
        selectedIds.add(checkbox.value);
        card.classList.add('selected');
    } else {
        selectedIds.delete(checkbox.value);
        card.classList.remove('selected');
    }
    updateCounts();
}

function toggleAll(masterCheckbox) {
    document.querySelectorAll('input[name="asset_ids[]"]').forEach(cb => {
        cb.checked = masterCheckbox.checked;
        onAssetToggle(cb);
    });
}

function updateCounts() {
    const n = selectedIds.size;
    document.getElementById('selectedCount').textContent = n;
    document.getElementById('panelCount').textContent    = n + ' asset' + (n !== 1 ? 's' : '');
    document.getElementById('assignBtnCount').textContent = n;
    document.getElementById('assignBtnPlural').textContent = n !== 1 ? 's' : '';
    document.getElementById('assignBtn').disabled = n === 0;
}

// Loading state on submit
document.getElementById('bulkAssignForm').addEventListener('submit', function() {
    if (selectedIds.size === 0) {
        alert('Please select at least one asset.');
        return false;
    }
    const btn = document.getElementById('assignBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning…';
    btn.disabled = true;
});
</script>
@endpush
