@extends('layouts.app')
@section('title', 'Request an Asset')

@section('content')
<div class="page-header">
    <a href="{{ route('staff.requests.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> My Requests</a>
    <h4>Request an Asset</h4>
    <p>Submit a request by asset category, request type, and required quantity.</p>
</div>

<form action="{{ route('staff.requests.store') }}" method="POST">
@csrf
<div class="row g-4">
    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-clipboard-plus"></i></span>
                Request Information
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Request Type <span class="req">*</span></label>
                        <select name="request_type" class="form-select @error('request_type') is-invalid @enderror" required>
                            <option value="new_asset" {{ old('request_type', 'new_asset') === 'new_asset' ? 'selected' : '' }}>New Asset</option>
                            <option value="replacement" {{ old('request_type') === 'replacement' ? 'selected' : '' }}>Replacement</option>
                            <option value="additional" {{ old('request_type') === 'additional' ? 'selected' : '' }}>Additional Asset</option>
                            <option value="temporary" {{ old('request_type') === 'temporary' ? 'selected' : '' }}>Temporary Use</option>
                        </select>
                        @error('request_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Quantity <span class="req">*</span></label>
                        <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity', 1) }}" min="1" max="100" required>
                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Asset Category <span class="req">*</span></label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Choose a category</option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected':'' }}>
                                {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-chat-square-text"></i></span>
                Reason & Justification
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Priority <span class="req">*</span></label>
                        <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                            <option value="low" {{ old('priority') == 'low' ? 'selected':'' }}>Low</option>
                            <option value="normal" {{ old('priority','normal') == 'normal' ? 'selected':'' }}>Normal</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected':'' }}>High</option>
                            <option value="urgent" {{ old('priority') == 'urgent' ? 'selected':'' }}>Urgent</option>
                        </select>
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Required By Date</label>
                        <input type="date" name="required_date" class="form-control"
                               value="{{ old('required_date') }}"
                               min="{{ today()->addDay()->format('Y-m-d') }}">
                        <div class="form-text">Leave blank if flexible.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Reason / Justification <span class="req">*</span></label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                                  rows="4" required
                                  placeholder="Explain why you need this asset, whether it is new/replacement/temporary, and any specification preferences.">{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-info-circle"></i></span>
                How It Works
            </div>
            <div class="form-card-body">
                <ol class="ps-3 mb-0" style="font-size:.83rem;color:#64748b;line-height:2">
                    <li>Select request type, category, and quantity</li>
                    <li>Admin reviews and approves or rejects</li>
                    <li>If approved, Admin assigns available assets</li>
                    <li>Your assigned assets appear in My Assets</li>
                </ol>
                <div class="mt-3 p-2 rounded-3" style="background:#eff6ff;border:1px solid #bfdbfe;font-size:.78rem;color:#1d4ed8">
                    <i class="bi bi-lightbulb me-1"></i>
                    Admin will choose the actual asset during fulfillment.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('staff.requests.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-send"></i> Submit Request
    </button>
</div>
</form>
@endsection
