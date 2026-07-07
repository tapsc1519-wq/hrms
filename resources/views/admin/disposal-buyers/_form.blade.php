@csrf
<div class="row g-4">
    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="icon-wrap icon-teal"><i class="bi bi-person-vcard"></i></span>
                    <div>
                        <h5 class="mb-1">Buyer Details</h5>
                        <p class="text-muted small mb-0">Create a reusable buyer, recycler, auction winner or donation recipient for disposal completion.</p>
                    </div>
                </div>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $buyer->name) }}" class="form-control" required>
                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach(['active','inactive','blacklisted'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $buyer->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Buyer Type <span class="req">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach(['employee' => 'Employee Buyer', 'external_buyer' => 'External Buyer', 'vendor_recycler' => 'Vendor / Recycler', 'auction_buyer' => 'Auction Buyer', 'donation_recipient' => 'Donation Recipient'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $buyer->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $buyer->contact_person) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $buyer->email) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $buyer->phone) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">GST / Tax / Registration No.</label>
                        <input type="text" name="tax_number" value="{{ old('tax_number', $buyer->tax_number) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3" class="form-control">{{ old('address', $buyer->address) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $buyer->notes) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <a href="{{ route('admin.disposal-buyers.index') }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Buyer</button>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header"><h5 class="mb-0">Where This Is Used</h5></div>
            <div class="form-card-body small text-muted">
                <div class="mb-3"><strong class="text-dark">Sale / Auction</strong><br>Select the buyer and record recovered amount and payment status.</div>
                <div class="mb-3"><strong class="text-dark">Recycle / Scrap</strong><br>Select a recycler or scrap buyer and record certificate details.</div>
                <div><strong class="text-dark">Donation</strong><br>Select the recipient and capture handover reference for audit history.</div>
            </div>
        </div>
    </div>
</div>
