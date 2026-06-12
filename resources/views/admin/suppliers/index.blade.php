@extends('layouts.app')
@section('title', 'Suppliers')

@section('content')

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Suppliers</h4>
        <p>{{ $suppliers->total() }} supplier{{ $suppliers->total() !== 1 ? 's' : '' }} registered</p>
    </div>
    <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add Supplier
    </a>
</div>

{{-- Filters --}}
<div class="table-card mb-3" style="padding:0">
    <div class="px-4 py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Name, email, contact…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active"      {{ request('status') == 'active'      ? 'selected' : '' }}>Active</option>
                    <option value="inactive"    {{ request('status') == 'inactive'    ? 'selected' : '' }}>Inactive</option>
                    <option value="blacklisted" {{ request('status') == 'blacklisted' ? 'selected' : '' }}>Blacklisted</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(request()->anyFilled(['search','status']))
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary btn-sm ms-1">
                    <i class="bi bi-x-lg me-1"></i>Clear
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Supplier Cards --}}
<div class="row g-3">
    @forelse($suppliers as $supplier)
    <div class="col-md-6 col-xl-4">
        <div class="table-card h-100" style="padding:0;overflow:hidden">

            {{-- Status accent bar --}}
            @php
            $accentColor = $supplier->status === 'active' ? '#22c55e' : ($supplier->status === 'blacklisted' ? '#ef4444' : '#94a3b8');
            @endphp
            <div style="height:4px;background:{{ $accentColor }}"></div>

            <div class="p-4">
                {{-- Header --}}
                <div class="d-flex align-items-start gap-3 mb-3">
                    @if($supplier->logo)
                        <img src="{{ asset('storage/' . $supplier->logo) }}"
                             class="rounded-3 flex-shrink-0"
                             style="width:48px;height:48px;object-fit:cover">
                    @else
                        <div class="rounded-3 d-flex align-items-center justify-content-center fw-700 text-white flex-shrink-0"
                             style="width:48px;height:48px;font-size:1.2rem;background:linear-gradient(135deg,#3b82f6,#6366f1)">
                            {{ strtoupper(substr($supplier->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-700 text-truncate" style="color:#0f172a">{{ $supplier->name }}</div>
                        @if($supplier->code)
                        <div style="font-size:.75rem;color:#94a3b8;font-family:monospace">{{ $supplier->code }}</div>
                        @endif
                        <div class="mt-1">
                            <span class="badge bg-{{ $supplier->status === 'active' ? 'success' : ($supplier->status === 'blacklisted' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($supplier->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="dropdown flex-shrink-0">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.875rem">
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.suppliers.show', $supplier) }}">
                                    <i class="bi bi-eye me-2 text-primary"></i>View
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.suppliers.edit', $supplier) }}">
                                    <i class="bi bi-pencil me-2 text-warning"></i>Edit
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="POST"
                                      onsubmit="return confirm('Delete this supplier? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="dropdown-item text-danger">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Contact details --}}
                <div style="font-size:.8rem;color:#64748b" class="mb-3">
                    @if($supplier->contact_person)
                    <div class="mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-person text-muted" style="width:14px"></i>
                        <span>{{ $supplier->contact_person }}</span>
                    </div>
                    @endif
                    @if($supplier->email)
                    <div class="mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-envelope text-muted" style="width:14px"></i>
                        <span class="text-truncate">{{ $supplier->email }}</span>
                    </div>
                    @endif
                    @if($supplier->phone)
                    <div class="mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-telephone text-muted" style="width:14px"></i>
                        <span>{{ $supplier->phone }}</span>
                    </div>
                    @endif
                    @if($supplier->city)
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt text-muted" style="width:14px"></i>
                        <span>{{ $supplier->city }}@if($supplier->country), {{ $supplier->country }}@endif</span>
                    </div>
                    @endif
                </div>

                {{-- Stats footer --}}
                <div class="pt-3 border-top d-flex justify-content-between text-center">
                    <div>
                        <div class="fw-700" style="color:#334155">{{ $supplier->assets_count }}</div>
                        <div class="text-muted" style="font-size:.72rem">Assets</div>
                    </div>
                    <div>
                        <div class="fw-700" style="color:#334155">{{ $supplier->purchase_orders_count }}</div>
                        <div class="text-muted" style="font-size:.72rem">POs</div>
                    </div>
                    <div>
                        <div>
                            @for($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star{{ $i <= round($supplier->rating) ? '-fill' : '' }} text-warning" style="font-size:.75rem"></i>
                            @endfor
                        </div>
                        <div class="text-muted" style="font-size:.72rem">{{ $supplier->rating }}/5</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="table-card text-center py-5 text-muted">
            <i class="bi bi-shop fs-1 d-block mb-2 opacity-25"></i>
            <div class="fw-500 mb-1">No suppliers found</div>
            <small class="d-block mb-3">Add suppliers to link them to assets, purchase orders, and invoices.</small>
            <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Add First Supplier
            </a>
        </div>
    </div>
    @endforelse
</div>

@if($suppliers->hasPages())
<div class="mt-3 d-flex justify-content-between align-items-center">
    <small class="text-muted">
        Showing {{ $suppliers->firstItem() }}–{{ $suppliers->lastItem() }} of {{ $suppliers->total() }} suppliers
    </small>
    {{ $suppliers->links() }}
</div>
@endif

@endsection
