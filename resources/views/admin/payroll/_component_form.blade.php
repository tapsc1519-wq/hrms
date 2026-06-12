<div class="modal-header border-0 pb-0">
    <div>
        <h5 class="modal-title fw-bold">{{ $component ? 'Edit Component' : 'Add Component' }}</h5>
        <div class="text-muted small">Create reusable earning and deduction heads.</div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-7">
            <label class="form-label">Name <span class="req">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $component?->name) }}" placeholder="Basic Salary" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">Code <span class="req">*</span></label>
            <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code', $component?->code) }}" placeholder="BASIC" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="earning" @selected(old('type', $component?->type ?? 'earning') === 'earning')>Earning</option>
                <option value="deduction" @selected(old('type', $component?->type) === 'deduction')>Deduction</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active" @selected(old('status', $component?->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $component?->status) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="checkbox" name="is_statutory" value="1" class="form-check-input" id="statutory{{ $component?->id ?? 'new' }}" @checked(old('is_statutory', $component?->is_statutory ?? false))>
                <label class="form-check-label" for="statutory{{ $component?->id ?? 'new' }}">Statutory component</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $component?->description) }}</textarea>
        </div>
    </div>
</div>
<div class="modal-footer border-0 pt-0">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Component</button>
</div>
