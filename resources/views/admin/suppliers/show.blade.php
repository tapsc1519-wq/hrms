@extends('layouts.app')
@section('title', $supplier->name)

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.suppliers.index') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Suppliers
        </a>
        <div class="d-flex align-items-center gap-3 mt-1">
            @if($supplier->logo)
                <img src="{{ asset('storage/' . $supplier->logo) }}"
                     style="width:52px;height:52px;border-radius:13px;object-fit:cover;flex-shrink:0">
            @else
                <div style="width:52px;height:52px;border-radius:13px;flex-shrink:0;
                            background:linear-gradient(135deg,#3b82f6,#6366f1);
                            display:flex;align-items:center;justify-content:center;
                            color:#fff;font-size:1.5rem;font-weight:700">
                    {{ strtoupper(substr($supplier->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h4 class="mb-0">{{ $supplier->name }}</h4>
                <p class="mb-0">
                    @if($supplier->code)
                        <code style="font-size:.78rem;color:#64748b">{{ $supplier->code }}</code> ·
                    @endif
                    <span class="badge bg-{{ $supplier->status === 'active' ? 'success' : ($supplier->status === 'blacklisted' ? 'danger' : 'secondary') }}">
                        {{ ucfirst($supplier->status) }}
                    </span>
                    @if($supplier->rating)
                        <span class="ms-2" style="font-size:.8rem;color:#f59e0b">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star{{ $i <= round($supplier->rating) ? '-fill' : '' }}"></i>
                            @endfor
                            {{ $supplier->rating }}/5
                        </span>
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}"
              onsubmit="return confirm('Delete {{ addslashes($supplier->name) }}? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-blue h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-box-seam-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $supplier->assets->count() }}</div>
                        <div class="stat-label">Assets Supplied</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-green h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $supplier->purchaseOrders->count() }}</div>
                        <div class="stat-label">Purchase Orders</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-purple h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-receipt-cutoff"></i></div>
                    <div>
                        <div class="stat-number">{{ $supplier->invoices->count() }}</div>
                        <div class="stat-label">Invoices</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-orange h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <div class="stat-number" style="font-size:1.4rem">
                            &#8377;{{ number_format($supplier->invoices->where('status','paid')->sum('total_amount'), 0) }}
                        </div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Left: Contact & Details --}}
    <div class="col-lg-4">

        {{-- Contact Info --}}
        <div class="form-card mb-0">
            <div class="form-card-header">
                <div class="icon-wrap icon-blue"><i class="bi bi-person-lines-fill"></i></div>
                Contact Information
            </div>
            <div class="form-card-body">
                <dl class="row g-0 mb-0" style="font-size:.855rem">
                    @if($supplier->contact_person)
                    <dt class="col-5 text-muted py-2 border-bottom" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Contact</dt>
                    <dd class="col-7 py-2 border-bottom fw-500">{{ $supplier->contact_person }}</dd>
                    @endif
                    @if($supplier->contact_phone)
                    <dt class="col-5 text-muted py-2 border-bottom" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Phone</dt>
                    <dd class="col-7 py-2 border-bottom fw-500">{{ $supplier->contact_phone }}</dd>
                    @endif
                    @if($supplier->email)
                    <dt class="col-5 text-muted py-2 border-bottom" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Email</dt>
                    <dd class="col-7 py-2 border-bottom fw-500 text-truncate">
                        <a href="mailto:{{ $supplier->email }}" class="text-primary">{{ $supplier->email }}</a>
                    </dd>
                    @endif
                    @if($supplier->phone)
                    <dt class="col-5 text-muted py-2 border-bottom" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Main Phone</dt>
                    <dd class="col-7 py-2 border-bottom fw-500">{{ $supplier->phone }}</dd>
                    @endif
                    @if($supplier->website)
                    <dt class="col-5 text-muted py-2 border-bottom" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Website</dt>
                    <dd class="col-7 py-2 border-bottom fw-500">
                        <a href="{{ $supplier->website }}" target="_blank" class="text-primary text-truncate d-block">
                            <i class="bi bi-box-arrow-up-right me-1"></i>{{ $supplier->website }}
                        </a>
                    </dd>
                    @endif
                    @if($supplier->address || $supplier->city)
                    <dt class="col-5 text-muted py-2 border-bottom" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Address</dt>
                    <dd class="col-7 py-2 border-bottom fw-500">
                        {{ $supplier->address }}
                        @if($supplier->city)<br>{{ $supplier->city }}@if($supplier->country), {{ $supplier->country }}@endif @endif
                    </dd>
                    @endif
                    @if($supplier->tax_number)
                    <dt class="col-5 text-muted py-2 border-bottom" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Tax No.</dt>
                    <dd class="col-7 py-2 border-bottom fw-500"><code style="font-size:.82rem">{{ $supplier->tax_number }}</code></dd>
                    @endif
                    @if($supplier->bank_details)
                    <dt class="col-5 text-muted py-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Bank</dt>
                    <dd class="col-7 py-2 fw-500" style="white-space:pre-line;font-size:.82rem">{{ $supplier->bank_details }}</dd>
                    @endif
                </dl>
                @if($supplier->notes)
                <div class="mt-3 pt-3 border-top" style="font-size:.82rem;color:#475569">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:.4rem">Notes</div>
                    {{ $supplier->notes }}
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Right: POs & Assets --}}
    <div class="col-lg-8">

        {{-- Purchase Orders --}}
        <div class="table-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="bi bi-file-earmark-text me-2 text-primary"></i>Purchase Orders</div>
                <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">View All</a>
            </div>
            <div class="table-responsive">
                @if($supplier->purchaseOrders->isEmpty())
                    <div class="text-center py-4 text-muted" style="font-size:.875rem">No purchase orders yet.</div>
                @else
                    <table class="table table-hover mb-0" style="font-size:.855rem">
                        <thead style="background:#f8fafc;border-bottom:2px solid #e9ecef">
                            <tr>
                                <th class="px-4 py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">PO Number</th>
                                <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Date</th>
                                <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Amount</th>
                                <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Status</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($supplier->purchaseOrders->take(8) as $po)
                            <tr style="border-bottom:1px solid #f1f5f9">
                                <td class="px-4 py-3">
                                    <code style="font-size:.82rem;color:#3b82f6">{{ $po->po_number }}</code>
                                </td>
                                <td class="py-3 text-muted" style="font-size:.82rem">{{ $po->order_date?->format('d-m-Y') ?? '—' }}</td>
                                <td class="py-3 fw-600">&#8377;{{ number_format($po->total_amount, 2) }}</td>
                                <td class="py-3">
                                    <span class="badge bg-{{ $po->status_badge ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$po->status)) }}</span>
                                </td>
                                <td class="py-3 pe-3">
                                    <a href="{{ route('admin.purchase-orders.show', $po) }}" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Assets --}}
        <div class="table-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="bi bi-box-seam me-2 text-primary"></i>Assets from this Supplier</div>
                <a href="{{ route('admin.assets.index') }}" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">View All</a>
            </div>
            <div class="table-responsive">
                @if($supplier->assets->isEmpty())
                    <div class="text-center py-4 text-muted" style="font-size:.875rem">No assets linked to this supplier.</div>
                @else
                    <table class="table table-hover mb-0" style="font-size:.855rem">
                        <thead style="background:#f8fafc;border-bottom:2px solid #e9ecef">
                            <tr>
                                <th class="px-4 py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Asset</th>
                                <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Tag</th>
                                <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Status</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($supplier->assets->take(8) as $asset)
                            <tr style="border-bottom:1px solid #f1f5f9">
                                <td class="px-4 py-3">
                                    <div class="fw-600" style="color:#1e293b">{{ $asset->name }}</div>
                                    <div class="text-muted" style="font-size:.72rem">{{ $asset->brand }} {{ $asset->model }}</div>
                                </td>
                                <td class="py-3">
                                    <code style="font-size:.78rem;background:#f1f5f9;padding:2px 6px;border-radius:5px;color:#334155">{{ $asset->asset_tag }}</code>
                                </td>
                                <td class="py-3">
                                    <span class="badge bg-{{ $asset->status_badge ?? 'secondary' }}">{{ ucfirst($asset->status) }}</span>
                                </td>
                                <td class="py-3 pe-3">
                                    <a href="{{ route('admin.assets.show', $asset) }}" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
