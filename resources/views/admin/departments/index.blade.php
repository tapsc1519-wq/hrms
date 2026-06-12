@extends('layouts.app')
@section('title', 'Departments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Departments</h4>
        <p>Manage organisational departments for permission scoping and reporting.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Add Department
    </button>
</div>

<div class="form-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <th class="px-4 py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Department</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Code</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Members</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Description</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Status</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                <tr>
                    <td class="px-4 fw-semibold">{{ $dept->name }}</td>
                    <td>
                        @if($dept->code)
                        <code class="px-2 py-1 rounded" style="background:#f1f5f9;color:#475569;font-size:.8rem">{{ $dept->code }}</code>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge rounded-pill" style="background:#e2e8f0;color:#334155">
                            {{ $dept->users_count ?? 0 }} users
                        </span>
                    </td>
                    <td><small class="text-muted">{{ Str::limit($dept->description, 55) ?: '—' }}</small></td>
                    <td>
                        <span class="badge bg-{{ $dept->status === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($dept->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#editModal{{ $dept->id }}"
                                    title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('admin.departments.destroy', $dept) }}" method="POST"
                                  onsubmit="return confirm('Delete {{ $dept->name }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                {{-- Edit Modal --}}
                <div class="modal fade" id="editModal{{ $dept->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('admin.departments.update', $dept) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="modal-content border-0 shadow-lg">
                                <div class="modal-header border-0 pb-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="icon-wrap icon-blue"><i class="bi bi-pencil"></i></span>
                                        <h5 class="modal-title mb-0">Edit Department</h5>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body pt-3">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">Department Name <span class="req">*</span></label>
                                            <input type="text" name="name" class="form-control"
                                                   value="{{ $dept->name }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Code</label>
                                            <input type="text" name="code" class="form-control"
                                                   value="{{ $dept->code }}" placeholder="IT, HR…">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control"
                                                      rows="2">{{ $dept->description }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="active"   {{ $dept->status === 'active'   ? 'selected':'' }}>Active</option>
                                                <option value="inactive" {{ $dept->status === 'inactive' ? 'selected':'' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-1"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-building fs-1 d-block mb-2 opacity-25"></i>
                        No departments yet.
                        <a href="#" data-bs-toggle="modal" data-bs-target="#addModal">Add the first one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($departments->hasPages())
    <div class="px-4 py-3 border-top">{{ $departments->links() }}</div>
    @endif
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.departments.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="icon-wrap icon-green"><i class="bi bi-plus-lg"></i></span>
                        <h5 class="modal-title mb-0">Add Department</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Department Name <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="e.g. IT Department">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control"
                                   placeholder="IT, HR, FIN…">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Brief description of this department's role"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Add Department
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
