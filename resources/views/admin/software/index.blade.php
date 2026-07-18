@extends('layouts.app')
@section('title', 'Software Catalog')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-display me-2 text-primary"></i>Software Catalog</h4>
        <p>{{ $stats['total_titles'] }} {{ Str::plural('title', $stats['total_titles']) }} in your catalog</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.software-licenses.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-key me-1"></i>All Licenses
        </a>
        <a href="{{ route('admin.software.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add Software
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-blue h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-display-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $stats['total_titles'] }}</div>
                        <div class="stat-label">Software Titles</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-green h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-key-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $stats['total_licenses'] }}</div>
                        <div class="stat-label">Active Licenses</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-orange h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $stats['expiring_soon'] }}</div>
                        <div class="stat-label">Expiring ≤ 30 Days</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-purple h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $stats['total_assigned'] }}</div>
                        <div class="stat-label">Total Assigned</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <div class="input-group" style="max-width:240px">
                <span class="input-group-text bg-white border-end-0" style="border:1.5px solid #e2e8f0;border-radius:9px 0 0 9px">
                    <i class="bi bi-search text-muted" style="font-size:.85rem"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search software…"
                       class="form-control border-start-0" style="border-radius:0 9px 9px 0">
            </div>
            <select name="category" class="form-select" style="width:auto;min-width:160px">
                <option value="">All Categories</option>
                @foreach(['productivity','security','design','development','communication','database','erp','operating_system','other'] as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>
                        {{ ucwords(str_replace('_',' ',$cat)) }}
                    </option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['search','category']))
                <a href="{{ route('admin.software.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i>Clear
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Software Grid --}}
@if($software->isEmpty())
    <div class="table-card">
        @include('partials._empty_state', [
            'icon' => 'bi-display',
            'title' => request()->hasAny(['search', 'category', 'status']) ? 'No software titles match these filters' : 'Build your software catalog',
            'message' => request()->hasAny(['search', 'category', 'status'])
                ? 'Clear filters or search by software name, vendor, version or category.'
                : 'Add software titles first, then record licenses, assignments, policies and compliance evidence.',
            'actionRoute' => route('admin.software.create'),
            'actionLabel' => 'Add Software',
            'secondaryRoute' => request()->hasAny(['search', 'category', 'status']) ? route('admin.software.index') : null,
        ])
    </div>
@else
    <div class="row g-3">
        @foreach($software as $sw)
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
            <div class="card h-100 border-0 shadow-sm" style="border-radius:16px;overflow:hidden;transition:transform .2s,box-shadow .2s"
                 onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 28px rgba(0,0,0,.13)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                {{-- Accent bar --}}
                <div style="height:4px;background:linear-gradient(90deg,#3b82f6,#6366f1)"></div>
                <div class="card-body p-3">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        {{-- Icon / Logo --}}
                        @if($sw->icon)
                            <img src="{{ Storage::url($sw->icon) }}" alt="{{ $sw->name }}"
                                 style="width:44px;height:44px;border-radius:10px;object-fit:cover;flex-shrink:0">
                        @else
                            <div style="width:44px;height:44px;border-radius:10px;flex-shrink:0;
                                        background:linear-gradient(135deg,#3b82f6,#6366f1);
                                        display:flex;align-items:center;justify-content:center;
                                        color:#fff;font-size:1.25rem">
                                <i class="bi {{ $sw->category_icon }}"></i>
                            </div>
                        @endif
                        <div style="min-width:0">
                            <div class="fw-700 text-truncate" style="color:#0f172a;font-size:.9rem">{{ $sw->name }}</div>
                            @if($sw->vendor)
                                <div class="text-muted" style="font-size:.75rem">{{ $sw->vendor }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge rounded-pill" style="font-size:.68rem;background:#eff6ff;color:#2563eb;font-weight:600">
                            {{ $sw->category_label }}
                        </span>
                        @if($sw->version)
                            <span class="text-muted" style="font-size:.72rem">v{{ $sw->version }}</span>
                        @endif
                    </div>

                    {{-- Seat usage --}}
                    <div class="mb-3">
                        @php $used = $sw->used_seats; $total = $sw->total_seats; $pct = $total > 0 ? min(100, round($used/$total*100)) : 0; @endphp
                        <div class="d-flex justify-content-between mb-1" style="font-size:.72rem;color:#64748b">
                            <span><i class="bi bi-people-fill me-1"></i>{{ $used }} / {{ $total }} seats</span>
                            <span>{{ $pct }}%</span>
                        </div>
                        <div class="progress" style="height:5px;border-radius:99px">
                            <div class="progress-bar {{ $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success') }}"
                                 style="width:{{ $pct }}%"></div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-auto">
                        <a href="{{ route('admin.software.show', $sw) }}" class="btn btn-sm btn-primary flex-fill" style="font-size:.78rem">
                            <i class="bi bi-eye me-1"></i>Manage
                        </a>
                        <a href="{{ route('admin.software.edit', $sw) }}" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($software->hasPages())
        <div class="d-flex align-items-center justify-content-between mt-4">
            <div class="text-muted" style="font-size:.8rem">
                Showing {{ $software->firstItem() }}–{{ $software->lastItem() }} of {{ $software->total() }} titles
            </div>
            {{ $software->links() }}
        </div>
    @endif
@endif
@endsection
