@csrf

<div class="card-body">
    @if($lead->converted_organization_id)
        <div class="alert alert-success border-0 rounded-3">
            <i class="bi bi-check-circle-fill me-1"></i>This lead has been converted to organization #{{ $lead->converted_organization_id }}.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Partner</label>
            <select name="partner_id" id="leadPartnerSelect" class="form-select @error('partner_id') is-invalid @enderror" required>
                <option value="">Select Partner</option>
                @foreach($partners as $partner)
                    <option value="{{ $partner->id }}" data-commission="{{ $partner->default_commission_percent }}" {{ (int) old('partner_id', $lead->partner_id) === $partner->id ? 'selected' : '' }}>{{ $partner->display_name }}</option>
                @endforeach
            </select>
            @error('partner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Interested Product</label>
            <select name="product_id" class="form-select @error('product_id') is-invalid @enderror">
                <option value="">Default Product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ (int) old('product_id', $lead->product_id) === $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                @endforeach
            </select>
            @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Stage</label>
            <select name="stage" class="form-select @error('stage') is-invalid @enderror" required>
                @foreach($stages as $value => $label)
                    <option value="{{ $value }}" {{ old('stage', $lead->stage ?: 'new') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('stage')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Company Name</label>
            <input type="text" name="company_name" value="{{ old('company_name', $lead->company_name) }}" class="form-control @error('company_name') is-invalid @enderror" required>
            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Contact Person</label>
            <input type="text" name="contact_person" value="{{ old('contact_person', $lead->contact_person) }}" class="form-control @error('contact_person') is-invalid @enderror">
            @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $lead->email) }}" class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $lead->phone) }}" class="form-control @error('phone') is-invalid @enderror">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Expected Close Date</label>
            <input type="date" name="expected_close_date" value="{{ old('expected_close_date', $lead->expected_close_date?->toDateString()) }}" class="form-control @error('expected_close_date') is-invalid @enderror">
            @error('expected_close_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Expected Monthly Value</label>
            <div class="input-group">
                <span class="input-group-text">&#8377;</span>
                <input type="number" name="expected_monthly_value" step="0.01" min="0" value="{{ old('expected_monthly_value', $lead->expected_monthly_value ?? 0) }}" class="form-control @error('expected_monthly_value') is-invalid @enderror" required>
                @error('expected_monthly_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Commission</label>
            <div class="input-group">
                <input type="number" name="commission_percent" id="leadCommissionPercent" step="0.01" min="0" max="100" value="{{ old('commission_percent', $lead->commission_percent) }}" class="form-control @error('commission_percent') is-invalid @enderror" placeholder="Use partner default">
                <span class="input-group-text">%</span>
                @error('commission_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Conversation notes, requirements, proposal status or handover details">{{ old('notes', $lead->notes) }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
<div class="card-footer bg-white d-flex justify-content-between">
    <a href="{{ route('super-admin.partner-leads.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
    <button class="btn btn-primary btn-sm">
        <i class="bi bi-check2 me-1"></i>{{ $lead->exists ? 'Save Lead' : 'Create Lead' }}
    </button>
</div>

@push('scripts')
<script>
document.getElementById('leadPartnerSelect')?.addEventListener('change', function () {
    var selected = this.options[this.selectedIndex];
    var commissionInput = document.getElementById('leadCommissionPercent');
    if (!commissionInput || commissionInput.value) return;
    commissionInput.value = selected.dataset.commission || '';
});
</script>
@endpush
