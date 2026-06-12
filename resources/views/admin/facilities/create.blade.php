@extends('layouts.app')
@section('title', 'Add Facility')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.facilities.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Facilities</a>
    <h4>Add New Facility</h4>
    <p>A facility is a physical site (office, warehouse, branch) under your organisation.</p>
</div>

<form action="{{ route('admin.facilities.store') }}" method="POST">
@csrf
<div class="row g-4">

    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-building-add"></i></span>
                Facility Details
            </div>
            <div class="form-card-body">
                @include('admin.facilities._form', ['facility' => null])
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-lightbulb"></i></span>
                About Facilities
            </div>
            <div class="form-card-body">
                <ul class="list-unstyled mb-0" style="font-size:.83rem;color:#64748b;line-height:1.9">
                    <li><i class="bi bi-building text-primary me-2"></i>Represents a <strong>physical location</strong> of your organisation</li>
                    <li><i class="bi bi-diagram-3 text-success me-2"></i>Each facility can have multiple <strong>work locations</strong></li>
                    <li><i class="bi bi-geo-alt text-warning me-2"></i>Use <strong>State/Region</strong> to group facilities by geography</li>
                    <li><i class="bi bi-tags text-info me-2"></i>Use a short <strong>Code</strong> for quick identification</li>
                </ul>
            </div>
        </div>
    </div>

</div>

<div class="form-actions" style="border-radius:14px;border:1px solid #e2e8f0;margin-top:.5rem">
    <a href="{{ route('admin.facilities.index') }}" class="btn-cancel">Cancel</a>
    <button type="submit" class="btn btn-primary btn-save">
        <i class="bi bi-check-lg"></i> Create Facility
    </button>
</div>
</form>
@endsection
