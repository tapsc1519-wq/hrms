@extends('layouts.app')
@section('title', 'Edit Task')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.tasks.show', $task) }}" class="back-link"><i class="bi bi-arrow-left"></i> Task Details</a>
    <h4>Edit Task</h4>
    <p>Update assignment, priority, due date, or task instructions.</p>
</div>

<form action="{{ route('admin.tasks.update', $task) }}" method="POST">
    @include('admin.tasks._form')
</form>
@endsection
