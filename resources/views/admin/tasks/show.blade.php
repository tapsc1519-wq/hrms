@extends('layouts.app')
@section('title', $task->title)

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('admin.tasks.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Tasks</a>
        <h4>{{ $task->title }}</h4>
        <p>{{ $task->assignee ? 'Assigned to ' . $task->assignee->name : 'This task is currently unassigned.' }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.tasks.edit', $task) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <span class="badge fs-6 mt-1 bg-{{ $task->status_badge }}">{{ $task->status_label }}</span>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="form-card mb-3">
            <div class="form-card-header" style="justify-content:space-between">
                <div>
                    <span class="icon-wrap icon-blue"><i class="bi bi-card-text"></i></span>
                    Task Brief
                </div>
                <span class="badge bg-{{ $task->priority_badge }}">{{ ucfirst($task->priority) }}</span>
            </div>
            <div class="form-card-body">
                <div style="white-space:pre-line;color:#334155;font-size:.9rem;line-height:1.75">{{ $task->description ?: 'No description added.' }}</div>
            </div>
        </div>

        <div class="form-card mb-3">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-chat-left-text"></i></span>
                Activity & Comments
            </div>
            <div class="form-card-body">
                @forelse($task->comments as $comment)
                    <div class="d-flex gap-3 pb-3 mb-3 border-bottom">
                        <div class="flex-shrink-0" style="width:34px;height:34px;border-radius:50%;background:#eff6ff;color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-weight:700">
                            {{ strtoupper(substr($comment->user?->name ?? 'S', 0, 1)) }}
                        </div>
                        <div class="flex-grow-1">
                            <div style="font-size:.78rem;color:#94a3b8">
                                <strong style="color:#334155">{{ $comment->user?->name ?? 'System' }}</strong>
                                · {{ $comment->created_at->format('d-m-Y H:i') }}
                            </div>
                            @if($comment->has_status_change)
                                <div class="mt-1 mb-1" style="font-size:.78rem;color:#64748b">
                                    Status changed
                                    @if($comment->old_status) from <strong>{{ ucwords(str_replace('_', ' ', $comment->old_status)) }}</strong>@endif
                                    @if($comment->new_status) to <strong>{{ ucwords(str_replace('_', ' ', $comment->new_status)) }}</strong>@endif
                                </div>
                            @endif
                            @if($comment->comment)
                                <div style="white-space:pre-line;color:#334155;font-size:.875rem;line-height:1.6">{{ $comment->comment }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-muted text-center py-3">No activity yet.</div>
                @endforelse

                <form action="{{ route('admin.tasks.comments.store', $task) }}" method="POST">
                    @csrf
                    <label class="form-label">Add Comment</label>
                    <textarea name="comment" rows="3" class="form-control @error('comment') is-invalid @enderror" required></textarea>
                    @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="mt-3 text-end">
                        <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Add Comment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-toggles"></i></span>
                Update Status
            </div>
            <div class="form-card-body">
                <form action="{{ route('admin.tasks.status', $task) }}" method="POST">
                    @csrf @method('PATCH')
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select mb-3">
                        @foreach(\App\Models\Task::STATUSES as $status)
                            <option value="{{ $status }}" {{ $task->status === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">Status Note</label>
                    <textarea name="comment" rows="3" class="form-control mb-3" placeholder="Optional"></textarea>
                    <button type="submit" class="btn btn-warning text-dark w-100"><i class="bi bi-check-lg me-1"></i>Update Status</button>
                </form>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-slate"><i class="bi bi-info-circle"></i></span>
                Task Details
            </div>
            <div class="form-card-body">
                <dl style="font-size:.82rem;margin:0;display:grid;row-gap:.8rem">
                    <div><dt class="text-muted">Assignee</dt><dd class="mb-0 fw-semibold">{{ $task->assignee?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt class="text-muted">Created By</dt><dd class="mb-0">{{ $task->creator?->name ?? 'System' }}</dd></div>
                    <div><dt class="text-muted">Due Date</dt><dd class="mb-0 {{ $task->is_overdue ? 'text-danger fw-semibold' : '' }}">{{ $task->due_at ? $task->due_at->format('d-m-Y H:i') : 'No due date' }}</dd></div>
                    @if($task->related_module)<div><dt class="text-muted">Related Module</dt><dd class="mb-0">{{ $task->related_module }}</dd></div>@endif
                    <div><dt class="text-muted">Created</dt><dd class="mb-0">{{ $task->created_at->format('d-m-Y H:i') }}</dd></div>
                    @if($task->completed_at)<div><dt class="text-muted">Completed</dt><dd class="mb-0">{{ $task->completed_at->format('d-m-Y H:i') }}</dd></div>@endif
                </dl>
            </div>
        </div>

        <div class="form-card" style="border-color:#fecaca">
            <div class="form-card-body">
                <form action="{{ route('admin.tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Delete this task permanently?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash me-1"></i>Delete Task</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
