@extends('layouts.app')
@section('title', 'My Tasks')

@section('content')
<div class="page-header">
    <h4>My Tasks</h4>
    <p>View assigned work, update progress, and keep your manager informed.</p>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-auto"><div class="rounded-3 px-4 py-2" style="background:#eff6ff;border:1.5px solid #bfdbfe"><strong>{{ $counts['open'] }}</strong> <span class="text-muted ms-1">Open</span></div></div>
    <div class="col-auto"><div class="rounded-3 px-4 py-2" style="background:#ecfeff;border:1.5px solid #a5f3fc"><strong>{{ $counts['review'] }}</strong> <span class="text-muted ms-1">In Review</span></div></div>
    <div class="col-auto"><div class="rounded-3 px-4 py-2" style="background:#fef2f2;border:1.5px solid #fecaca"><strong>{{ $counts['overdue'] }}</strong> <span class="text-muted ms-1">Overdue</span></div></div>
</div>

<div class="form-card mb-3" style="padding:0">
    <div class="px-4 py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\Task::STATUSES as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    @foreach(\App\Models\Task::PRIORITIES as $priority)
                    <option value="{{ $priority }}" {{ request('priority') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="due" class="form-select form-select-sm">
                    <option value="">Due Date</option>
                    <option value="today" {{ request('due') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="overdue" {{ request('due') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('staff.tasks.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="form-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead><tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0"><th class="px-4 py-3">Task</th><th>Priority</th><th>Status</th><th>Due</th><th>Created By</th><th></th></tr></thead>
            <tbody>
                @forelse($tasks as $task)
                <tr>
                    <td class="px-4"><div class="fw-semibold" style="color:#334155">{{ $task->title }}</div>@if($task->related_module)<small class="text-muted">{{ $task->related_module }}</small>@endif</td>
                    <td><span class="badge bg-{{ $task->priority_badge }}">{{ ucfirst($task->priority) }}</span></td>
                    <td><span class="badge bg-{{ $task->status_badge }}">{{ $task->status_label }}</span></td>
                    <td class="{{ $task->is_overdue ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $task->due_at ? $task->due_at->format('d M Y H:i') : 'No due date' }}</td>
                    <td>{{ $task->creator?->name ?? 'System' }}</td>
                    <td class="text-end pe-4"><a href="{{ route('staff.tasks.show', $task) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-5"><i class="bi bi-list-task fs-1 d-block mb-2 opacity-25"></i><div class="text-muted">No tasks assigned to you.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tasks->hasPages())<div class="px-4 py-3 border-top">{{ $tasks->links() }}</div>@endif
</div>
@endsection
