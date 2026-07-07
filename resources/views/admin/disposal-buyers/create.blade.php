@extends('layouts.app')

@section('title', 'Add Disposal Buyer')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.disposal-buyers.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to Buyers</a>
    <h4>Add Disposal Buyer</h4>
    <p>Add a buyer, recycler, auction winner or donation recipient for disposal workflows.</p>
</div>

<form method="POST" action="{{ route('admin.disposal-buyers.store') }}">
    @include('admin.disposal-buyers._form')
</form>
@endsection
