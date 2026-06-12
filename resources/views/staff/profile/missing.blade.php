@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="page-header">
    <h4>My Profile</h4>
    <p>Your HR profile is not available yet.</p>
</div>

<div class="form-card">
    <div class="form-card-body text-center py-5">
        <div class="icon-wrap icon-amber mx-auto mb-3" style="width:54px;height:54px">
            <i class="bi bi-person-exclamation fs-4"></i>
        </div>
        <h5 class="fw-bold">Profile pending</h5>
        <p class="text-muted mb-0">Please contact HR/Admin to create your employee profile.</p>
    </div>
</div>
@endsection
