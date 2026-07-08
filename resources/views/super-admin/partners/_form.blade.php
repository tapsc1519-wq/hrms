@csrf
@if($partner->exists)
    @method('PUT')
@endif

<div class="card-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Partner Name</label>
            <input type="text" name="name" value="{{ old('name', $partner->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Company Name</label>
            <input type="text" name="company_name" value="{{ old('company_name', $partner->company_name) }}" class="form-control @error('company_name') is-invalid @enderror">
            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Contact Person</label>
            <input type="text" name="contact_person" value="{{ old('contact_person', $partner->contact_person) }}" class="form-control @error('contact_person') is-invalid @enderror">
            @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $partner->email) }}" class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $partner->phone) }}" class="form-control @error('phone') is-invalid @enderror">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Partner Type</label>
            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" {{ old('type', $partner->type ?: 'individual') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $partner->status ?: 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Default Commission</label>
            <div class="input-group">
                <input type="number" name="default_commission_percent" step="0.01" min="0" max="100" value="{{ old('default_commission_percent', $partner->default_commission_percent ?? 0) }}" class="form-control @error('default_commission_percent') is-invalid @enderror" required>
                <span class="input-group-text">%</span>
                @error('default_commission_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Payout Method</label>
            <input type="text" name="payout_method" value="{{ old('payout_method', $partner->payout_method) }}" class="form-control @error('payout_method') is-invalid @enderror" placeholder="Bank transfer, UPI, etc.">
            @error('payout_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label class="form-label">Payout Details</label>
            <input type="text" name="payout_details" value="{{ old('payout_details', $partner->payout_details) }}" class="form-control @error('payout_details') is-invalid @enderror" placeholder="Internal payout reference details">
            @error('payout_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Internal notes about onboarding, agreement or service area">{{ old('notes', $partner->notes) }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
<div class="card-footer bg-white text-end">
    <button class="btn btn-primary btn-sm">
        <i class="bi bi-check2 me-1"></i>{{ $partner->exists ? 'Save Partner' : 'Create Partner' }}
    </button>
</div>
