@extends('layouts.app')
@section('title', 'Edit — ' . $facility->name)

@section('content')
<div class="page-header">
    <a href="{{ route('admin.facilities.show', $facility) }}" class="back-link"><i class="bi bi-arrow-left"></i> {{ $facility->name }}</a>
    <h4>Edit Facility</h4>
    <p>Update details for <strong>{{ $facility->display_name }}</strong>.</p>
</div>

<form action="{{ route('admin.facilities.update', $facility) }}" method="POST">
@csrf @method('PUT')
<div class="row g-4">

    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-building"></i></span>
                Facility Details
            </div>
            <div class="form-card-body">
                @include('admin.facilities._form', ['facility' => $facility])
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-slate"><i class="bi bi-clock-history"></i></span>
                Record Info
            </div>
            <div class="form-card-body">
                <div style="font-size:.82rem;color:#64748b;line-height:2">
                    <div><span style="font-weight:600;color:#334155">Created:</span>
                        {{ $facility->created_at->format('d-m-Y') }}
                    </div>
                    <div><span style="font-weight:600;color:#334155">Work Locations:</span>
                        {{ $facility->locations->count() }} total
                    </div>
                    <div><span style="font-weight:600;color:#334155">Status:</span>
                        <span class="badge bg-{{ $facility->status === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($facility->status) }}
                        </span>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('admin.facilities.show', $facility) }}"
                       class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-eye me-1"></i>View Facility & Locations
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('admin.facilities.show', $facility) }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Save Changes
    </button>
</div>
</form>
@endsection
