@extends('layouts.app')
@section('title', 'Assets')

@section('content')

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>IT Assets</h4>
        <p>{{ $assets->total() }} asset{{ $assets->total() !== 1 ? 's' : '' }} in your inventory</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.bulk-import.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-cloud-upload me-1"></i>Bulk Import
        </a>
        <a href="{{ route('admin.assets.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Add Asset
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filters --}}
<div class="table-card mb-3" style="padding:0">
    <div class="px-4 py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Name, tag, serial…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(['available','assigned','maintenance','repair','retired','disposed','lost'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Supplier</label>
                <select name="vendor_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $v)
                        <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Condition</label>
                <select name="condition" class="form-select form-select-sm">
                    <option value="">All Conditions</option>
                    @foreach(['excellent','good','fair','poor'] as $c)
                        <option value="{{ $c }}" {{ request('condition') == $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel"></i>
                    </button>
                    @if(request()->anyFilled(['search','status','category_id','vendor_id','condition']))
                    <a href="{{ route('admin.assets.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
    @if(request()->anyFilled(['search','status','category_id','vendor_id','condition']))
    <div class="px-4 pb-2 d-flex gap-2 flex-wrap">
        @if(request('search'))
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary" style="font-size:.75rem">
            <i class="bi bi-search me-1"></i>{{ request('search') }}
        </span>
        @endif
        @if(request('status'))
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary" style="font-size:.75rem">
            Status: {{ ucfirst(request('status')) }}
        </span>
        @endif
        @if(request('condition'))
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary" style="font-size:.75rem">
            Condition: {{ ucfirst(request('condition')) }}
        </span>
        @endif
    </div>
    @endif
</div>

{{-- Table --}}
<div class="table-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <th class="px-4 py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Asset Tag</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Asset</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Category</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Supplier</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Assigned To</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Value</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Status</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Condition</th>
                    <th class="py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                <tr>
                    <td class="px-4">
                        <code style="background:#f1f5f9;color:#3b82f6;padding:.2rem .45rem;border-radius:5px;font-size:.78rem;font-weight:700">
                            {{ $asset->asset_tag }}
                        </code>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($asset->image)
                                <img src="{{ asset('storage/' . $asset->image) }}"
                                     class="rounded-2" width="36" height="36" style="object-fit:cover;flex-shrink:0">
                            @else
                                <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:36px;height:36px;background:#f1f5f9">
                                    <i class="bi {{ $asset->category?->icon ?? 'bi-box' }} text-primary"></i>
                                </div>
                            @endif
                            <div>
                                <a href="{{ route('admin.assets.show', $asset) }}"
                                   class="text-decoration-none fw-600" style="color:#334155">
                                    {{ $asset->name }}
                                </a>
                                <br><small class="text-muted">{{ $asset->brand }} {{ $asset->model }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <small class="fw-500" style="color:#475569">{{ $asset->category?->name ?? '—' }}</small>
                    </td>
                    <td>
                        <small class="text-muted">{{ $asset->supplier?->name ?? '—' }}</small>
                    </td>
                    <td>
                        @if($asset->activeAssignment)
                            <div class="d-flex align-items-center gap-1">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-700 flex-shrink-0"
                                     style="width:22px;height:22px;font-size:.62rem;background:#3b82f6">
                                    {{ strtoupper(substr($asset->activeAssignment->user?->name ?? 'D', 0, 1)) }}
                                </div>
                                <small class="fw-500" style="color:#334155">{{ $asset->activeAssignment->user?->name ?? 'Dept.' }}</small>
                            </div>
                        @else
                            <small class="text-muted">—</small>
                        @endif
                    </td>
                    <td>
                        <span class="fw-600" style="color:#334155">
                            {!! $asset->purchase_price ? '&#8377;'.number_format($asset->purchase_price, 0) : '—' !!}
                        </span>
                    </td>
                    <td><span class="badge bg-{{ $asset->status_badge }}">{{ ucfirst($asset->status) }}</span></td>
                    <td>
                        @php
                        $condColors = ['excellent'=>'success','good'=>'info','fair'=>'warning','poor'=>'danger'];
                        $cond = $asset->condition ?? 'good';
                        @endphp
                        <span class="badge bg-{{ $condColors[$cond] ?? 'secondary' }}">{{ ucfirst($cond) }}</span>
                    </td>
                    <td class="pe-3">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.875rem">
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.assets.show', $asset) }}">
                                        <i class="bi bi-eye me-2 text-primary"></i>View
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.assets.edit', $asset) }}">
                                        <i class="bi bi-pencil me-2 text-warning"></i>Edit
                                    </a>
                                </li>
                                @if($asset->activeDisposal)
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.disposals.show', $asset->activeDisposal) }}">
                                        <i class="bi bi-recycle me-2 text-info"></i>Disposal in Progress
                                    </a>
                                </li>
                                @elseif(!in_array($asset->status, ['disposed', 'lost'], true) && auth()->user()->hasPermission('assets.disposal.request'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.disposals.create', ['asset_id' => $asset->id]) }}">
                                        <i class="bi bi-recycle me-2 text-danger"></i>Create Disposal
                                    </a>
                                </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('admin.assets.destroy', $asset) }}" method="POST"
                                          onsubmit="return confirm('Delete this asset? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger">
                                            <i class="bi bi-trash me-2"></i>Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        @include('partials._empty_state', [
                            'icon' => 'bi-box-seam',
                            'title' => request()->hasAny(['search', 'status', 'category_id', 'supplier_id']) ? 'No assets match these filters' : 'Start your asset register',
                            'message' => request()->hasAny(['search', 'status', 'category_id', 'supplier_id'])
                                ? 'Try clearing filters or search with asset tag, serial number, user name, or asset name.'
                                : 'Add laptops, desktops, phones, servers and other IT assets before assignments, repairs, disposal and reports can work well.',
                            'actionRoute' => route('admin.assets.create'),
                            'actionLabel' => 'Add Asset',
                            'secondaryRoute' => request()->hasAny(['search', 'status', 'category_id', 'supplier_id']) ? route('admin.assets.index') : null,
                        ])
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assets->hasPages())
    <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between">
        <small class="text-muted">
            Showing {{ $assets->firstItem() }}–{{ $assets->lastItem() }} of {{ $assets->total() }} assets
        </small>
        {{ $assets->links() }}
    </div>
    @endif
</div>

@endsection
