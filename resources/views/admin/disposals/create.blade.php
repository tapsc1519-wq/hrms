@extends('layouts.app')

@section('title', 'New Disposal')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('admin.disposals.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to Disposal</a>
        <h4>New Disposal Request</h4>
        <p>Create a disposal workflow for an asset that should be retired, sold, recycled or destroyed.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.disposals.store') }}">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="icon-wrap icon-red"><i class="bi bi-recycle"></i></span>
                        <div>
                            <h5 class="mb-1">Disposal Details</h5>
                            <p class="text-muted small mb-0">Select the asset and describe why disposal is required.</p>
                        </div>
                    </div>
                </div>
                <div class="form-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Asset <span class="req">*</span></label>
                            <select name="asset_id" class="form-select" required>
                                <option value="">Select Asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" @selected(old('asset_id', $selectedAsset?->id) == $asset->id)>
                                        {{ $asset->asset_tag }} - {{ $asset->name }} ({{ ucfirst($asset->status) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
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
                            <label class="form-label">Expected Recovery Value</label>
                            <input type="number" step="0.01" min="0" name="expected_value" value="{{ old('expected_value') }}" class="form-control" placeholder="0.00">
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
                            <div class="text-muted small mt-1">Use Disposal Buyers to maintain reusable buyers, recyclers and donation recipients.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">One-time Buyer / Recipient Name</label>
                            <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason <span class="req">*</span></label>
                            <textarea name="reason" rows="5" class="form-control" required placeholder="Example: End of life, repair cost is higher than current value, damaged beyond repair.">{{ old('reason') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ route('admin.disposals.index') }}" class="btn btn-light">Cancel</a>
                    <button class="btn btn-primary"><i class="bi bi-send me-1"></i> Submit Disposal Request</button>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-card">
                <div class="form-card-header">
                    <h5 class="mb-0">Workflow</h5>
                </div>
                <div class="form-card-body">
                    <div class="small text-muted">
                        <div class="mb-3"><strong class="text-dark">1. Request</strong><br>Create the disposal request with reason and expected value.</div>
                        <div class="mb-3"><strong class="text-dark">2. Approve</strong><br>Admin reviews and approves or rejects the request.</div>
                        <div><strong class="text-dark">3. Complete</strong><br>Enter disposal date, recovered amount and certificate details. Asset status becomes disposed.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
