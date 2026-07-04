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
                                <option value="" data-warranty="Select an asset to see warranty information.">Select asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}"
                                        data-warranty="{{ $asset->warranty_expiry_date ? ($asset->warranty_expiry_date->isPast() ? 'Warranty expired on '.$asset->warranty_expiry_date->format('d-m-Y') : 'Warranty valid until '.$asset->warranty_expiry_date->format('d-m-Y')) : 'Warranty date not set' }}"
                                        @selected(old('asset_id', $selectedAsset) == $asset->id)
                                        @disabled($asset->activeRepair)>
                                        {{ $asset->name }} - {{ $asset->asset_tag }}{{ $asset->activeAssignment?->user ? ' - Assigned to '.$asset->activeAssignment->user->name : '' }}{{ $asset->activeRepair ? ' - Repair already open' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Repair Type <span class="req">*</span></label>
                            <select name="repair_type" class="form-select repair-type-select" required>
                                @foreach(['internal' => 'Internal IT', 'amc' => 'AMC Contract', 'vendor' => 'Onboarded Vendor', 'market' => 'Market Repair', 'warranty' => 'Warranty Claim'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('repair_type', 'internal') === $value)>{{ $label }}</option>
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
                <div class="form-card-header"><span class="icon-wrap icon-teal"><i class="bi bi-building"></i></span>Repair Source</div>
                <div class="form-card-body">
                    <div class="alert alert-light border rounded-3 small repair-type-hint">
                        Select a repair type to see only the required source details.
                    </div>

                    <div class="mb-3 repair-source-panel" data-repair-source="vendor">
                        <label class="form-label">Vendor <span class="req">*</span></label>
                        <select name="vendor_id" class="form-select">
                            <option value="">Select vendor</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('vendor_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Only records added in Vendors will appear here.</div>
                    </div>

                    <div class="mb-3 repair-source-panel" data-repair-source="amc">
                        <label class="form-label">AMC Contract <span class="req">*</span></label>
                        <select name="amc_contract_id" class="form-select">
                            <option value="">Select AMC contract</option>
                            @foreach($amcContracts as $contract)
                                <option value="{{ $contract->id }}" @selected(old('amc_contract_id') == $contract->id)>{{ $contract->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="repair-source-panel" data-repair-source="market">
                        <div class="mb-3">
                            <label class="form-label">Market Vendor Name <span class="req">*</span></label>
                            <input type="text" name="market_vendor_name" class="form-control" value="{{ old('market_vendor_name') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Market Vendor Phone No.</label>
                            <input type="text" name="market_vendor_phone" class="form-control" value="{{ old('market_vendor_phone') }}">
                        </div>
                    </div>

                    <div class="repair-source-panel" data-repair-source="warranty">
                        <div class="alert alert-info border-0 rounded-3 small asset-warranty-note">
                            Select an asset to see warranty information.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warranty Provider <span class="req">*</span></label>
                            <select name="warranty_provider_type" class="form-select warranty-provider-select">
                                <option value="">Select warranty provider</option>
                                <option value="original_supplier" @selected(old('warranty_provider_type') === 'original_supplier')>Original Supplier</option>
                                <option value="manufacturer_service_center" @selected(old('warranty_provider_type') === 'manufacturer_service_center')>Manufacturer / Brand Service Center</option>
                                <option value="other" @selected(old('warranty_provider_type') === 'other')>Other Warranty Provider</option>
                            </select>
                        </div>
                        <div class="mb-3 warranty-other-provider">
                            <label class="form-label">Provider Name <span class="req">*</span></label>
                            <input type="text" name="warranty_provider_name" class="form-control" value="{{ old('warranty_provider_name') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Provider Phone No.</label>
                            <input type="text" name="warranty_provider_phone" class="form-control" value="{{ old('warranty_provider_phone') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warranty Claim / Ticket No.</label>
                            <input type="text" name="warranty_claim_number" class="form-control" value="{{ old('warranty_claim_number') }}">
                        </div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var repairTypeSelect = document.querySelector('.repair-type-select');
    var assetSelect = document.querySelector('select[name="asset_id"]');
    var warrantyProviderSelect = document.querySelector('.warranty-provider-select');

    function setPanelState(panel, active) {
        panel.style.display = active ? '' : 'none';
        panel.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.disabled = !active;
        });
    }

    function updateWarrantyNote() {
        var note = document.querySelector('.asset-warranty-note');
        if (!note || !assetSelect) return;
        var selected = assetSelect.options[assetSelect.selectedIndex];
        note.textContent = selected && selected.dataset.warranty ? selected.dataset.warranty : 'Select an asset to see warranty information.';
    }

    function updateWarrantyOther() {
        var wrapper = document.querySelector('.warranty-other-provider');
        if (!wrapper || !warrantyProviderSelect) return;
        var isOther = warrantyProviderSelect.value === 'other';
        wrapper.style.display = isOther ? '' : 'none';
        wrapper.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.disabled = !isOther;
        });
    }

    function updateRepairSourcePanels() {
        var type = repairTypeSelect ? repairTypeSelect.value : 'internal';
        document.querySelectorAll('.repair-source-panel').forEach(function (panel) {
            setPanelState(panel, panel.dataset.repairSource === type);
        });
        updateWarrantyOther();
        updateWarrantyNote();
    }

    if (repairTypeSelect) repairTypeSelect.addEventListener('change', updateRepairSourcePanels);
    if (assetSelect) assetSelect.addEventListener('change', updateWarrantyNote);
    if (warrantyProviderSelect) warrantyProviderSelect.addEventListener('change', updateWarrantyOther);
    updateRepairSourcePanels();
});
</script>
@endpush
