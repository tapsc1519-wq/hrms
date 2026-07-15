@extends('layouts.app')
@section('title', 'Create Task')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.tasks.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Tasks</a>
    <h4>Create Task</h4>
    <p>Assign work to an employee or admin and track it through completion.</p>
</div>

<form action="{{ route('admin.tasks.store') }}" method="POST">
    @include('admin.tasks._form')
</form>
@endsection
