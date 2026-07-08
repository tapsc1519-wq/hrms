@extends('layouts.app')
@section('title', 'Add Lead')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-plus-circle me-2 text-primary"></i>Add Lead</h4>
        <p>Share a qualified opportunity with the Niyantron team.</p>
    </div>
    <a href="{{ route('partner.leads.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Leads</a>
</div>

<form method="POST" action="{{ route('partner.leads.store') }}" class="table-card">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Company Name</label><input type="text" name="company_name" value="{{ old('company_name') }}" class="form-control @error('company_name') is-invalid @enderror" required>@error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Contact Person</label><input type="text" name="contact_person" value="{{ old('contact_person') }}" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" value="{{ old('phone') }}" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Interested Product</label><select name="product_id" class="form-select"><option value="">Default Product</option>@foreach($products as $product)<option value="{{ $product->id }}" {{ (int) old('product_id') === $product->id ? 'selected' : '' }}>{{ $product->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Expected Monthly Value</label><div class="input-group"><span class="input-group-text">&#8377;</span><input type="number" name="expected_monthly_value" step="0.01" min="0" value="{{ old('expected_monthly_value', 0) }}" class="form-control @error('expected_monthly_value') is-invalid @enderror" required>@error('expected_monthly_value')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
            <div class="col-md-6"><label class="form-label">Expected Close Date</label><input type="date" name="expected_close_date" value="{{ old('expected_close_date') }}" class="form-control"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="4" class="form-control" placeholder="Requirements, current conversation, decision maker, timeline">{{ old('notes') }}</textarea></div>
        </div>
    </div>
    <div class="card-footer bg-white text-end"><button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i>Submit Lead</button></div>
</form>
@endsection
