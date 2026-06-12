@extends('layouts.app')

@section('title', 'Apply Leave')

@section('content')
<div class="page-header">
    <a href="{{ route('staff.leaves.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> My Leaves</a>
    <h4>Apply Leave</h4>
    <p>Submit a leave request for HR/Admin approval.</p>
</div>

<form method="POST" action="{{ route('staff.leaves.store') }}">
    @csrf
    <div class="form-card">
        <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-calendar-plus"></i></span> Leave Details</div>
        <div class="form-card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Leave Type <span class="req">*</span></label>
                    <select name="leave_type" class="form-select" required>
                        <option value="">Select leave type</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ rtrim(rtrim($type->annual_quota, '0'), '.') }} days/year)</option>
                        @endforeach
                    </select>
                    @if($leaveTypes->isEmpty())
                        <div class="form-text text-danger">No active leave types configured. Please contact HR/Admin.</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">From Date <span class="req">*</span></label>
                    <input type="date" name="from_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">To Date <span class="req">*</span></label>
                    <input type="date" name="to_date" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Reason <span class="req">*</span></label>
                    <textarea name="reason" class="form-control" rows="4" required placeholder="Explain why you need leave"></textarea>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('staff.leaves.index') }}" class="btn-cancel">Cancel</a>
            <button class="btn btn-primary btn-save"><i class="bi bi-send me-1"></i> Submit Request</button>
        </div>
    </div>
</form>
@endsection
