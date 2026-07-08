@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Products</h4>
        <p>Niyantron platform products. OpsBridge is registered as the first product foundation.</p>
    </div>
    <a href="{{ route('super-admin.dashboard') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
</div>

<div class="row g-3">
    @forelse($products as $product)
        <div class="col-lg-6 col-xl-4">
            <div class="table-card h-100" style="padding:0;overflow:hidden">
                <div style="height:4px;background:#2563eb"></div>
                <div class="p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width:48px;height:48px;background:linear-gradient(135deg,#2563eb,#14b8a6)">
                            <i class="bi {{ $product->icon }}"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-700 text-truncate" style="color:#0f172a">{{ $product->name }}</div>
                            <div class="text-muted small">{{ $product->domain ?: 'No domain configured' }}</div>
                            <span class="badge bg-{{ $product->status_badge }}">{{ ucwords(str_replace('_', ' ', $product->status)) }}</span>
                        </div>
                        <a href="{{ route('super-admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary flex-shrink-0">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>

                    <p class="text-muted small mb-3" style="min-height:42px">{{ $product->description ?: 'No product description configured.' }}</p>

                    <div class="pt-3 border-top d-flex justify-content-between text-center">
                        <div>
                            <div class="fw-700">{{ $product->registered_modules_count }}</div>
                            <div class="text-muted small">Modules</div>
                        </div>
                        <div>
                            <div class="fw-700">{{ $product->enabled_modules_count }}</div>
                            <div class="text-muted small">Org Modules</div>
                        </div>
                        <div>
                            <div class="fw-700">{{ $product->sort_order }}</div>
                            <div class="text-muted small">Order</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="table-card text-center py-5 text-muted">
                <i class="bi bi-grid-3x3-gap fs-1 d-block mb-2 opacity-25"></i>
                <div class="fw-500 mb-1">No products registered</div>
                <small>The platform product seed should create OpsBridge during migration.</small>
            </div>
        </div>
    @endforelse
</div>
@endsection
