@extends('layouts.app')

@section('title', 'Edit Partner Lead')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Partner Lead</h4>
        <p>Update opportunity details, stage and conversion readiness.</p>
    </div>
    <a href="{{ route('super-admin.partner-leads.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Leads
    </a>
</div>

<form method="POST" action="{{ route('super-admin.partner-leads.update', $lead) }}" class="table-card">
    @method('PUT')
    @include('super-admin.partner-leads._form')
</form>
@endsection
