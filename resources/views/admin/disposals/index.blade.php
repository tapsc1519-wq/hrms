@extends('layouts.app')

@section('title', 'Asset Disposal')

@section('content')
@php
    $clearRoute = match ($mode) {
        'approvals' => route('admin.disposals.approvals'),
        'history' => route('admin.disposals.history'),
        default => route('admin.disposals.requests'),
    };
@endphp
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>{{ $pageTitle }}</h4>
        <p>{{ $pageDescription }}</p>
    </div>
    @if($mode === 'requests' && auth()->user()->hasPermission('assets.disposal.request'))
    <div class="d-flex gap-2">
        <a href="{{ route('admin.disposals.bulk') }}" class="btn btn-outline-primary">
            <i class="bi bi-upc-scan me-1"></i> Bulk Disposal
        </a>
        <a href="{{ route('admin.disposals.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Disposal
        </a>
    </div>
    @endif
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
            @if(auth()->user()->hasPermission('assets.disposal.request'))
            <a href="{{ route('admin.disposals.requests') }}" class="btn btn-sm {{ $mode === 'requests' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-send me-1"></i> Disposal Requests
            </a>
            @endif
            @if(auth()->user()->hasPermission('assets.disposal.approve'))
            <a href="{{ route('admin.disposals.approvals') }}" class="btn btn-sm {{ $mode === 'approvals' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-check2-square me-1"></i> Disposal Approvals
            </a>
            @endif
            @if(auth()->user()->hasPermission('assets.disposal.view'))
            <a href="{{ route('admin.disposals.history') }}" class="btn btn-sm {{ $mode === 'history' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-clock-history me-1"></i> Disposal History
            </a>
            @endif
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient grad-orange"><div class="card-body">
            <div class="stat-label">Pending</div><div class="stat-number">{{ $stats['pending'] }}</div>
            <div class="stat-sub">Awaiting approval</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient grad-blue"><div class="card-body">
            <div class="stat-label">Approved</div><div class="stat-number">{{ $stats['approved'] }}</div>
            <div class="stat-sub">Ready to complete</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient grad-green"><div class="card-body">
            <div class="stat-label">Completed</div><div class="stat-number">{{ $stats['completed'] }}</div>
            <div class="stat-sub">Disposed assets</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient grad-teal"><div class="card-body">
            <div class="stat-label">Recovered</div><div class="stat-number">&#8377;{{ number_format((float) $stats['recovered'], 0) }}</div>
            <div class="stat-sub">Recovered value</div>
        </div></div>
    </div>
</div>

<div class="table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Asset name, tag or serial">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach($defaultStatusOptions as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Method</label>
                <select name="method" class="form-select">
                    <option value="">All Methods</option>
                    @foreach(['scrap','sell','donate','recycle','return_to_supplier','destroy'] as $method)
                        <option value="{{ $method }}" @selected(request('method') === $method)>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if(request()->hasAny(['search','status','method']))
                    <a href="{{ $clearRoute }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Asset</th>
                    <th>Method</th>
                    <th>Buyer / Recipient</th>
                    <th>Requested</th>
                    <th>Requested By</th>
                    <th>Recovery</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disposals as $disposal)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $disposal->asset?->name }}</div>
                            <div class="text-muted small">{{ $disposal->asset?->asset_tag }} &middot; {{ $disposal->asset?->category?->name ?? 'Uncategorised' }}</div>
                        </td>
                        <td>{{ $disposal->method_label }}</td>
                        <td>
                            <div class="fw-semibold">{{ $disposal->disposalBuyer?->name ?? $disposal->recipient_name ?? '-' }}</div>
                            <div class="text-muted small">{{ $disposal->disposalBuyer?->type_label ?? 'Manual / pending' }}</div>
                        </td>
                        <td>{{ $disposal->requested_date?->format('d-m-Y') }}</td>
                        <td>{{ $disposal->requestedBy?->name ?? '-' }}</td>
                        <td>
                            <div class="fw-bold">&#8377;{{ number_format((float) ($disposal->recovered_value ?? $disposal->expected_value ?? 0), 0) }}</div>
                            <div class="text-muted small">{{ $disposal->recovered_value ? 'Recovered' : 'Expected' }}</div>
                        </td>
                        <td><span class="badge bg-{{ $disposal->status_badge }}">{{ ucfirst($disposal->status) }}</span></td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-1">
                                @if($mode === 'requests')
                                    <a href="{{ route('admin.disposals.show', ['disposal' => $disposal, 'context' => 'requests']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> View Request
                                    </a>
                                    @if(in_array($disposal->status, ['pending', 'approved'], true) && auth()->user()->hasPermission('assets.disposal.request'))
                                        <form method="POST" action="{{ route('admin.disposals.cancel', $disposal) }}" onsubmit="return confirm('Cancel this disposal request?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-x-lg me-1"></i> Cancel
                                            </button>
                                        </form>
                                    @endif
                                @elseif($mode === 'approvals')
                                    <a href="{{ route('admin.disposals.show', ['disposal' => $disposal, 'context' => 'approvals']) }}" class="btn btn-sm {{ $disposal->status === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">
                                        <i class="bi {{ $disposal->status === 'pending' ? 'bi-check2-square' : 'bi-archive' }} me-1"></i>
                                        {{ $disposal->status === 'pending' ? 'Review' : 'Complete' }}
                                    </a>
                                @else
                                    <a href="{{ route('admin.disposals.show', ['disposal' => $disposal, 'context' => 'history']) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-clock-history me-1"></i> View History
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-recycle fs-1 d-block mb-2 opacity-25"></i>
                            {{ $emptyText }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($disposals->hasPages())
        <div class="p-3 border-top">{{ $disposals->links() }}</div>
    @endif
</div>
@endsection
