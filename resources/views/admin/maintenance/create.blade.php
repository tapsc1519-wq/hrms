@extends('layouts.app')
@section('title', 'Log Maintenance')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.maintenance.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Maintenance</a>
    <h4>Log Maintenance Record</h4>
    <p>Schedule or record a maintenance activity for an asset.</p>
</div>

<form action="{{ route('admin.maintenance.store') }}" method="POST">
@csrf
<div class="row g-4">

    {{-- LEFT --}}
    <div class="col-lg-8">

        {{-- Asset & Type --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-box-seam"></i></span>
                Asset & Maintenance Type
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Asset <span class="req">*</span></label>
                        <select name="asset_id" class="form-select @error('asset_id') is-invalid @enderror" required>
                            <option value="">— Select Asset —</option>
                            @foreach($assets as $a)
                            <option value="{{ $a->id }}"
                                    {{ (old('asset_id', request('asset_id')) == $a->id) ? 'selected' : '' }}>
                                {{ $a->name }}
                                @if($a->asset_tag) ({{ $a->asset_tag }}) @endif
                            </option>
                            @endforeach
                        </select>
                        @error('asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Maintenance Type <span class="req">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="preventive"  {{ old('type') == 'preventive'  ? 'selected':'' }}>🛡 Preventive</option>
                            <option value="corrective"  {{ old('type') == 'corrective'  ? 'selected':'' }}>🔧 Corrective</option>
                            <option value="predictive"  {{ old('type') == 'predictive'  ? 'selected':'' }}>📊 Predictive</option>
                            <option value="emergency"   {{ old('type') == 'emergency'   ? 'selected':'' }}>🚨 Emergency</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="scheduled"   {{ old('status','scheduled')  == 'scheduled'   ? 'selected':'' }}>📅 Scheduled</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected':'' }}>⚙ In Progress</option>
                            <option value="completed"   {{ old('status') == 'completed'   ? 'selected':'' }}>✅ Completed</option>
                            <option value="cancelled"   {{ old('status') == 'cancelled'   ? 'selected':'' }}>✗ Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="form-control"
                               value="{{ old('scheduled_date') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Next Maintenance Date</label>
                        <input type="date" name="next_maintenance_date" class="form-control"
                               value="{{ old('next_maintenance_date') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description <span class="req">*</span></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="3" required
                                  placeholder="Describe the maintenance work to be performed or already done…">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Technician --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-purple"><i class="bi bi-person-gear"></i></span>
                Technician & Service Provider
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Supplier / Service Provider</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">— In-house / None —</option>
                            @foreach($suppliers as $v)
                            <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected':'' }}>
                                {{ $v->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Technician Name</label>
                        <input type="text" name="technician_name" class="form-control"
                               value="{{ old('technician_name') }}" placeholder="Full name of technician">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Technician Company</label>
                        <input type="text" name="technician_company" class="form-control"
                               value="{{ old('technician_company') }}" placeholder="Company name if external">
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT --}}
    <div class="col-lg-4">

        {{-- Costs --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-currency-exchange"></i></span>
                Cost Breakdown
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Labor Cost (&#8377;)</label>
                        <input type="number" name="labor_cost" class="form-control"
                               value="{{ old('labor_cost', 0) }}" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Parts / Materials Cost (&#8377;)</label>
                        <input type="number" name="parts_cost" class="form-control"
                               value="{{ old('parts_cost', 0) }}" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="col-12 pt-1">
                        <div class="rounded-3 bg-light px-3 py-2 d-flex justify-content-between align-items-center">
                            <span class="small text-muted fw-semibold">Total Cost</span>
                            <span class="fw-bold text-primary" id="totalCostDisplay">&#8377; 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-slate"><i class="bi bi-sticky"></i></span>
                Notes
            </div>
            <div class="form-card-body">
                <textarea name="notes" class="form-control" rows="4"
                          placeholder="Any additional notes…">{{ old('notes') }}</textarea>
            </div>
        </div>

    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('admin.maintenance.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-warning btn-save text-dark">
        <i class="bi bi-check-lg"></i> Save Record
    </button>
</div>
</form>
@endsection

@push('scripts')
<script>
function calcTotal() {
    const l = parseFloat(document.querySelector('[name=labor_cost]').value) || 0;
    const p = parseFloat(document.querySelector('[name=parts_cost]').value) || 0;
    document.getElementById('totalCostDisplay').textContent = '\u20B9 ' + (l + p).toLocaleString('en-IN', {minimumFractionDigits:2});
}
document.querySelectorAll('[name=labor_cost],[name=parts_cost]').forEach(el => el.addEventListener('input', calcTotal));
calcTotal();
</script>
@endpush
