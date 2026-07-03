@extends('layouts.app')

@section('title', 'Add Employee Profile')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.employees.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Employees</a>
    <div class="d-flex align-items-center justify-content-between gap-3">
        <div>
            <h4>Add Employee Profile</h4>
            <p>Create the employee HR profile and portal login together.</p>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <div class="d-flex gap-3">
        <i class="bi bi-signpost-split fs-5"></i>
        <div>
            <div class="fw-semibold">Recommended flow for internal users</div>
            <div class="small">Use this page for employees and organization admins. Enter identity, portal role, department, shift, and employment details here; the login account is created automatically when you save.</div>
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
