<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::where('organization_id', $this->orgId())
            ->where('assigned_to', auth()->id())
            ->with(['creator'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->due === 'overdue') {
            $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
        } elseif ($request->due === 'today') {
            $query->whereDate('due_at', today());
        }

        $tasks = $query->paginate(20)->withQueryString();

        $counts = [
            'open' => Task::where('organization_id', $this->orgId())->where('assigned_to', auth()->id())->open()->count(),
            'review' => Task::where('organization_id', $this->orgId())->where('assigned_to', auth()->id())->where('status', 'review')->count(),
            'overdue' => Task::where('organization_id', $this->orgId())->where('assigned_to', auth()->id())->open()->whereNotNull('due_at')->where('due_at', '<', now())->count(),
        ];

        return view('staff.tasks.index', compact('tasks', 'counts'));
    }

    public function show(Task $task)
    {
        $this->authorizeTask($task);

        $task->load(['creator', 'assignee', 'comments.user']);

        return view('staff.tasks.show', compact('task'));
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::STAFF_STATUSES)],
            'comment' => ['nullable', 'string', 'max:3000'],
        ]);

        abort_if(in_array($task->status, ['completed', 'cancelled'], true), 403, 'This task is already closed.');

        $oldStatus = $task->status;
        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? ($task->completed_at ?? now()) : null,
        ]);

        $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $validated['comment'] ?? null,
            'old_status' => $oldStatus,
            'new_status' => $task->status,
        ]);

        return back()->with('success', 'Task updated.');
    }

    public function comment(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:3000'],
        ]);

        $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'Comment added.');
    }

    private function authorizeTask(Task $task): void
    {
        abort_if($task->organization_id !== $this->orgId() || $task->assigned_to !== auth()->id(), 403);
    }
}
