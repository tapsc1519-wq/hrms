@extends('layouts.app')

@section('title', 'Supplier Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="page-title mb-0">Supplier Performance Report</h4></div>
    <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-danger btn-sm">
        <i class="bi bi-file-pdf me-1"></i>Export PDF
    </a>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr><th>#</th><th>Supplier</th><th>Contact</th><th>City</th><th>Assets Supplied</th><th>Purchase Orders</th><th>Total Spend</th><th>Rating</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($suppliers as $i => $v)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="fw-500">{{ $v->name }}</td>
                    <td>{{ $v->contact_person ?? '—' }}</td>
                    <td>{{ $v->city ?? '—' }}</td>
                    <td>{{ $v->assets_count }}</td>
                    <td>{{ $v->purchase_orders_count }}</td>
                    <td class="fw-500">{!! $v->purchase_orders_sum_total_amount ? '&#8377;' . number_format($v->purchase_orders_sum_total_amount, 2) : '—' !!}</td>
                    <td>
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star{{ $i <= round($v->rating) ? '-fill' : '' }} text-warning" style="font-size:.7rem"></i>
                        @endfor
                    </td>
                    <td><span class="badge bg-{{ $v->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($v->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-600">
                <tr>
                    <td colspan="4">Total</td>
                    <td>{{ $suppliers->sum('assets_count') }}</td>
                    <td>{{ $suppliers->sum('purchase_orders_count') }}</td>
                    <td>&#8377;{{ number_format($suppliers->sum('purchase_orders_sum_total_amount'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
