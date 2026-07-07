@extends('layouts.app')

@section('title', 'Disposal Buyers')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Disposal Buyers</h4>
        <p>{{ $buyers->total() }} buyer, recycler, auction winner or donation recipient record{{ $buyers->total() !== 1 ? 's' : '' }}</p>
    </div>
    <a href="{{ route('admin.disposal-buyers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add Buyer
    </a>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search buyer, contact, email or phone..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All buyer types</option>
                    @foreach(['employee' => 'Employee Buyer', 'external_buyer' => 'External Buyer', 'vendor_recycler' => 'Vendor / Recycler', 'auction_buyer' => 'Auction Buyer', 'donation_recipient' => 'Donation Recipient'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach(['active','inactive','blacklisted'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.disposal-buyers.index') }}" class="btn btn-light btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse($buyers as $buyer)
        <div class="col-md-6 col-xl-4">
            <div class="table-card h-100" style="padding:0;overflow:hidden">
                <div style="height:4px;background:{{ $buyer->status === 'active' ? '#22c55e' : ($buyer->status === 'blacklisted' ? '#ef4444' : '#94a3b8') }}"></div>
                <div class="p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center fw-700 text-white flex-shrink-0" style="width:48px;height:48px;font-size:1.2rem;background:linear-gradient(135deg,#14b8a6,#2563eb)">
                            {{ strtoupper(substr($buyer->name, 0, 1)) }}
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-700 text-truncate" style="color:#0f172a">{{ $buyer->name }}</div>
                            <div class="text-muted small">{{ $buyer->type_label }}</div>
                            <span class="badge bg-{{ $buyer->status_badge }}">{{ ucfirst($buyer->status) }}</span>
                        </div>
                        <div class="dropdown flex-shrink-0">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="{{ route('admin.disposal-buyers.edit', $buyer) }}"><i class="bi bi-pencil me-2 text-warning"></i>Edit</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('admin.disposal-buyers.destroy', $buyer) }}" method="POST" onsubmit="return confirm('Delete this buyer?')">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="small text-muted mb-3">
                        @if($buyer->contact_person)<div><i class="bi bi-person me-1"></i>{{ $buyer->contact_person }}</div>@endif
                        @if($buyer->email)<div><i class="bi bi-envelope me-1"></i>{{ $buyer->email }}</div>@endif
                        @if($buyer->phone)<div><i class="bi bi-telephone me-1"></i>{{ $buyer->phone }}</div>@endif
                        @if($buyer->tax_number)<div><i class="bi bi-receipt me-1"></i>{{ $buyer->tax_number }}</div>@endif
                    </div>
                    <div class="pt-3 border-top d-flex justify-content-between text-center">
                        <div><div class="fw-700">{{ $buyer->disposals_count }}</div><div class="text-muted small">Disposals</div></div>
                        <div><div class="fw-700">{{ $buyer->created_at?->format('d-m-Y') }}</div><div class="text-muted small">Added</div></div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="table-card text-center py-5 text-muted">
                <i class="bi bi-person-vcard fs-1 d-block mb-2 opacity-25"></i>
                <div class="fw-500 mb-1">No disposal buyers found</div>
                <small class="d-block mb-3">Create buyers, recyclers or donation recipients before completing sale, recycle or donation disposals.</small>
                <a href="{{ route('admin.disposal-buyers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add First Buyer</a>
            </div>
        </div>
    @endforelse
</div>

@if($buyers->hasPages())
    <div class="mt-3">{{ $buyers->links() }}</div>
@endif
@endsection
