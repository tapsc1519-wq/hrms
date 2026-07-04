@extends('layouts.app')

@section('title', 'Vendors')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Vendors</h4>
        <p>{{ $vendors->total() }} repair/service vendor{{ $vendors->total() !== 1 ? 's' : '' }} registered</p>
    </div>
    <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add Vendor
    </a>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search vendor name or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach(['active','inactive','blacklisted'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.vendors.index') }}" class="btn btn-light btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse($vendors as $vendor)
        <div class="col-md-6 col-xl-4">
            <div class="table-card h-100" style="padding:0;overflow:hidden">
                <div style="height:4px;background:{{ $vendor->status === 'active' ? '#22c55e' : ($vendor->status === 'blacklisted' ? '#ef4444' : '#94a3b8') }}"></div>
                <div class="p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center fw-700 text-white flex-shrink-0" style="width:48px;height:48px;font-size:1.2rem;background:linear-gradient(135deg,#f59e0b,#0f766e)">
                            {{ strtoupper(substr($vendor->name, 0, 1)) }}
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-700 text-truncate" style="color:#0f172a">{{ $vendor->name }}</div>
                            <div class="text-muted small">{{ $vendor->code ?: 'No vendor code' }}</div>
                            <span class="badge bg-{{ $vendor->status === 'active' ? 'success' : ($vendor->status === 'blacklisted' ? 'danger' : 'secondary') }}">{{ ucfirst($vendor->status) }}</span>
                        </div>
                        <div class="dropdown flex-shrink-0">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="{{ route('admin.vendors.edit', $vendor) }}"><i class="bi bi-pencil me-2 text-warning"></i>Edit</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('admin.vendors.destroy', $vendor) }}" method="POST" onsubmit="return confirm('Delete this vendor?')">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="small text-muted mb-3">
                        @if($vendor->contact_person)<div><i class="bi bi-person me-1"></i>{{ $vendor->contact_person }}</div>@endif
                        @if($vendor->email)<div><i class="bi bi-envelope me-1"></i>{{ $vendor->email }}</div>@endif
                        @if($vendor->phone)<div><i class="bi bi-telephone me-1"></i>{{ $vendor->phone }}</div>@endif
                        @if($vendor->city)<div><i class="bi bi-geo-alt me-1"></i>{{ $vendor->city }}</div>@endif
                    </div>
                    <div class="pt-3 border-top d-flex justify-content-between text-center">
                        <div><div class="fw-700">{{ $vendor->asset_repairs_count }}</div><div class="text-muted small">Repairs</div></div>
                        <div><div class="fw-700">{{ $vendor->amc_contracts_count }}</div><div class="text-muted small">AMC</div></div>
                        <div><div class="fw-700">{{ number_format((float) $vendor->rating, 1) }}</div><div class="text-muted small">Rating</div></div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="table-card text-center py-5 text-muted">
                <i class="bi bi-tools fs-1 d-block mb-2 opacity-25"></i>
                <div class="fw-500 mb-1">No vendors found</div>
                <small class="d-block mb-3">Add repair/service vendors to use them in Asset Repairs and AMC Contracts.</small>
                <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add First Vendor</a>
            </div>
        </div>
    @endforelse
</div>

@if($vendors->hasPages())
    <div class="mt-3">{{ $vendors->links() }}</div>
@endif
@endsection
