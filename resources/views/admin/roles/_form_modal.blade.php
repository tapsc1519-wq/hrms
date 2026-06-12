@php
    $isEdit = $mode === 'edit';
    $selected = old('permissions', $role?->permissions ?? []);
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form action="{{ $isEdit ? route('admin.roles.update', $role) : route('admin.roles.store') }}" method="POST">
            @csrf
            @if($isEdit) @method('PATCH') @endif
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $isEdit ? 'Edit Role' : 'Add Role' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Role Name <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $role->name ?? '') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Portal Type <span class="req">*</span></label>
                            <select name="portal_role" class="form-select" required>
                                @foreach(['admin' => 'Admin', 'staff' => 'Staff', 'supplier' => 'Supplier'] as $value => $label)
                                <option value="{{ $value }}" {{ old('portal_role', $role->portal_role ?? 'staff') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">User must have this same portal role.</div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', $role->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $role->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description', $role->description ?? '') }}">
                        </div>
                    </div>

                    @if(!$isEdit)
                    <div class="border rounded-3 p-3 mb-4" style="background:#f8fafc">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="fw-700" style="font-size:.86rem;color:#0f172a">Quick Role Templates</div>
                                <div class="text-muted" style="font-size:.74rem">Start with a common role, then adjust permissions below.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary role-template-clear">Clear</button>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary role-template-btn"
                                    data-role-name="HR Manager"
                                    data-portal-role="admin"
                                    data-description="Handles employees, attendance, leaves, documents and HR settings."
                                    data-permissions="hrms.dashboard,employees.manage,employees.documents,attendance.view,attendance.manage,attendance.regularizations.review,leaves.manage,leave_balances.manage,hrms.settings,payroll.setup">
                                <i class="bi bi-person-workspace me-1"></i>HR Manager
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary role-template-btn"
                                    data-role-name="Payroll Manager"
                                    data-portal-role="admin"
                                    data-description="Handles salary setup, payroll runs, approvals, payments and exports."
                                    data-permissions="payroll.setup,payroll.run,payroll.approve,payroll.pay,payroll.export,attendance.view">
                                <i class="bi bi-cash-stack me-1"></i>Payroll Manager
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary role-template-btn"
                                    data-role-name="HR Executive"
                                    data-portal-role="admin"
                                    data-description="Handles employee records, documents, attendance review and leaves."
                                    data-permissions="hrms.dashboard,employees.manage,employees.documents,attendance.view,attendance.regularizations.review,leaves.manage">
                                <i class="bi bi-people me-1"></i>HR Executive
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary role-template-btn"
                                    data-role-name="IT Asset Manager"
                                    data-portal-role="admin"
                                    data-description="Handles assets, catalog, assignments, suppliers, purchases and support tickets."
                                    data-permissions="dashboard.view,assets.view,assets.create,assets.edit,assets.import,assets.catalog,assignments.view,assignments.create,assignments.return,requests.view,requests.review,requests.fulfill,suppliers.manage,purchase_orders.manage,maintenance.manage,tickets.manage,reports.view">
                                <i class="bi bi-box-seam me-1"></i>IT Asset Manager
                            </button>
                        </div>
                    </div>
                    @endif

                    <div class="row g-3">
                        @foreach($permissionGroups as $group => $permissions)
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-700 mb-2">{{ $group }}</div>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($permissions as $key => $label)
                                    <label class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}"
                                               {{ in_array($key, $selected, true) ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ $label }}</span>
                                        <small class="text-muted d-block">{{ $key }}</small>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Save Role' : 'Create Role' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('click', function (event) {
    var templateButton = event.target.closest('.role-template-btn');
    var clearButton = event.target.closest('.role-template-clear');

    if (!templateButton && !clearButton) return;

    var modal = event.target.closest('.modal');
    if (!modal) return;

    var form = modal.querySelector('form');
    if (!form) return;

    form.querySelectorAll('input[name="permissions[]"]').forEach(function (checkbox) {
        checkbox.checked = false;
    });

    if (clearButton) {
        form.querySelector('[name="name"]').value = '';
        form.querySelector('[name="description"]').value = '';
        return;
    }

    form.querySelector('[name="name"]').value = templateButton.dataset.roleName || '';
    form.querySelector('[name="portal_role"]').value = templateButton.dataset.portalRole || 'admin';
    form.querySelector('[name="description"]').value = templateButton.dataset.description || '';

    (templateButton.dataset.permissions || '').split(',').forEach(function (permission) {
        var checkbox = form.querySelector('input[name="permissions[]"][value="' + permission + '"]');
        if (checkbox) checkbox.checked = true;
    });
});
</script>
@endpush
@endonce
