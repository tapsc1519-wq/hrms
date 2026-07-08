@extends('layouts.app')

@section('title', 'Add Partner')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-plus-circle me-2 text-primary"></i>Add Partner</h4>
        <p>Create a Niyantron partner who can be linked with product subscriptions.</p>
    </div>
    <a href="{{ route('super-admin.partners.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Partners
    </a>
</div>

<form method="POST" action="{{ route('super-admin.partners.store') }}" class="table-card">
    @include('super-admin.partners._form', ['partner' => new \App\Models\Partner])
</form>
@endsection
