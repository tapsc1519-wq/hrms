{{-- Shared facility form fields --}}
<div class="row g-3">

    <div class="col-md-7">
        <label class="form-label">Facility Name <span class="req">*</span></label>
        <input type="text" name="name" required
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $facility?->name) }}"
               placeholder="e.g. Head Office, Cebu Branch, Davao Warehouse">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Code</label>
        <input type="text" name="code"
               class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $facility?->code) }}"
               placeholder="e.g. HQ-MNL">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-2">
        <label class="form-label">Status <span class="req">*</span></label>
        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
            <option value="active"   {{ old('status', $facility?->status ?? 'active') === 'active'   ? 'selected':'' }}>Active</option>
            <option value="inactive" {{ old('status', $facility?->status) === 'inactive' ? 'selected':'' }}>Inactive</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" rows="2"
                  class="form-control @error('address') is-invalid @enderror"
                  placeholder="Street / Building address">{{ old('address', $facility?->address) }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city"
               class="form-control @error('city') is-invalid @enderror"
               value="{{ old('city', $facility?->city) }}" placeholder="e.g. Manila">
        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">State / Region <span class="req">*</span></label>
        <input type="text" name="state"
               class="form-control @error('state') is-invalid @enderror"
               value="{{ old('state', $facility?->state) }}" placeholder="e.g. NCR, Cebu, Davao">
        <div class="form-text">Used to group facilities by location.</div>
        @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Country</label>
        <input type="text" name="country"
               class="form-control @error('country') is-invalid @enderror"
               value="{{ old('country', $facility?->country ?? 'Philippines') }}">
        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $facility?->phone) }}" placeholder="+63 2 8XXX XXXX">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $facility?->email) }}" placeholder="facility@company.com">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" rows="2"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Optional notes about this facility">{{ old('description', $facility?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

</div>
