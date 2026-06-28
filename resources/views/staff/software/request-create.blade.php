@extends('layouts.app')
@section('title', 'Request Software')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4>Request Software</h4>
        <p>Tell your IT team what you need and how it supports your work.</p>
    </div>
    <a href="{{ route('staff.software-requests.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Requests
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-xl-8">
        <form method="POST" action="{{ route('staff.software-requests.store') }}">
            @csrf
            <div class="table-card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-display text-primary"></i>
                        <h6 class="mb-0">Software Needed</h6>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Software <span class="text-danger">*</span></label>
                        <select name="software_id" class="form-select @error('software_id') is-invalid @enderror" required>
                            <option value="">Choose from the software catalog</option>
                            @foreach($software as $item)
                                <option value="{{ $item->id }}" @selected(old('software_id') == $item->id)>
                                    {{ $item->name }}{{ $item->vendor ? ' - '.$item->vendor : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('software_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">If the software is not listed, ask IT to add it through a support ticket.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Urgency <span class="text-danger">*</span></label>
                            <select name="urgency" class="form-select @error('urgency') is-invalid @enderror" required>
                                <option value="low" @selected(old('urgency') === 'low')>Low - no deadline</option>
                                <option value="normal" @selected(old('urgency', 'normal') === 'normal')>Normal - standard work need</option>
                                <option value="high" @selected(old('urgency') === 'high')>High - work is affected</option>
                                <option value="critical" @selected(old('urgency') === 'critical')>Critical - work is blocked</option>
                            </select>
                            @error('urgency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Needed By</label>
                            <input type="date" name="needed_by" value="{{ old('needed_by') }}" min="{{ today()->toDateString() }}"
                                   class="form-control @error('needed_by') is-invalid @enderror">
                            @error('needed_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-chat-left-text text-primary"></i>
                        <h6 class="mb-0">Business Reason</h6>
                    </div>
                    <label class="form-label">How will you use this software? <span class="text-danger">*</span></label>
                    <textarea name="business_justification" rows="5" required maxlength="2000"
                              class="form-control @error('business_justification') is-invalid @enderror"
                              placeholder="Example: I need this software to edit customer product images for the July catalog. Without it, the design work cannot be completed.">{{ old('business_justification') }}</textarea>
                    @error('business_justification')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Mention the task, project, or customer work it supports. Minimum 20 characters.</div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('staff.software-requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Request</button>
            </div>
        </form>
    </div>
</div>
@endsection
