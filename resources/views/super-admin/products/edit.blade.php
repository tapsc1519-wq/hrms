@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
<div class="page-header">
    <a href="{{ route('super-admin.products.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to Products</a>
    <h4>Edit {{ $product->name }}</h4>
    <p>Manage product identity used by the Niyantron platform foundation.</p>
</div>

<form method="POST" action="{{ route('super-admin.products.update', $product) }}">
    @csrf
    @method('PUT')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="icon-wrap icon-blue"><i class="bi {{ $product->icon }}"></i></span>
                    Product Details
                </div>
                <div class="form-card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Product Name <span class="req">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Short Name</label>
                            <input type="text" name="short_name" value="{{ old('short_name', $product->short_name) }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Domain</label>
                            <input type="text" name="domain" value="{{ old('domain', $product->domain) }}" class="form-control" placeholder="opsbridge.niyantron.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">App Path</label>
                            <input type="text" name="app_path" value="{{ old('app_path', $product->app_path) }}" class="form-control" placeholder="/admin/dashboard">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Icon <span class="req">*</span></label>
                            <input type="text" name="icon" value="{{ old('icon', $product->icon) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Color <span class="req">*</span></label>
                            <input type="text" name="color" value="{{ old('color', $product->color) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sort Order <span class="req">*</span></label>
                            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $product->sort_order) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="req">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach(['active' => 'Active', 'coming_soon' => 'Coming Soon', 'inactive' => 'Inactive'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $product->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ route('super-admin.products.index') }}" class="btn btn-light">Cancel</a>
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Product</button>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card">
                <div class="form-card-header"><h5 class="mb-0">Registered Modules</h5></div>
                <div class="form-card-body">
                    @forelse($modules as $key => $module)
                        <div class="d-flex align-items-start gap-2 py-2 border-bottom">
                            <span class="icon-wrap icon-purple" style="width:32px;height:32px"><i class="bi {{ $module['icon'] }}"></i></span>
                            <div>
                                <div class="fw-semibold" style="font-size:.84rem">{{ $module['name'] }}</div>
                                <div class="text-muted small">{{ $key }} | {{ $module['short_name'] }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small">No modules registered for this product yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
