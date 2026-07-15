@csrf
@if($task->exists)
    @method('PUT')
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-list-task"></i></span>
                Task Details
            </div>
            <div class="form-card-body">
                <div class="mb-3">
                    <label class="form-label">Task Title</label>
                    <input type="text" name="title" value="{{ old('title', $task->title) }}" class="form-control @error('title') is-invalid @enderror" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="7" class="form-control @error('description') is-invalid @enderror" placeholder="Add clear instructions, expected output, and any useful context.">{{ old('description', $task->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Related Module</label>
                    <input type="text" name="related_module" value="{{ old('related_module', $task->related_module) }}" class="form-control @error('related_module') is-invalid @enderror" placeholder="Optional: AMC, SAM, HRMS, Disposal">
                    @error('related_module')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-sliders"></i></span>
                Assignment
            </div>
            <div class="form-card-body">
                <div class="mb-3">
                    <label class="form-label">Assign To</label>
                    <select name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror">
                        <option value="">Unassigned</option>
                        @foreach($assignees as $assignee)
                            <option value="{{ $assignee->id }}" {{ (string) old('assigned_to', $task->assigned_to) === (string) $assignee->id ? 'selected' : '' }}>
                                {{ $assignee->name }} ({{ ucwords(str_replace('_', ' ', $assignee->role)) }})
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select @error('priority') is-invalid @enderror">
                        @foreach(\App\Models\Task::PRIORITIES as $priority)
                            <option value="{{ $priority }}" {{ old('priority', $task->priority ?: 'medium') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach(\App\Models\Task::STATUSES as $status)
                            <option value="{{ $status }}" {{ old('status', $task->status ?: 'assigned') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Due Date & Time</label>
                    <input type="datetime-local" name="due_at" value="{{ old('due_at', optional($task->due_at)->format('Y-m-d\TH:i')) }}" class="form-control @error('due_at') is-invalid @enderror">
                    @error('due_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>{{ $task->exists ? 'Save Changes' : 'Create Task' }}
                    </button>
                    <a href="{{ $task->exists ? route('admin.tasks.show', $task) : route('admin.tasks.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</div>
