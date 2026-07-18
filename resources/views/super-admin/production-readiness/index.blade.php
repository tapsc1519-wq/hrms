@extends('layouts.app')
@section('title', 'Launch Readiness')

@section('content')
@include('partials._production_readiness', [
    'title' => 'Niyantron Platform Launch Readiness',
    'subtitle' => 'A common-platform checklist for products, subscriptions, partners, billing attention and live-server configuration.',
])
@endsection
