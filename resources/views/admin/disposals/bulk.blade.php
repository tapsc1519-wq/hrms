@extends('layouts.app')

@section('title', 'Bulk Disposal')

@push('styles')
<style>
    .barcode-box {
        background: #f8fafc;
        border: 1.5px dashed #cbd5e1;
        border-radius: 14px;
        padding: 1rem;
    }
    .asset-select-table {
        max-height: 420px;
        overflow-y: auto;
    }
    .asset-select-row {
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        gap: .75rem;
        padding: .7rem 0;
    }
    .asset-select-row:last-child { border-bottom: 0; }
    .asset-select-row.disabled {
        opacity: .56;
    }
    .asset-select-name {
        color: #0f172a;
        font-size: .84rem;
        font-weight: 750;
        line-height: 1.25;
    }
    .asset-select-meta {
        color: #64748b;
        font-size: .72rem;
        line-height: 1.35;
    }
</style>
@endpush

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('admin.disposals.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to Disposal</a>
        <h4>Bulk Disposal</h4>
        <p>Select multiple assets or scan/paste multiple barcodes to create disposal requests together.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.disposals.bulk.store') }}">
    @csrf
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="form-card mb-4">
                <div class="form-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="icon-wrap icon-red"><i class="bi bi-upc-scan"></i></span>
                        <div>
                            <h5 class="mb-1">Barcode / Asset Selection</h5>
                            <p class="text-muted small mb-0">Use either checkbox selection, barcode input, or both.</p>
                        </div>
                    </div>
                </div>
                <div class="form-card-body">
                    <div class="barcode-box mb-3">
                        <label class="form-label">Scan or Paste Barcodes / Asset Tags</label>
                        <textarea name="asset_identifiers" class="form-control" rows="6" placeholder="Scan one barcode per line, or paste asset tags separated by comma/new line.">{{ old('asset_identifiers') }}</textarea>
                        <div class="text-muted small mt-2">
                            Supported identifiers: asset tag or serial number. Example: <code>ASSET-001</code>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <div>
                            <h6 class="fw-bold mb-0" style="font-size:.88rem">Available Assets</h6>
                            <div class="text-muted small">Assets already disposed/lost are excluded. Assets with open disposal are disabled.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="selectAvailableAssets">
                            <i class="bi bi-check2-square me-1"></i> Select Available
                        </button>
                    </div>

                    <div class="asset-select-table">
                        @forelse($assets as $asset)
                            @php $disabled = (bool) $asset->activeDisposal; @endphp
                            <label class="asset-select-row {{ $disabled ? 'disabled' : '' }}">
                                <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}" class="form-check-input bulk-asset-check" @disabled($disabled) @checked(in_array($asset->id, old('asset_ids', [])))>
                                <span class="flex-grow-1 min-w-0">
                                    <span class="asset-select-name d-block">{{ $asset->asset_tag }} - {{ $asset->name }}</span>
                                    <span class="asset-select-meta d-block">
                                        {{ $asset->category?->name ?? 'Uncategorised' }}
                                        &middot; {{ ucfirst($asset->status) }}
                                        @if($asset->serial_number)
                                            &middot; Serial: {{ $asset->serial_number }}
                                        @endif
                                        @if($asset->activeAssignment)
                                            &middot; Assigned to {{ $asset->activeAssignment->user?->name }}
                                        @endif
                                    </span>
                                </span>
                                @if($disabled)
                                    <span class="badge bg-warning text-dark">Open Disposal</span>
                                @endif
                            </label>
                        @empty
                            <div class="text-center text-muted py-4">No disposable assets found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="form-card sticky-top" style="top:86px">
                <div class="form-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="icon-wrap icon-slate"><i class="bi bi-card-checklist"></i></span>
                        <div>
                            <h5 class="mb-1">Common Disposal Details</h5>
                            <p class="text-muted small mb-0">These values apply to every selected asset.</p>
                        </div>
                    </div>
                </div>
                <div class="form-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Method <span class="req">*</span></label>
                            <select name="method" class="form-select" required>
                                @foreach(['scrap','sell','donate','recycle','return_to_supplier','destroy'] as $method)
                                    <option value="{{ $method }}" @selected(old('method') === $method)>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Requested Date <span class="req">*</span></label>
                            <input type="date" name="requested_date" value="{{ old('requested_date', now()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Value</label>
                            <input type="number" step="0.01" min="0" name="expected_value" value="{{ old('expected_value') }}" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Saved Buyer / Recipient</label>
                            <select name="disposal_buyer_id" class="form-select">
                                <option value="">Select when known</option>
                                @foreach($buyers as $buyer)
                                    <option value="{{ $buyer->id }}" @selected(old('disposal_buyer_id') == $buyer->id)>
                                        {{ $buyer->name }} - {{ $buyer->type_label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">One-time Buyer / Recipient</label>
                            <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason <span class="req">*</span></label>
                            <textarea name="reason" rows="5" class="form-control" required placeholder="Reason for bulk disposal.">{{ old('reason') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ route('admin.disposals.index') }}" class="btn btn-light">Cancel</a>
                    <button class="btn btn-primary"><i class="bi bi-recycle me-1"></i> Create Bulk Disposal</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.getElementById('selectAvailableAssets')?.addEventListener('click', function () {
    document.querySelectorAll('.bulk-asset-check:not(:disabled)').forEach(function (checkbox) {
        checkbox.checked = true;
    });
});
</script>
@endpush
