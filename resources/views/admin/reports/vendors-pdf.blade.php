<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 10px; }
        h2 { margin: 0 0 4px; font-size: 18px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; color: #334155; font-size: 9px; text-transform: uppercase; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Supplier Performance Report</h2>
    <div class="muted">Generated on {{ now()->format('d-m-Y') }} | {{ $suppliers->count() }} suppliers</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Supplier</th>
                <th>Contact</th>
                <th>City</th>
                <th class="right">Assets</th>
                <th class="right">POs</th>
                <th class="right">Total Spend</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($suppliers as $index => $supplier)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $supplier->name }}</td>
                    <td>{{ $supplier->contact_person ?? '-' }}</td>
                    <td>{{ $supplier->city ?? '-' }}</td>
                    <td class="right">{{ $supplier->assets_count }}</td>
                    <td class="right">{{ $supplier->purchase_orders_count }}</td>
                    <td class="right">{!! $supplier->purchase_orders_sum_total_amount ? '&#8377;' . number_format((float) $supplier->purchase_orders_sum_total_amount, 2) : '-' !!}</td>
                    <td>{{ ucfirst($supplier->status) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">Total</th>
                <th class="right">{{ $suppliers->sum('assets_count') }}</th>
                <th class="right">{{ $suppliers->sum('purchase_orders_count') }}</th>
                <th class="right">&#8377;{{ number_format((float) $suppliers->sum('purchase_orders_sum_total_amount'), 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
