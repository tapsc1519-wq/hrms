@extends('layouts.app')

@section('title', 'Edit Employee Profile')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.employees.show', $employee) }}" class="back-link"><i class="bi bi-arrow-left"></i> Employee Profile</a>
    <div class="d-flex align-items-center justify-content-between gap-3">
        <div>
            <h4>Edit {{ $employee->user->name }}</h4>
            <p>Update employment, reporting, and work location details.</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.employees.update', $employee) }}">
    @csrf
    @method('PUT')
    @include('admin.employees._form', ['employee' => $employee])

    <div class="form-card">
        <div class="form-actions">
            <a href="{{ route('admin.employees.show', $employee) }}" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn btn-primary btn-save">
                <i class="bi bi-check2-circle"></i> Update Profile
            </button>
        </div>
    </div>
</form>
@endsection
