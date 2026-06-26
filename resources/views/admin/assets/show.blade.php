@extends('layouts.app')
@section('title', $asset->name)

@push('styles')
<style>
.asset-hero {
    background: linear-gradient(135deg,#0f172a 0%,#1e293b 60%,#1e3a5f 100%);
    border-radius: 20px; padding: 1.75rem 2rem; margin-bottom: 1.5rem;
    position: relative; overflow: hidden;
}
.asset-hero::before {
    content:'';position:absolute;top:-50px;right:-50px;width:200px;height:200px;
    border-radius:50%;background:rgba(59,130,246,.12);pointer-events:none;
}
.detail-card {
    background:#fff;border:1px solid #f1f5f9;border-radius:16px;overflow:hidden;
    margin-bottom:1rem;box-shadow:0 2px 8px rgba(15,23,42,.06);
}
.detail-card-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:.9rem 1.25rem;border-bottom:1px solid #f8fafc;
}
.detail-card-title {
    display:flex;align-items:center;gap:.5rem;font-size:.82rem;font-weight:700;color:#0f172a;
}
.detail-card-title .di {
    width:26px;height:26px;border-radius:7px;
    display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0;
}
.detail-body { padding:1.1rem 1.25rem; }
.dl-row {
    display:flex;gap:.75rem;padding:.45rem 0;border-bottom:1px solid #f8fafc;font-size:.84rem;
}
.dl-row:last-child { border-bottom:none; }
.dl-label {
    min-width:130px;flex-shrink:0;font-size:.72rem;font-weight:700;color:#94a3b8;
    text-transform:uppercase;letter-spacing:.4px;padding-top:2px;
}
.dl-value { color:#1e293b;font-weight:500;flex:1; }
.spec-grid {
    display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.6rem;
}
.spec-item {
    background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:.6rem .85rem;
}
.spec-item-label {
    font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
    color:#94a3b8;margin-bottom:.2rem;
}
.spec-item-value { font-size:.84rem;font-weight:600;color:#1e293b; }
.stat-mini {
    background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px;
    padding:.8rem 1rem;text-align:center;
}
.stat-mini-val { font-size:1.4rem;font-weight:800;color:#0f172a;letter-spacing:-.3px; }
.stat-mini-lbl { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8; }
.timeline-item { display:flex;gap:.85rem;padding:.75rem 1.25rem;border-bottom:1px solid #f8fafc; }
.timeline-item:last-child { border-bottom:none; }
.timeline-dot {
    width:32px;height:32px;border-radius:50%;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;font-size:.8rem;
}
</style>
@endpush

@section('content')

@php
    // Build spec display ordered by category template
    $rawSpecs    = $asset->specs ?? [];
    $template    = $asset->category?->spec_template ?? [];
    $specDisplay = [];
    if (!empty($template)) {
        foreach ($template as $label) {
            $key = 'spec_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($label));
            if (!empty($rawSpecs[$key])) $specDisplay[$label] = $rawSpecs[$key];
        }
        foreach ($rawSpecs as $key => $val) {
            if (!empty($val)) {
                $al = ucwords(str_replace('_', ' ', preg_replace('/^spec_/', '', $key)));
                if (!isset($specDisplay[$al])) $specDisplay[$al] = $val;
            }
        }
    } else {
        foreach ($rawSpecs as $key => $val) {
            if (!empty($val))
                $specDisplay[ucwords(str_replace('_', ' ', preg_replace('/^spec_/', '', $key)))] = $val;
        }
    }
    $brandName = $asset->assetBrand?->name ?? $asset->brand;
    $modelName = $asset->assetModel?->name ?? $asset->model;
    $statusColors = [
        'available'   => ['#f0fdf4','#16a34a'],
        'assigned'    => ['#eff6ff','#2563eb'],
        'maintenance' => ['#fffbeb','#d97706'],
        'repair'      => ['#ecfeff','#0e7490'],
        'retired'     => ['#f8fafc','#64748b'],
        'disposed'    => ['#f8fafc','#374151'],
        'lost'        => ['#fef2f2','#dc2626'],
    ];
    [$sbg,$sfg] = $statusColors[$asset->status] ?? ['#f8fafc','#64748b'];
@endphp

{{-- Hero --}}
<div class="asset-hero">
    <div class="d-flex align-items-start justify-content-between gap-3" style="position:relative;z-index:1">
        <div class="d-flex align-items-center gap-3">
            <div style="width:72px;height:72px;border-radius:16px;overflow:hidden;flex-shrink:0;
                        border:2px solid rgba(255,255,255,.12);background:rgba(255,255,255,.07);
                        display:flex;align-items:center;justify-content:center">
                @if($asset->image)
                    <img src="{{ asset('storage/'.$asset->image) }}" style="width:100%;height:100%;object-fit:cover">
                @else
                    <i class="bi {{ $asset->category?->icon ?? 'bi-box-seam' }}" style="font-size:1.8rem;color:rgba(255,255,255,.5)"></i>
                @endif
            </div>
            <div>
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:rgba(147,197,253,.7);margin-bottom:.25rem">
                    {{ $asset->category?->name ?? 'Uncategorised' }}
                </div>
                <div style="font-size:1.5rem;font-weight:800;color:#fff;letter-spacing:-.4px;line-height:1.1">
                    {{ $asset->name }}
                </div>
                <div style="margin-top:.3rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                    <code style="background:rgba(255,255,255,.1);color:#93c5fd;border-radius:6px;padding:.15rem .55rem;font-size:.78rem;font-weight:700">
                        {{ $asset->asset_tag }}
                    </code>
                    @if($brandName)
                    <span style="color:rgba(148,163,184,.75);font-size:.78rem">
                        {{ $brandName }}{{ $modelName ? ' · '.$modelName : '' }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex flex-column align-items-end gap-2">
            <span style="background:{{ $sbg }};color:{{ $sfg }};border-radius:8px;padding:.3rem .85rem;font-size:.8rem;font-weight:800">
                {{ ucfirst($asset->status) }}
            </span>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.assets.edit', $asset) }}"
                   class="btn btn-sm"
                   style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:8px;font-size:.78rem;font-weight:600;padding:.35rem .9rem">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                @if($asset->status === 'available')
                <a href="{{ route('admin.assignments.create') }}?asset_id={{ $asset->id }}"
                   class="btn btn-sm"
                   style="background:#10b981;border:none;color:#fff;border-radius:8px;font-size:.78rem;font-weight:600;padding:.35rem .9rem">
                    <i class="bi bi-person-check me-1"></i>Assign
                </a>
                @endif
                @if($asset->activeDisposal)
                <a href="{{ route('admin.disposals.show', $asset->activeDisposal) }}"
                   class="btn btn-sm"
                   style="background:#0ea5e9;border:none;color:#fff;border-radius:8px;font-size:.78rem;font-weight:600;padding:.35rem .9rem">
                    <i class="bi bi-recycle me-1"></i>Disposal
                </a>
                @elseif(!in_array($asset->status, ['disposed', 'lost'], true) && auth()->user()->hasPermission('assets.disposal.request'))
                <a href="{{ route('admin.disposals.create', ['asset_id' => $asset->id]) }}"
                   class="btn btn-sm"
                   style="background:#ef4444;border:none;color:#fff;border-radius:8px;font-size:.78rem;font-weight:600;padding:.35rem .9rem">
                    <i class="bi bi-recycle me-1"></i>Dispose
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">

        {{-- Basic details --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <div class="di" style="background:#eff6ff;color:#3b82f6"><i class="bi bi-info-circle-fill"></i></div>
                    Asset Details
                </div>
            </div>
            <div class="detail-body">
                <div class="dl-row"><div class="dl-label">Asset Tag</div>
                    <div class="dl-value"><code style="background:#f1f5f9;color:#3b82f6;border-radius:5px;padding:.15rem .5rem">{{ $asset->asset_tag }}</code></div></div>
                <div class="dl-row"><div class="dl-label">Serial Number</div><div class="dl-value">{{ $asset->serial_number ?? '—' }}</div></div>
                <div class="dl-row">
                    <div class="dl-label">Acquisition Source</div>
                    <div class="dl-value">{{ ucwords(str_replace('_', ' ', $asset->acquisition_source ?? 'manual')) }}</div>
                </div>
                @if($asset->purchaseOrder)
                <div class="dl-row">
                    <div class="dl-label">Purchase Order</div>
                    <div class="dl-value">
                        <a href="{{ route('admin.purchase-orders.show', $asset->purchaseOrder) }}" class="text-decoration-none fw-semibold">
                            {{ $asset->purchaseOrder->po_number }}
                        </a>
                        @if($asset->purchaseOrderItem)
                            <span class="text-muted"> / {{ $asset->purchaseOrderItem->item_name }}</span>
                        @endif
                    </div>
                </div>
                @endif
                @if($asset->goodsReceipt)
                <div class="dl-row">
                    <div class="dl-label">Goods Receipt</div>
                    <div class="dl-value">
                        {{ $asset->goodsReceipt->receipt_number }}
                        <span class="text-muted">on {{ $asset->goodsReceipt->received_date->format('d-m-Y') }}</span>
                    </div>
                </div>
                @endif
                <div class="dl-row">
                    <div class="dl-label">Brand</div>
                    <div class="dl-value">
                        @if($asset->assetBrand)
                            <div class="d-flex align-items-center gap-2">
                                @if($asset->assetBrand->logo)
                                    <img src="{{ Storage::url($asset->assetBrand->logo) }}" style="height:18px;object-fit:contain">
                                @endif
                                {{ $asset->assetBrand->name }}
                            </div>
                        @else {{ $asset->brand ?? '—' }}
                        @endif
                    </div>
                </div>
                <div class="dl-row">
                    <div class="dl-label">Model</div>
                    <div class="dl-value">
                        @if($asset->assetModel)<span style="font-weight:700">{{ $asset->assetModel->name }}</span>
                        @else {{ $asset->model ?? '—' }}
                        @endif
                    </div>
                </div>
                <div class="dl-row">
                    <div class="dl-label">Category</div>
                    <div class="dl-value">
                        @if($asset->category)
                            <span style="background:#f1f5f9;border-radius:7px;padding:.2rem .65rem;font-size:.8rem;display:inline-flex;align-items:center;gap:.4rem">
                                <i class="bi {{ $asset->category->icon ?? 'bi-box' }}" style="color:#3b82f6"></i>
                                {{ $asset->category->name }}
                            </span>
                        @else <span style="color:#94a3b8">—</span>
                        @endif
                    </div>
                </div>
                <div class="dl-row">
                    <div class="dl-label">Condition</div>
                    <div class="dl-value">
                        @php $condC = ['excellent'=>['#f0fdf4','#16a34a'],'good'=>['#eff6ff','#2563eb'],'fair'=>['#fffbeb','#d97706'],'poor'=>['#fef2f2','#dc2626']];
                             [$cbg,$cfg] = $condC[$asset->condition] ?? ['#f8fafc','#64748b']; @endphp
                        <span style="background:{{ $cbg }};color:{{ $cfg }};border-radius:6px;padding:.15rem .55rem;font-size:.78rem;font-weight:700">{{ ucfirst($asset->condition) }}</span>
                    </div>
                </div>
                <div class="dl-row">
                    <div class="dl-label">Supplier</div>
                    <div class="dl-value">
                        @if($asset->supplier)
                            <a href="{{ route('admin.suppliers.show', $asset->supplier) }}" style="text-decoration:none;color:#3b82f6;font-weight:600">{{ $asset->supplier->name }}</a>
                        @else <span style="color:#94a3b8">—</span>
                        @endif
                    </div>
                </div>
                <div class="dl-row"><div class="dl-label">Location</div><div class="dl-value">{{ $asset->location?->name ?? '—' }}</div></div>
                @if($asset->description)<div class="dl-row"><div class="dl-label">Description</div><div class="dl-value" style="white-space:pre-line">{{ $asset->description }}</div></div>@endif
                @if($asset->notes)<div class="dl-row"><div class="dl-label">Notes</div><div class="dl-value" style="white-space:pre-line;color:#64748b">{{ $asset->notes }}</div></div>@endif
            </div>
        </div>

        {{-- Technical Specifications --}}
        @if(!empty($specDisplay) || $asset->specifications)
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <div class="di" style="background:#eef2ff;color:#6366f1"><i class="bi bi-cpu-fill"></i></div>
                    Technical Specifications
                </div>
                <a href="{{ route('admin.assets.edit', $asset) }}"
                   style="font-size:.72rem;font-weight:700;color:#6366f1;text-decoration:none;background:#eef2ff;padding:.25rem .65rem;border-radius:8px">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            </div>
            <div class="detail-body">
                @if(!empty($specDisplay))
                <div class="spec-grid @if($asset->specifications) mb-3 @endif">
                    @foreach($specDisplay as $label => $value)
                    <div class="spec-item">
                        <div class="spec-item-label">{{ $label }}</div>
                        <div class="spec-item-value">{{ $value }}</div>
                    </div>
                    @endforeach
                </div>
                @endif
                @if($asset->specifications)
                <div style="background:#f8fafc;border-radius:10px;padding:.75rem 1rem;font-size:.82rem;color:#475569;line-height:1.6;white-space:pre-line">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:.4rem">Additional Notes</div>
                    {{ $asset->specifications }}
                </div>
                @endif
            </div>
        </div>
        @else
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <div class="di" style="background:#eef2ff;color:#6366f1"><i class="bi bi-cpu-fill"></i></div>
                    Technical Specifications
                </div>
                <a href="{{ route('admin.assets.edit', $asset) }}"
                   style="font-size:.72rem;font-weight:700;color:#3b82f6;text-decoration:none;background:#eff6ff;padding:.25rem .65rem;border-radius:8px">
                    <i class="bi bi-plus-lg me-1"></i>Add Specs
                </a>
            </div>
            <div style="padding:1.5rem 1.25rem;text-align:center;color:#94a3b8">
                <i class="bi bi-cpu" style="font-size:1.5rem;display:block;margin-bottom:.4rem;opacity:.35"></i>
                <div style="font-size:.82rem;font-weight:600">No specifications recorded</div>
                <div style="font-size:.75rem;margin-top:.2rem">
                    @if(!$asset->category)Assign a category to enable spec fields
                    @else Edit this asset to add {{ $asset->category->name }} specifications
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Purchase & Warranty --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <div class="di" style="background:#f0fdf4;color:#16a34a"><i class="bi bi-receipt-cutoff"></i></div>
                    Purchase & Warranty
                </div>
            </div>
            <div class="detail-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-mini">
                            <div class="stat-mini-val" style="font-size:1.15rem">{!! $asset->purchase_price ? '&#8377;'.number_format($asset->purchase_price,0) : '—' !!}</div>
                            <div class="stat-mini-lbl">Purchase Price</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-mini">
                            <div class="stat-mini-val" style="font-size:1rem">{{ $asset->purchase_date?->format('d-m-Y') ?? '—' }}</div>
                            <div class="stat-mini-lbl">Purchase Date</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        @php $wexp = $asset->warranty_expiry_date; @endphp
                        <div class="stat-mini">
                            <div class="stat-mini-val {{ $wexp?->isPast() ? 'text-danger' : '' }}" style="font-size:1rem">{{ $wexp?->format('d-m-Y') ?? '—' }}</div>
                            <div class="stat-mini-lbl">Warranty Expiry</div>
                            @if($wexp)
                            <div style="font-size:.68rem;margin-top:.2rem;color:{{ $wexp->isPast() ? '#dc2626' : '#94a3b8' }}">
                                {{ $wexp->isPast() ? 'Expired '.$wexp->diffForHumans() : 'Expires '.$wexp->diffForHumans() }}
                            </div>
                            @endif
                        </div>
                    </div>
                    @if($asset->warranty_terms)
                    <div class="col-12">
                        <div class="dl-row" style="padding:0;border:none">
                            <div class="dl-label">Warranty Terms</div>
                            <div class="dl-value">{{ $asset->warranty_terms }}</div>
                        </div>
                    </div>
                    @endif
                    @if($asset->depreciationRecord)
                    <div class="col-12">
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:.75rem 1rem">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#16a34a">Current Book Value</div>
                                    <div style="font-size:1.5rem;font-weight:800;color:#15803d">&#8377;{{ number_format($asset->current_value, 2) }}</div>
                                </div>
                                <div style="text-align:right;font-size:.75rem;color:#16a34a">
                                    <div>{{ ucfirst(str_replace('_',' ',$asset->depreciationRecord->method)) }}</div>
                                    <div>{{ $asset->depreciationRecord->useful_life_years }}-year useful life</div>
                                    <div>Salvage: &#8377;{{ number_format($asset->depreciationRecord->salvage_value,0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Disposal History --}}
        @if($asset->disposals->isNotEmpty())
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <div class="di" style="background:#fef2f2;color:#dc2626"><i class="bi bi-recycle"></i></div>
                    Disposal History
                </div>
            </div>
            @foreach($asset->disposals->sortByDesc('requested_date') as $disposal)
            <div class="timeline-item">
                <div class="timeline-dot" style="background:#f8fafc;color:#64748b">
                    <i class="bi bi-recycle"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <a href="{{ route('admin.disposals.show', $disposal) }}" style="font-weight:700;font-size:.84rem;color:#1e293b;text-decoration:none">
                        {{ $disposal->method_label }}
                    </a>
                    <div style="font-size:.72rem;color:#64748b;margin-top:.1rem">
                        Requested {{ $disposal->requested_date?->format('d-m-Y') }} by {{ $disposal->requestedBy?->name ?? '-' }}
                    </div>
                    @if($disposal->disposed_date)
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:.1rem">Disposed {{ $disposal->disposed_date->format('d-m-Y') }}</div>
                    @endif
                </div>
                <div class="text-end">
                    <span class="badge bg-{{ $disposal->status_badge }}">{{ ucfirst($disposal->status) }}</span>
                    @if($disposal->recovered_value)
                        <div style="font-size:.72rem;color:#16a34a;font-weight:700;margin-top:.25rem">&#8377;{{ number_format($disposal->recovered_value, 0) }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Assignment History --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <div class="di" style="background:#f0fdf4;color:#10b981"><i class="bi bi-person-check-fill"></i></div>
                    Assignment History
                    <span style="background:#f1f5f9;color:#64748b;border-radius:6px;padding:.05rem .45rem;font-size:.72rem;font-weight:800;margin-left:.2rem">{{ $asset->assignments->count() }}</span>
                </div>
                @if($asset->status === 'available')
                <a href="{{ route('admin.assignments.create') }}?asset_id={{ $asset->id }}"
                   style="font-size:.72rem;font-weight:700;color:#10b981;text-decoration:none;background:#f0fdf4;padding:.25rem .65rem;border-radius:8px">
                    <i class="bi bi-plus-lg me-1"></i>Assign Now
                </a>
                @endif
            </div>
            @forelse($asset->assignments->sortByDesc('assigned_date') as $a)
            <div class="timeline-item">
                <div class="timeline-dot" style="background:{{ $a->status==='active'?'#f0fdf4':'#f8fafc' }};color:{{ $a->status==='active'?'#10b981':'#94a3b8' }}">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:.84rem;color:#1e293b">{{ $a->user?->name ?? $a->department?->name ?? 'Department' }}</div>
                    <div style="font-size:.72rem;color:#64748b;margin-top:.1rem">
                        {{ $a->assigned_date->format('d-m-Y') }} →
                        {{ $a->actual_return_date?->format('d-m-Y') ?? ($a->expected_return_date?->format('d-m-Y') ? 'Expected '.$a->expected_return_date->format('d-m-Y') : 'Ongoing') }}
                    </div>
                    @if($a->purpose)<div style="font-size:.72rem;color:#94a3b8;margin-top:.1rem">{{ $a->purpose }}</div>@endif
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                    <span style="background:{{ $a->status==='active'?'#f0fdf4':'#f8fafc' }};color:{{ $a->status==='active'?'#16a34a':'#64748b' }};border-radius:6px;padding:.1rem .5rem;font-size:.7rem;font-weight:700">
                        {{ ucfirst($a->status) }}
                    </span>
                    @if($a->condition_in)<span style="font-size:.68rem;color:#94a3b8">In: {{ ucfirst($a->condition_in) }}</span>@endif
                </div>
            </div>
            @empty
            <div style="padding:1.5rem;text-align:center;color:#94a3b8">
                <i class="bi bi-person-x" style="font-size:1.5rem;display:block;margin-bottom:.4rem;opacity:.3"></i>
                <div style="font-size:.82rem;font-weight:600">Never assigned</div>
            </div>
            @endforelse
        </div>

    </div>

    <div class="col-lg-4">

        {{-- Quick Stats --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <div class="di" style="background:#f5f3ff;color:#8b5cf6"><i class="bi bi-graph-up-arrow"></i></div>
                    Quick Stats
                </div>
            </div>
            <div class="detail-body">
                <div class="row g-2">
                    <div class="col-6"><div class="stat-mini"><div class="stat-mini-val">{{ $asset->assignments->count() }}</div><div class="stat-mini-lbl">Assignments</div></div></div>
                    <div class="col-6"><div class="stat-mini"><div class="stat-mini-val">{{ $asset->maintenanceRecords->count() }}</div><div class="stat-mini-lbl">Maintenance</div></div></div>
                    <div class="col-12"><div class="stat-mini"><div class="stat-mini-val" style="font-size:1.2rem">&#8377;{{ number_format($asset->maintenanceRecords->sum('total_cost'),0) }}</div><div class="stat-mini-lbl">Maintenance Cost</div></div></div>
                </div>
            </div>
        </div>

        {{-- Maintenance --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <div class="di" style="background:#fffbeb;color:#d97706"><i class="bi bi-tools"></i></div>
                    Maintenance
                    <span style="background:#f1f5f9;color:#64748b;border-radius:6px;padding:.05rem .45rem;font-size:.72rem;font-weight:800;margin-left:.2rem">{{ $asset->maintenanceRecords->count() }}</span>
                </div>
                <a href="{{ route('admin.maintenance.create') }}?asset_id={{ $asset->id }}"
                   style="font-size:.72rem;font-weight:700;color:#d97706;text-decoration:none;background:#fffbeb;padding:.25rem .65rem;border-radius:8px">
                    <i class="bi bi-plus-lg me-1"></i>Log
                </a>
            </div>
            @forelse($asset->maintenanceRecords->sortByDesc('scheduled_date')->take(6) as $m)
            <div style="padding:.7rem 1.25rem;border-bottom:1px solid #f8fafc;font-size:.8rem">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-weight:700;color:#1e293b">{{ ucfirst($m->type) }}</div>
                        <div style="color:#94a3b8;font-size:.7rem;margin-top:.1rem">
                            {{ $m->scheduled_date?->format('d-m-Y') }}
                            @if($m->technician_name) · {{ $m->technician_name }} @endif
                        </div>
                        @if($m->description)<div style="color:#64748b;margin-top:.2rem;font-size:.75rem">{{ Str::limit($m->description,60) }}</div>@endif
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1">
                        @php $mBadgeC=['scheduled'=>['#eff6ff','#2563eb'],'in_progress'=>['#fffbeb','#d97706'],'completed'=>['#f0fdf4','#16a34a'],'cancelled'=>['#f8fafc','#64748b']];
                             [$mb,$mf]=$mBadgeC[$m->status]??['#f8fafc','#64748b']; @endphp
                        <span style="background:{{ $mb }};color:{{ $mf }};border-radius:6px;padding:.1rem .45rem;font-size:.68rem;font-weight:700">{{ ucfirst(str_replace('_',' ',$m->status)) }}</span>
                        @if($m->total_cost>0)<span style="font-size:.68rem;color:#ef4444;font-weight:600">&#8377;{{ number_format($m->total_cost,0) }}</span>@endif
                    </div>
                </div>
            </div>
            @empty
            <div style="padding:1.5rem;text-align:center;color:#94a3b8">
                <i class="bi bi-check-circle-fill text-success" style="font-size:1.5rem;display:block;margin-bottom:.4rem"></i>
                <div style="font-size:.82rem;font-weight:600">No maintenance records</div>
            </div>
            @endforelse
        </div>

    </div>
</div>
@endsection
