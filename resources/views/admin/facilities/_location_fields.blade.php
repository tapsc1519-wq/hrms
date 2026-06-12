{{-- Shared work-location fields used in add/edit modals --}}
<div class="row g-3">
    <div class="col-12">
        <label class="form-label fw-semibold">Location Name <span class="text-danger">*</span></label>
        <input type="text" name="name" required
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $loc?->name) }}"
               placeholder="e.g. 5th Floor Office, Server Room, Warehouse A">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Building</label>
        <input type="text" name="building"
               class="form-control"
               value="{{ old('building', $loc?->building) }}"
               placeholder="e.g. Tower A">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Floor</label>
        <input type="text" name="floor"
               class="form-control"
               value="{{ old('floor', $loc?->floor) }}"
               placeholder="e.g. 5F">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Room</label>
        <input type="text" name="room"
               class="form-control"
               value="{{ old('room', $loc?->room) }}"
               placeholder="501">
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Description</label>
        <textarea name="description" rows="2" class="form-control"
                  placeholder="Optional notes">{{ old('description', $loc?->description) }}</textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select">
            <option value="active"   {{ old('status', $loc?->status ?? 'active') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $loc?->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
</div>
