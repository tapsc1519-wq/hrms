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
    <h2>Asset Report</h2>
    <div class="muted">Generated on {{ now()->format('d-m-Y') }} | {{ $assets->count() }} assets</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset Tag</th>
                <th>Name</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Assigned To</th>
                <th class="right">Purchase Price</th>
                <th>Status</th>
                <th>Warranty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $index => $asset)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $asset->asset_tag }}</td>
                    <td>{{ $asset->name }}</td>
                    <td>{{ $asset->category?->name ?? '-' }}</td>
                    <td>{{ $asset->supplier?->name ?? '-' }}</td>
                    <td>{{ $asset->activeAssignment?->user?->name ?? '-' }}</td>
                    <td class="right">{!! $asset->purchase_price ? '&#8377;' . number_format((float) $asset->purchase_price, 2) : '-' !!}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $asset->status)) }}</td>
                    <td>{{ $asset->warranty_expiry_date?->format('d-m-Y') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6">Total Purchase Value</th>
                <th class="right">&#8377;{{ number_format((float) $assets->sum('purchase_price'), 2) }}</th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
