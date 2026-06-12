@php
    $selectedDays = old('working_days', $shift?->working_days ?? ['mon', 'tue', 'wed', 'thu', 'fri']);
@endphp

<div class="modal-header border-0 pb-0">
    <div>
        <h5 class="modal-title fw-bold">{{ $shift ? 'Edit Shift' : 'Add Shift' }}</h5>
        <div class="text-muted small">Configure work timings and attendance thresholds.</div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Shift Name <span class="req">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $shift?->name) }}" placeholder="General Shift" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Code <span class="req">*</span></label>
            <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code', $shift?->code) }}" placeholder="GENERAL" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Start Time <span class="req">*</span></label>
            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $shift ? substr((string) $shift->start_time, 0, 5) : '09:30') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">End Time <span class="req">*</span></label>
            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $shift ? substr((string) $shift->end_time, 0, 5) : '18:30') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Grace Minutes</label>
            <input type="number" name="grace_minutes" min="0" max="240" class="form-control" value="{{ old('grace_minutes', $shift?->grace_minutes ?? 15) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Half Day Minutes</label>
            <input type="number" name="half_day_minutes" min="0" max="1440" class="form-control" value="{{ old('half_day_minutes', $shift?->half_day_minutes ?? 240) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Full Day Minutes</label>
            <input type="number" name="full_day_minutes" min="0" max="1440" class="form-control" value="{{ old('full_day_minutes', $shift?->full_day_minutes ?? 480) }}">
        </div>
        <div class="col-12">
            <label class="form-label">Working Days</label>
            <div class="d-flex flex-wrap gap-2">
                @foreach($days as $value => $label)
                    <input type="checkbox" class="btn-check" name="working_days[]" value="{{ $value }}" id="day-{{ $shift?->id ?? 'new' }}-{{ $value }}" @checked(in_array($value, $selectedDays, true))>
                    <label class="btn btn-outline-primary btn-sm" for="day-{{ $shift?->id ?? 'new' }}-{{ $value }}">{{ $label }}</label>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active" @selected(old('status', $shift?->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $shift?->status) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch">
                <input type="checkbox" name="is_night_shift" value="1" class="form-check-input" id="night-{{ $shift?->id ?? 'new' }}" @checked(old('is_night_shift', $shift?->is_night_shift ?? false))>
                <label class="form-check-label" for="night-{{ $shift?->id ?? 'new' }}">Night shift / crosses midnight</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Optional shift notes">{{ old('description', $shift?->description) }}</textarea>
        </div>
    </div>
</div>
<div class="modal-footer border-0 pt-0">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Shift</button>
</div>
