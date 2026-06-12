@extends('layouts.app')
@section('title', 'Assign Asset')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.assignments.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Assignments</a>
    <h4>Assign Asset</h4>
    <p>Assign an available asset to a staff member or department.</p>
</div>

<form action="{{ route('admin.assignments.store') }}" method="POST">
@csrf
<div class="row g-4">

    {{-- LEFT --}}
    <div class="col-lg-8">

        {{-- Asset & Assignee --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-box-seam"></i></span>
                Asset & Assignee
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Asset <span class="req">*</span></label>
                        <select name="asset_id" class="form-select @error('asset_id') is-invalid @enderror" required>
                            <option value="">— Select Asset —</option>
                            @foreach($assets as $a)
                            <option value="{{ $a->id }}" {{ (old('asset_id', request('asset_id')) == $a->id) ? 'selected':'' }}>
                                {{ $a->name }}
                                @if($a->asset_tag) ({{ $a->asset_tag }}) @endif
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Only <strong>available</strong> assets are listed.</div>
                        @error('asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assign To (User)</label>
                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                            <option value="">— Select User —</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected':'' }}>
                                {{ $u->name }}
                                @if($u->job_title) — {{ $u->job_title }} @elseif($u->role) ({{ $u->role }}) @endif
                            </option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Or Assign to Department</label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                            <option value="">— Select Department —</option>
                            @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected':'' }}>
                                {{ $d->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Assignment Details --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-calendar-check"></i></span>
                Assignment Details
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Assigned Date <span class="req">*</span></label>
                        <input type="date" name="assigned_date" class="form-control @error('assigned_date') is-invalid @enderror"
                               value="{{ old('assigned_date', today()->format('Y-m-d')) }}" required>
                        @error('assigned_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Expected Return Date</label>
                        <input type="date" name="expected_return_date" class="form-control @error('expected_return_date') is-invalid @enderror"
                               value="{{ old('expected_return_date') }}">
                        <div class="form-text">Leave blank for indefinite assignments.</div>
                        @error('expected_return_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Condition (Outgoing) <span class="req">*</span></label>
                        <select name="condition_out" class="form-select @error('condition_out') is-invalid @enderror" required>
                            @foreach(['excellent','good','fair','poor'] as $c)
                            <option value="{{ $c }}" {{ old('condition_out', 'good') == $c ? 'selected':'' }}>
                                {{ ucfirst($c) }}
                            </option>
                            @endforeach
                        </select>
                        @error('condition_out')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Purpose</label>
                        <input type="text" name="purpose" class="form-control"
                               value="{{ old('purpose') }}"
                               placeholder="e.g. Daily work, Field operations, Temporary assignment">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Any additional remarks or conditions…">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT --}}
    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-info-circle"></i></span>
                Quick Guide
            </div>
            <div class="form-card-body">
                <ul class="list-unstyled mb-0" style="font-size:.83rem;color:#64748b;line-height:1.8">
                    <li><i class="bi bi-check-circle text-success me-2"></i>Select <strong>User</strong> for individual assignments</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Select <strong>Department</strong> for shared assets</li>
                    <li><i class="bi bi-info-circle text-primary me-2"></i>Asset status will update to <strong>Assigned</strong></li>
                    <li><i class="bi bi-info-circle text-primary me-2"></i>Only available assets are shown</li>
                    <li><i class="bi bi-calendar text-warning me-2"></i>Set return date for temporary loans</li>
                </ul>
            </div>
        </div>
    </div>

</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('admin.assignments.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-success btn-save">
        <i class="bi bi-person-check"></i> Assign Asset
    </button>
</div>
</form>
@endsection
