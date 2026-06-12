@extends('layouts.app')

@section('title', 'Add Employee Profile')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.employees.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Employees</a>
    <div class="d-flex align-items-center justify-content-between gap-3">
        <div>
            <h4>Add Employee Profile</h4>
            <p>Create HR master data for an existing admin or staff login.</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.employees.store') }}">
    @csrf
    @include('admin.employees._form')

    <div class="form-card">
        <div class="form-actions">
            <a href="{{ route('admin.employees.index') }}" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn btn-primary btn-save">
                <i class="bi bi-check2-circle"></i> Save Profile
            </button>
        </div>
    </div>
</form>
@endsection
