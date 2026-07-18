@extends('layouts.app')
@section('title', 'Production Readiness')

@section('content')
@include('partials._production_readiness', [
    'title' => 'OpsBridge Production Readiness',
    'subtitle' => 'A launch checklist for organization setup, modules, operational queues and live-server configuration.',
])
@endsection
