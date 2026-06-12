@extends('layouts.app')

@section('title', 'Depreciation Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="page-title mb-0">Asset Depreciation Report</h4></div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.875rem">
            <thead class="table-light">
                <tr><th>#</th><th>Asset</th><th>Category</th><th>Purchase Price</th><th>Purchase Date</th><th>Method</th><th>Useful Life</th><th>Salvage Value</th><th>Current Value</th><th>Depreciated</th></tr>
            </thead>
            <tbody>
                @foreach($assets as $i => $asset)
                @php
                    $dep = $asset->depreciationRecord;
                    $currentVal = $asset->current_value;
                    $depreciated = $asset->purchase_price - $currentVal;
                    $pct = $asset->purchase_price > 0 ? ($depreciated / $asset->purchase_price * 100) : 0;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <a href="{{ route('admin.assets.show', $asset) }}" class="text-decoration-none fw-500">{{ $asset->name }}</a>
                        <br><small class="text-muted">{{ $asset->asset_tag }}</small>
                    </td>
                    <td><small>{{ $asset->category?->name ?? '—' }}</small></td>
                    <td>&#8377;{{ number_format($asset->purchase_price, 2) }}</td>
                    <td>{{ $asset->purchase_date?->format('d-m-Y') ?? '—' }}</td>
                    <td><small>{{ ucwords(str_replace('_', ' ', $dep->method)) }}</small></td>
                    <td>{{ $dep->useful_life_years }} yrs</td>
                    <td>&#8377;{{ number_format($dep->salvage_value, 2) }}</td>
                    <td class="fw-500 text-success">&#8377;{{ number_format($currentVal, 2) }}</td>
                    <td>
                        <div class="progress" style="height:6px;width:80px">
                            <div class="progress-bar bg-danger" style="width:{{ min($pct, 100) }}%"></div>
                        </div>
                        <small class="text-danger">{{ number_format($pct, 0) }}%</small>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
