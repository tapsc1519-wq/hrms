@php
    $isEdit = isset($employee);
    $profile = $employee ?? null;
    $selectedDepartment = old('department_id', $profile?->user?->department_id);
    $selectedManager = old('reporting_manager_id', $profile?->reporting_manager_id);
    $selectedFacility = old('facility_id', $profile?->facility_id);
    $selectedLocation = old('location_id', $profile?->location_id);
    $selectedShift = old('shift_id', $profile?->shift_id);
    $selectedRole = old('role', $profile?->user?->role ?? 'staff');
    $selectedPermissionRole = old('custom_role_id', $profile?->user?->custom_role_id);
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-blue"><i class="bi bi-person-badge"></i></span>
                Employee Identity
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Employee Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $profile?->user?->name) }}" placeholder="Amit Sharma" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Official Email <span class="req">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $profile?->user?->email) }}" placeholder="amit.sharma@company.com" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Employee Code</label>
                        <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror"
                               value="{{ old('employee_code', $profile?->employee_code) }}" placeholder="EMP-1001">
                        @error('employee_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Portal Access <span class="req">*</span></label>
                        <select name="role" id="portalRoleSelect" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="staff" @selected($selectedRole === 'staff')>Employee Portal Only</option>
                            <option value="admin" @selected($selectedRole === 'admin')>Admin / Manager Portal</option>
                        </select>
                        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">First choose which portal this person can open.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Password {{ $isEdit ? '' : '' }}</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               minlength="8" placeholder="{{ $isEdit ? 'Leave blank to keep current' : 'Default: Welcome@123' }}">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @unless($isEdit)
                            <div class="form-text">Leave blank to use <strong>Welcome@123</strong>.</div>
                        @endunless
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                            <option value="">No department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string)$selectedDepartment === (string)$department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Permission Role <span class="text-muted">(optional)</span></label>
                        <select name="custom_role_id" id="permissionRoleSelect" class="form-select @error('custom_role_id') is-invalid @enderror">
                            <option value="" id="defaultPermissionRoleOption">Default access for selected portal</option>
                            @foreach($customRoles as $role)
                                <option value="{{ $role->id }}" data-portal-role="{{ $role->portal_role }}" @selected((string)$selectedPermissionRole === (string)$role->id)>
                                    {{ $role->name }} - {{ ucfirst($role->portal_role) }} portal role
                                </option>
                            @endforeach
                        </select>
                        @error('custom_role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text" id="permissionRoleHelp">Only roles matching the selected portal access are shown.</div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Department is only for reporting and grouping. Portal Access decides employee vs admin area. Permission Role decides exact module permissions such as HR, Assets, SAM, Payroll or Support.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror"
                               value="{{ old('job_title', $profile?->user?->job_title) }}" placeholder="IT Executive">
                        @error('job_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone', $profile?->user?->phone) }}" placeholder="+91 98765 43210">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-briefcase"></i></span>
                Employment Details
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Joining Date</label>
                        <input type="date" name="joining_date" class="form-control @error('joining_date') is-invalid @enderror"
                               value="{{ old('joining_date', $profile?->joining_date?->format('Y-m-d')) }}">
                        @error('joining_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Employment Type <span class="req">*</span></label>
                        <select name="employment_type" class="form-select @error('employment_type') is-invalid @enderror" required>
                            @foreach(['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'intern' => 'Intern', 'consultant' => 'Consultant'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_type', $profile?->employment_type ?? 'full_time') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Employment Status <span class="req">*</span></label>
                        <select name="employment_status" class="form-select @error('employment_status') is-invalid @enderror" required>
                            @foreach(['active' => 'Active', 'probation' => 'Probation', 'notice' => 'Notice Period', 'resigned' => 'Resigned', 'terminated' => 'Terminated'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_status', $profile?->employment_status ?? 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reporting Manager</label>
                        <select name="reporting_manager_id" class="form-select @error('reporting_manager_id') is-invalid @enderror">
                            <option value="">No manager assigned</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}" @selected((string)$selectedManager === (string)$manager->id)>
                                    {{ $manager->name }}{{ $manager->job_title ? ' — '.$manager->job_title : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('reporting_manager_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Exit Date</label>
                        <input type="date" name="exit_date" class="form-control @error('exit_date') is-invalid @enderror"
                               value="{{ old('exit_date', $profile?->exit_date?->format('Y-m-d')) }}">
                        @error('exit_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Shift</label>
                        <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror">
                            <option value="">No shift assigned</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" @selected((string)$selectedShift === (string)$shift->id)>
                                    {{ $shift->name }} ({{ $shift->time_range }})
                                </option>
                            @endforeach
                        </select>
                        @error('shift_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-purple"><i class="bi bi-building"></i></span>
                Work Location
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Facility</label>
                        <select name="facility_id" id="facilitySelect" class="form-select @error('facility_id') is-invalid @enderror">
                            <option value="">No facility</option>
                            @foreach($facilities as $facility)
                                <option value="{{ $facility->id }}" @selected((string)$selectedFacility === (string)$facility->id)>
                                    {{ $facility->display_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('facility_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Work Location</label>
                        <select name="location_id" id="locationSelect" class="form-select @error('location_id') is-invalid @enderror">
                            <option value="">No work location</option>
                            @foreach($facilities as $facility)
                                @if($facility->activeLocations->count())
                                    <optgroup label="{{ $facility->name }}">
                                        @foreach($facility->activeLocations as $location)
                                            <option value="{{ $location->id }}" data-facility="{{ $facility->id }}" @selected((string)$selectedLocation === (string)$location->id)>
                                                {{ $location->name }}{{ $location->floor ? ' — Floor '.$location->floor : '' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                            @foreach($locations->whereNull('facility_id') as $location)
                                <option value="{{ $location->id }}" data-facility="" @selected((string)$selectedLocation === (string)$location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-teal"><i class="bi bi-bank"></i></span>
                Payroll & Statutory Details
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                               value="{{ old('bank_name', $profile?->bank_name) }}" placeholder="HDFC Bank">
                        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Holder Name</label>
                        <input type="text" name="bank_account_name" class="form-control @error('bank_account_name') is-invalid @enderror"
                               value="{{ old('bank_account_name', $profile?->bank_account_name) }}" placeholder="Amit Sharma">
                        @error('bank_account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="bank_account_number" class="form-control @error('bank_account_number') is-invalid @enderror"
                               value="{{ old('bank_account_number', $profile?->bank_account_number) }}" placeholder="123456789012">
                        @error('bank_account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IFSC Code</label>
                        <input type="text" name="ifsc_code" class="form-control text-uppercase @error('ifsc_code') is-invalid @enderror"
                               value="{{ old('ifsc_code', $profile?->ifsc_code) }}" placeholder="HDFC0001234">
                        @error('ifsc_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PAN Number</label>
                        <input type="text" name="pan_number" class="form-control text-uppercase @error('pan_number') is-invalid @enderror"
                               value="{{ old('pan_number', $profile?->pan_number) }}" placeholder="ABCDE1234F">
                        @error('pan_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">UAN Number</label>
                        <input type="text" name="uan_number" class="form-control @error('uan_number') is-invalid @enderror"
                               value="{{ old('uan_number', $profile?->uan_number) }}" placeholder="Universal Account Number">
                        @error('uan_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PF Number</label>
                        <input type="text" name="pf_number" class="form-control @error('pf_number') is-invalid @enderror"
                               value="{{ old('pf_number', $profile?->pf_number) }}" placeholder="Provident Fund number">
                        @error('pf_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ESI Number</label>
                        <input type="text" name="esi_number" class="form-control @error('esi_number') is-invalid @enderror"
                               value="{{ old('esi_number', $profile?->esi_number) }}" placeholder="ESI insurance number">
                        @error('esi_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-person-lines-fill"></i></span>
                Personal & Emergency
            </div>
            <div class="form-card-body">
                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                           value="{{ old('date_of_birth', $profile?->date_of_birth?->format('Y-m-d')) }}">
                    @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">Prefer not to set</option>
                        @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer Not To Say'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender', $profile?->gender) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Personal Email</label>
                    <input type="email" name="personal_email" class="form-control @error('personal_email') is-invalid @enderror"
                           value="{{ old('personal_email', $profile?->personal_email) }}" placeholder="personal@example.com">
                    @error('personal_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control @error('emergency_contact_name') is-invalid @enderror"
                           value="{{ old('emergency_contact_name', $profile?->emergency_contact_name) }}">
                    @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                           value="{{ old('emergency_contact_phone', $profile?->emergency_contact_phone) }}">
                    @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $profile?->address) }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $profile?->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const facilitySelect = document.getElementById('facilitySelect');
    const locationSelect = document.getElementById('locationSelect');
    const portalRoleSelect = document.getElementById('portalRoleSelect');
    const permissionRoleSelect = document.getElementById('permissionRoleSelect');
    const defaultPermissionRoleOption = document.getElementById('defaultPermissionRoleOption');
    const permissionRoleHelp = document.getElementById('permissionRoleHelp');

    function filterLocations() {
        const facilityId = facilitySelect.value;
        Array.from(locationSelect.options).forEach(option => {
            if (!option.value) return;
            const optionFacility = option.dataset.facility || '';
            option.hidden = facilityId && optionFacility !== facilityId;
        });
        const selected = locationSelect.selectedOptions[0];
        if (selected && selected.hidden) {
            locationSelect.value = '';
        }
    }

    function filterPermissionRoles() {
        if (!portalRoleSelect || !permissionRoleSelect) return;
        const role = portalRoleSelect.value;
        let visibleRoles = 0;
        Array.from(permissionRoleSelect.options).forEach(option => {
            if (!option.value) return;
            option.hidden = option.dataset.portalRole !== role;
            if (!option.hidden) visibleRoles++;
        });
        const selected = permissionRoleSelect.selectedOptions[0];
        if (selected && selected.hidden) {
            permissionRoleSelect.value = '';
        }

        if (defaultPermissionRoleOption) {
            defaultPermissionRoleOption.textContent = role === 'admin'
                ? 'Full admin access - use only for trusted admins'
                : 'Default employee self-service access';
        }

        if (permissionRoleHelp) {
            permissionRoleHelp.textContent = role === 'admin'
                ? (visibleRoles > 0
                    ? 'Select a role such as IT Manager, HR Manager or Finance Manager to limit this admin to required modules.'
                    : 'No admin permission roles are available yet. Create them from Roles & Permissions before giving controlled admin access.')
                : (visibleRoles > 0
                    ? 'Normal employees usually use default self-service access. Select a staff role only when extra staff permissions are required.'
                    : 'No staff permission roles are available yet. Default self-service access will be used.');
        }
    }

    if (facilitySelect && locationSelect) {
        facilitySelect.addEventListener('change', filterLocations);
        filterLocations();
    }

    if (portalRoleSelect && permissionRoleSelect) {
        portalRoleSelect.addEventListener('change', filterPermissionRoles);
        filterPermissionRoles();
    }
});
</script>
@endpush
