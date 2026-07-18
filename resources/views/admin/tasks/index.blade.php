@extends('layouts.app')
@section('title', 'Tasks')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Tasks</h4>
        <p>Create, assign, and monitor work across the organization.</p>
    </div>
    <a href="{{ route('admin.tasks.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Task
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3 mb-4">
    @foreach(['assigned' => 'Assigned', 'in_progress' => 'In Progress', 'blocked' => 'Blocked', 'review' => 'Review', 'overdue' => 'Overdue'] as $key => $label)
    <div class="col-auto">
        <div class="rounded-3 px-4 py-2 d-flex align-items-center gap-2" style="background:#f8fafc;border:1.5px solid #e2e8f0">
            <span style="font-size:1.25rem;font-weight:800;color:#0f172a">{{ $counts[$key] ?? 0 }}</span>
            <span style="font-size:.8rem;font-weight:600;color:#64748b">{{ $label }}</span>
        </div>
    </div>
    @endforeach
</div>

<div class="form-card mb-3" style="padding:0">
    <div class="px-4 py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search task title or description" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\Task::STATUSES as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    @foreach(\App\Models\Task::PRIORITIES as $priority)
                    <option value="{{ $priority }}" {{ request('priority') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="assigned_to" class="form-select form-select-sm">
                    <option value="">All Assignees</option>
                    @foreach($assignees as $assignee)
                    <option value="{{ $assignee->id }}" {{ (string) request('assigned_to') === (string) $assignee->id ? 'selected' : '' }}>{{ $assignee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <select name="due" class="form-select form-select-sm">
                    <option value="">Due</option>
                    <option value="today" {{ request('due') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="overdue" {{ request('due') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('admin.tasks.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="form-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <th class="px-4 py-3">Task</th>
                    <th>Assignee</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th>Created By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr>
                    <td class="px-4">
                        <div class="fw-semibold" style="color:#334155">{{ $task->title }}</div>
                        @if($task->related_module)<small class="text-muted">{{ $task->related_module }}</small>@endif
                    </td>
                    <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                    <td><span class="badge bg-{{ $task->priority_badge }}">{{ ucfirst($task->priority) }}</span></td>
                    <td><span class="badge bg-{{ $task->status_badge }}">{{ $task->status_label }}</span></td>
                    <td class="{{ $task->is_overdue ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $task->due_at ? $task->due_at->format('d M Y H:i') : 'No due date' }}</td>
                    <td>{{ $task->creator?->name ?? 'System' }}</td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.tasks.show', $task) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        @include('partials._empty_state', [
                            'icon' => 'bi-list-task',
                            'title' => request()->hasAny(['search', 'status', 'priority', 'assigned_to', 'due']) ? 'No tasks match these filters' : 'Create your first task',
                            'message' => request()->hasAny(['search', 'status', 'priority', 'assigned_to', 'due'])
                                ? 'Try widening the filters or clear them to see all organization tasks.'
                                : 'Use tasks for setup work, internal follow-ups, approval tracking and daily operations.',
                            'actionRoute' => route('admin.tasks.create'),
                            'actionLabel' => 'Create Task',
                            'secondaryRoute' => request()->hasAny(['search', 'status', 'priority', 'assigned_to', 'due']) ? route('admin.tasks.index') : null,
                        ])
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tasks->hasPages())
    <div class="px-4 py-3 border-top">{{ $tasks->links() }}</div>
    @endif
</div>
@endsection
