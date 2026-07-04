@extends('layouts.app')

@section('title', $contract->exists ? 'Edit AMC Contract' : 'Add AMC Contract')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.asset-amc-contracts.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> AMC Contracts</a>
    <h4>{{ $contract->exists ? 'Edit AMC Contract' : 'Add AMC Contract' }}</h4>
    <p>Define vendor coverage, SLA, dates and assets included under the contract.</p>
</div>

<form method="POST" action="{{ $contract->exists ? route('admin.asset-amc-contracts.update', $contract) : route('admin.asset-amc-contracts.store') }}">
    @csrf
    @if($contract->exists)
        @method('PUT')
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="form-card">
                <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-shield-check"></i></span>Contract Details</div>
                <div class="form-card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title <span class="req">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $contract->title) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contract Number</label>
                            <input type="text" name="contract_number" class="form-control" value="{{ old('contract_number', $contract->contract_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select">
                                <option value="">Select vendor</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('vendor_id', $contract->vendor_id) == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Coverage Type</label>
                            <select name="coverage_type" class="form-select" required>
                                @foreach(['service_only' => 'Service only', 'parts_and_service' => 'Parts and service', 'onsite' => 'On-site support', 'carry_in' => 'Carry-in support', 'warranty_hybrid' => 'Warranty/AMC hybrid'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('coverage_type', $contract->coverage_type ?: 'service_only') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach(['active','expired','cancelled'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $contract->status ?: 'active') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Response SLA Hours</label>
                            <input type="number" min="1" name="response_sla_hours" class="form-control" value="{{ old('response_sla_hours', $contract->response_sla_hours) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Resolution SLA Hours</label>
                            <input type="number" min="1" name="resolution_sla_hours" class="form-control" value="{{ old('resolution_sla_hours', $contract->resolution_sla_hours) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-check border rounded-3 p-3 h-100">
                                <input type="checkbox" name="parts_included" value="1" class="form-check-input me-2" @checked(old('parts_included', $contract->parts_included))>
                                <span class="fw-bold">Parts Included</span>
                                <div class="text-muted small mt-1">Parts cost is covered under AMC.</div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check border rounded-3 p-3 h-100">
                                <input type="checkbox" name="onsite_support" value="1" class="form-check-input me-2" @checked(old('onsite_support', $contract->onsite_support))>
                                <span class="fw-bold">On-site Support</span>
                                <div class="text-muted small mt-1">Vendor visits the organization location.</div>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="4">{{ old('notes', $contract->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="form-card">
                <div class="form-card-header"><span class="icon-wrap icon-teal"><i class="bi bi-boxes"></i></span>Covered Assets</div>
                <div class="form-card-body" style="max-height:620px;overflow:auto">
                    @php $selectedAssets = collect(old('asset_ids', $contract->assets->pluck('id')->all()))->map(fn($id) => (int) $id)->all(); @endphp
                    @forelse($assets as $asset)
                        <label class="form-check border-bottom py-2">
                            <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}" class="form-check-input me-2" @checked(in_array($asset->id, $selectedAssets, true))>
                            <span class="fw-bold">{{ $asset->name }}</span>
                            <span class="text-muted small d-block ms-4">{{ $asset->asset_tag }} · {{ $asset->category?->name ?? 'Uncategorised' }}</span>
                        </label>
                    @empty
                        <div class="text-muted text-center py-4">No active assets available.</div>
                    @endforelse
                </div>
                <div class="form-actions">
                    <a href="{{ route('admin.asset-amc-contracts.index') }}" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-save"><i class="bi bi-check2-circle"></i>Save Contract</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
