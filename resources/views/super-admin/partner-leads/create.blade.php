@extends('layouts.app')

@section('title', 'Add Partner Lead')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-plus-circle me-2 text-primary"></i>Add Partner Lead</h4>
        <p>Capture a partner opportunity before converting it into an organization.</p>
    </div>
    <a href="{{ route('super-admin.partner-leads.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Leads
    </a>
</div>

<form method="POST" action="{{ route('super-admin.partner-leads.store') }}" class="table-card">
    @include('super-admin.partner-leads._form')
</form>
@endsection
