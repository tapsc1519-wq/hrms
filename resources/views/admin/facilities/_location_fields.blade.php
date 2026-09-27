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

    <div class="col-12"><hr class="my-1"></div>
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div>
                <div class="fw-bold"><i class="bi bi-geo-alt-fill text-primary me-1"></i> Attendance Geo-fence</div>
                <div class="text-muted small">Control where employees assigned to this work location can mark attendance.</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary js-use-current-location">
                <i class="bi bi-crosshair me-1"></i>Use Current Location
            </button>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Enforcement Mode</label>
        <select name="geofence_mode" class="form-select js-geofence-mode" required>
            @foreach(['disabled' => 'Disabled', 'warning' => 'Warning Only', 'strict' => 'Strict'] as $value => $label)
                <option value="{{ $value }}" @selected(old('geofence_mode', $loc?->geofence_mode ?? 'disabled') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text">Warning records exceptions; Strict blocks attendance outside the radius.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Radius (metres)</label>
        <input type="number" name="geofence_radius_metres" min="20" max="5000" required class="form-control"
               value="{{ old('geofence_radius_metres', $loc?->geofence_radius_metres ?? 100) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Max Accuracy</label>
        <input type="number" name="maximum_gps_accuracy_metres" min="10" max="1000" required class="form-control"
               value="{{ old('maximum_gps_accuracy_metres', $loc?->maximum_gps_accuracy_metres ?? 100) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Latitude</label>
        <input type="number" step="0.0000001" name="latitude" class="form-control js-latitude"
               value="{{ old('latitude', $loc?->latitude) }}" placeholder="28.6139000">
        @error('latitude')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Longitude</label>
        <input type="number" step="0.0000001" name="longitude" class="form-control js-longitude"
               value="{{ old('longitude', $loc?->longitude) }}" placeholder="77.2090000">
        @error('longitude')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 d-flex flex-wrap gap-4">
        <div class="form-check form-switch">
            <input type="checkbox" name="require_location_on_sign_in" value="1" class="form-check-input"
                   @checked(old('require_location_on_sign_in', $loc?->require_location_on_sign_in ?? true))>
            <label class="form-check-label">Check location on Sign In</label>
        </div>
        <div class="form-check form-switch">
            <input type="checkbox" name="require_location_on_sign_out" value="1" class="form-check-input"
                   @checked(old('require_location_on_sign_out', $loc?->require_location_on_sign_out ?? true))>
            <label class="form-check-label">Check location on Sign Out</label>
        </div>
    </div>
</div>
