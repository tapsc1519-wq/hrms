@extends('layouts.app')
@section('title', 'Supplier Dashboard')

@section('content')

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>{{ $supplier->name }}</h4>
        <p>Supplier Portal &mdash; {{ now()->format('l, d-m-Y') }}</p>
    </div>
    <span class="badge fs-6 mt-1 bg-{{ $supplier->status === 'active' ? 'success' : ($supplier->status === 'blacklisted' ? 'danger' : 'secondary') }}">
        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;vertical-align:middle"></i>
        {{ ucfirst($supplier->status) }}
    </span>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card-gradient grad-blue h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total POs</div>
                        <div class="stat-number mt-1">{{ $stats['total_pos'] }}</div>
                        <div class="stat-sub"><i class="bi bi-file-earmark-text me-1"></i>Purchase orders received</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card-gradient grad-orange h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Pending POs</div>
                        <div class="stat-number mt-1">{{ $stats['pending_pos'] }}</div>
                        <div class="stat-sub"><i class="bi bi-hourglass-split me-1"></i>Awaiting processing</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card-gradient grad-red h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Pending Invoices</div>
                        <div class="stat-number mt-1">{{ $stats['pending_invoices'] }}</div>
                        <div class="stat-sub"><i class="bi bi-receipt me-1"></i>Awaiting payment</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card-gradient grad-green h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Revenue (Paid)</div>
                        <div class="stat-number mt-1" style="font-size:1.5rem">&#8377;{{ number_format($stats['total_revenue'], 0) }}</div>
                        <div class="stat-sub"><i class="bi bi-check-circle me-1"></i>Total paid to date</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Profile + Recent POs --}}
<div class="row g-4">

    {{-- Company Profile --}}
    <div class="col-md-4">
        <div class="form-card h-100">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-building"></i></span>
                Company Profile
            </div>
            <div class="form-card-body">

                {{-- Logo / Avatar --}}
                <div class="text-center mb-4">
                    @if($supplier->logo)
                        <img src="{{ asset('storage/' . $supplier->logo) }}"
                             class="rounded-3 shadow-sm"
                             style="width:80px;height:80px;object-fit:cover">
                    @else
                        <div class="mx-auto rounded-3 d-flex align-items-center justify-content-center fw-700 text-white"
                             style="width:80px;height:80px;font-size:2rem;background:linear-gradient(135deg,#3b82f6,#6366f1)">
                            {{ strtoupper(substr($supplier->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="mt-2 fw-700" style="color:#0f172a">{{ $supplier->name }}</div>
                    @if($supplier->code)
                    <div class="text-muted" style="font-size:.78rem">{{ $supplier->code }}</div>
                    @endif
                    <div class="mt-1">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star{{ $i <= round($supplier->rating) ? '-fill' : '' }} text-warning" style="font-size:.85rem"></i>
                        @endfor
                        <span class="text-muted ms-1" style="font-size:.78rem">{{ $supplier->rating }}/5</span>
                    </div>
                </div>

                <dl style="font-size:.82rem;margin:0;display:grid;row-gap:.75rem">
                    @if($supplier->contact_person)
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700"><i class="bi bi-person me-1"></i>Contact Person</dt>
                        <dd style="margin:0;font-weight:600;color:#334155">{{ $supplier->contact_person }}</dd>
                    </div>
                    @endif
                    @if($supplier->email)
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700"><i class="bi bi-envelope me-1"></i>Email</dt>
                        <dd style="margin:0;color:#334155">{{ $supplier->email }}</dd>
                    </div>
                    @endif
                    @if($supplier->phone)
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700"><i class="bi bi-telephone me-1"></i>Phone</dt>
                        <dd style="margin:0;color:#334155">{{ $supplier->phone }}</dd>
                    </div>
                    @endif
                    @if($supplier->city)
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700"><i class="bi bi-geo-alt me-1"></i>Location</dt>
                        <dd style="margin:0;color:#334155">{{ $supplier->city }}@if($supplier->country), {{ $supplier->country }}@endif</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Recent Purchase Orders --}}
    <div class="col-md-8">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-600"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Recent Purchase Orders</span>
                <a href="{{ route('supplier.purchase-orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
                    <thead class="table-light">
                        <tr>
                            <th>PO Number</th>
                            <th>Organization</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $po)
                        <tr>
                            <td>
                                <a href="{{ route('supplier.purchase-orders.show', $po) }}"
                                   class="text-decoration-none fw-700"
                                   style="font-family:monospace;color:#3b82f6">
                                    {{ $po->po_number }}
                                </a>
                            </td>
                            <td>
                                <span class="fw-500" style="color:#334155">{{ $po->organization->name }}</span>
                            </td>
                            <td><small class="text-muted">{{ $po->order_date->format('d-m-Y') }}</small></td>
                            <td class="fw-600">&#8377;{{ number_format($po->total_amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $po->status_badge }}">
                                    {{ ucwords(str_replace('_', ' ', $po->status)) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-text fs-1 d-block mb-2 opacity-25"></i>
                                No purchase orders yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
