<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::forOrganization($this->orgId())
            ->with(['assignee', 'creator'])
            ->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->due === 'overdue') {
            $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
        } elseif ($request->due === 'today') {
            $query->whereDate('due_at', today());
        }

        $tasks = $query->paginate(20)->withQueryString();
        $assignees = $this->assignees();

        $counts = [
            'assigned' => Task::forOrganization($this->orgId())->where('status', 'assigned')->count(),
            'in_progress' => Task::forOrganization($this->orgId())->where('status', 'in_progress')->count(),
            'blocked' => Task::forOrganization($this->orgId())->where('status', 'blocked')->count(),
            'review' => Task::forOrganization($this->orgId())->where('status', 'review')->count(),
            'overdue' => Task::forOrganization($this->orgId())->open()->whereNotNull('due_at')->where('due_at', '<', now())->count(),
        ];

        return view('admin.tasks.index', compact('tasks', 'assignees', 'counts'));
    }

    public function create()
    {
        $task = new Task(['priority' => 'medium', 'status' => 'assigned']);
        $assignees = $this->assignees();

        return view('admin.tasks.create', compact('task', 'assignees'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedTask($request);

        $task = Task::create($validated + [
            'organization_id' => $this->orgId(),
            'created_by' => auth()->id(),
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ]);

        $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => 'Task created.',
            'new_status' => $task->status,
        ]);

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $this->authorizeOrganization($task);

        $task->load(['assignee', 'creator', 'comments.user']);
        $assignees = $this->assignees();

        return view('admin.tasks.show', compact('task', 'assignees'));
    }

    public function edit(Task $task)
    {
        $this->authorizeOrganization($task);

        $assignees = $this->assignees();

        return view('admin.tasks.edit', compact('task', 'assignees'));
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeOrganization($task);

        $validated = $this->validatedTask($request);
        $oldStatus = $task->status;
        $validated['completed_at'] = $validated['status'] === 'completed' ? ($task->completed_at ?? now()) : null;

        $task->update($validated);

        if ($oldStatus !== $task->status) {
            $this->recordStatusChange($task, $oldStatus, $task->status);
        }

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $this->authorizeOrganization($task);
        $task->delete();

        return redirect()->route('admin.tasks.index')->with('success', 'Task deleted.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorizeOrganization($task);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::STATUSES)],
            'comment' => ['nullable', 'string', 'max:3000'],
        ]);

        $oldStatus = $task->status;
        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? ($task->completed_at ?? now()) : null,
        ]);

        if ($oldStatus !== $task->status || filled($validated['comment'] ?? null)) {
            $this->recordStatusChange($task, $oldStatus, $task->status, $validated['comment'] ?? null);
        }

        return back()->with('success', 'Task status updated.');
    }

    public function comment(Request $request, Task $task)
    {
        $this->authorizeOrganization($task);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:3000'],
        ]);

        $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'Comment added.');
    }

    private function validatedTask(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn($query) => $query->where('organization_id', $this->orgId())),
            ],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'status' => ['required', Rule::in(Task::STATUSES)],
            'due_at' => ['nullable', 'date'],
            'related_module' => ['nullable', 'string', 'max:80'],
        ]);
    }

    private function assignees()
    {
        return User::where('organization_id', $this->orgId())
            ->whereIn('role', ['admin', 'staff'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    private function authorizeOrganization(Task $task): void
    {
        abort_if($task->organization_id !== $this->orgId(), 403);
    }

    private function recordStatusChange(Task $task, ?string $oldStatus, ?string $newStatus, ?string $comment = null): void
    {
        $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $comment,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }
}
