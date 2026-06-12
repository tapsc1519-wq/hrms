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
    <h2>Maintenance Report</h2>
    <div class="muted">
        Generated on {{ now()->format('d-m-Y') }} | {{ $summary['total'] }} records |
        Total cost &#8377;{{ number_format((float) $summary['total_cost'], 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset</th>
                <th>Type</th>
                <th>Scheduled</th>
                <th>Completed</th>
                <th>Technician</th>
                <th class="right">Labor</th>
                <th class="right">Parts</th>
                <th class="right">Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $record->asset?->name ?? '-' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $record->type)) }}</td>
                    <td>{{ $record->scheduled_date?->format('d-m-Y') ?? '-' }}</td>
                    <td>{{ $record->completed_date?->format('d-m-Y') ?? '-' }}</td>
                    <td>{{ $record->technician_name ?? '-' }}</td>
                    <td class="right">{!! $record->labor_cost > 0 ? '&#8377;' . number_format((float) $record->labor_cost, 2) : '-' !!}</td>
                    <td class="right">{!! $record->parts_cost > 0 ? '&#8377;' . number_format((float) $record->parts_cost, 2) : '-' !!}</td>
                    <td class="right">{!! $record->total_cost > 0 ? '&#8377;' . number_format((float) $record->total_cost, 2) : '-' !!}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $record->status)) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6">Total</th>
                <th class="right">&#8377;{{ number_format((float) $records->sum('labor_cost'), 2) }}</th>
                <th class="right">&#8377;{{ number_format((float) $records->sum('parts_cost'), 2) }}</th>
                <th class="right">&#8377;{{ number_format((float) $records->sum('total_cost'), 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
