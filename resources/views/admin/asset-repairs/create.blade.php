@extends('layouts.app')

@section('title', 'Create Repair Job')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.asset-repairs.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Asset Repairs</a>
    <h4>Create Repair Job</h4>
    <p>Create a repair workflow for assigned, available, warranty, AMC, vendor or market repair.</p>
</div>

<form method="POST" action="{{ route('admin.asset-repairs.store') }}">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-wrench-adjustable"></i></span>Repair Details</div>
                <div class="form-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Asset <span class="req">*</span></label>
                            <select name="asset_id" class="form-select @error('asset_id') is-invalid @enderror" required>
                                <option value="">Select asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" @selected(old('asset_id', $selectedAsset) == $asset->id) @disabled($asset->activeRepair)>
                                        {{ $asset->name }} · {{ $asset->asset_tag }}{{ $asset->activeAssignment?->user ? ' · Assigned to '.$asset->activeAssignment->user->name : '' }}{{ $asset->activeRepair ? ' · Repair already open' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Repair Type <span class="req">*</span></label>
                            <select name="repair_type" class="form-select" required>
                                @foreach(['internal' => 'Internal IT', 'amc' => 'AMC Vendor', 'vendor' => 'Onboarded Vendor', 'market' => 'Market Repair', 'warranty' => 'Warranty'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('repair_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Priority <span class="req">*</span></label>
                            <select name="priority" class="form-select" required>
                                @foreach(['low','medium','high','critical'] as $priority)
                                    <option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>{{ ucfirst($priority) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="req">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach(['under_review','approved','assigned_to_it','assigned_to_vendor','sent_for_repair','repair_in_progress'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', 'under_review') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Requested Date</label>
                            <input type="date" name="requested_date" class="form-control" value="{{ old('requested_date', today()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected Return</label>
                            <input type="date" name="expected_return_date" class="form-control" value="{{ old('expected_return_date') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Issue Summary <span class="req">*</span></label>
                            <textarea name="issue_summary" class="form-control" rows="4" required placeholder="Describe the fault, symptoms, damage, or repair instruction.">{{ old('issue_summary') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-card">
                <div class="form-card-header"><span class="icon-wrap icon-teal"><i class="bi bi-building"></i></span>Vendor / AMC</div>
                <div class="form-card-body">
                    <div class="mb-3">
                        <label class="form-label">Onboarded Vendor</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">Internal or market repair</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('vendor_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">AMC Contract</label>
                        <select name="amc_contract_id" class="form-select">
                            <option value="">No AMC selected</option>
                            @foreach($amcContracts as $contract)
                                <option value="{{ $contract->id }}" @selected(old('amc_contract_id') == $contract->id)>{{ $contract->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Market Vendor Name</label>
                        <input type="text" name="market_vendor_name" class="form-control" value="{{ old('market_vendor_name') }}" placeholder="Required for market repair">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Market Vendor Phone</label>
                        <input type="text" name="market_vendor_phone" class="form-control" value="{{ old('market_vendor_phone') }}">
                    </div>
                    <div class="form-actions">
                        <a href="{{ route('admin.asset-repairs.index') }}" class="btn-cancel">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-save"><i class="bi bi-check2-circle"></i>Create Job</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
