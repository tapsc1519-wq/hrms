@extends('layouts.app')

@section('title', 'Edit Disposal Buyer')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.disposal-buyers.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to Buyers</a>
    <h4>Edit Disposal Buyer</h4>
    <p>Update buyer details used in asset disposal records.</p>
</div>

<form method="POST" action="{{ route('admin.disposal-buyers.update', $buyer) }}">
    @method('PUT')
    @include('admin.disposal-buyers._form')
</form>
@endsection
