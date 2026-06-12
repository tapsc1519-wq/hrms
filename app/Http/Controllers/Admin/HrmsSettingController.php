<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrHoliday;
use App\Models\HrmsSetting;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HrmsSettingController extends Controller
{
    public function index()
    {
        $setting = HrmsSetting::forOrganization($this->orgId());
        $this->ensureDefaultLeaveTypes();
        $leaveTypes = LeaveType::where('organization_id', $this->orgId())->orderBy('name')->get();
        $holidays = HrHoliday::where('organization_id', $this->orgId())->orderBy('holiday_date')->get();

        return view('admin.hrms-settings.index', compact('setting', 'leaveTypes', 'holidays'));
    }

    public function updateRules(Request $request)
    {
        $data = $request->validate([
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'office_start_time' => ['required', 'date_format:H:i'],
            'office_end_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'half_day_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'full_day_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'allow_weekend_attendance' => ['nullable', 'boolean'],
        ]);

        HrmsSetting::forOrganization($this->orgId())->update([
            'working_days' => $data['working_days'] ?? [],
            'office_start_time' => $data['office_start_time'],
            'office_end_time' => $data['office_end_time'],
            'grace_minutes' => $data['grace_minutes'],
            'half_day_minutes' => $data['half_day_minutes'],
            'full_day_minutes' => $data['full_day_minutes'],
            'allow_weekend_attendance' => $request->boolean('allow_weekend_attendance'),
        ]);

        return back()->with('success', 'HRMS attendance rules updated.');
    }

    public function storeLeaveType(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', Rule::unique('leave_types')->where('organization_id', $this->orgId())],
            'annual_quota' => ['required', 'numeric', 'min:0', 'max:365'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        LeaveType::create([
            'organization_id' => $this->orgId(),
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'annual_quota' => $data['annual_quota'],
            'is_paid' => $request->boolean('is_paid'),
            'requires_approval' => $request->boolean('requires_approval', true),
            'status' => $data['status'],
        ]);

        return back()->with('success', 'Leave type added.');
    }

    public function updateLeaveType(Request $request, LeaveType $leaveType)
    {
        $this->authorizeOrg($leaveType->organization_id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', Rule::unique('leave_types')->where('organization_id', $this->orgId())->ignore($leaveType->id)],
            'annual_quota' => ['required', 'numeric', 'min:0', 'max:365'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $leaveType->update([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'annual_quota' => $data['annual_quota'],
            'is_paid' => $request->boolean('is_paid'),
            'requires_approval' => $request->boolean('requires_approval'),
            'status' => $data['status'],
        ]);

        return back()->with('success', 'Leave type updated.');
    }

    public function destroyLeaveType(LeaveType $leaveType)
    {
        $this->authorizeOrg($leaveType->organization_id);
        $leaveType->delete();

        return back()->with('success', 'Leave type deleted.');
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'holiday_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['public', 'company', 'optional'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        HrHoliday::create([
            'organization_id' => $this->orgId(),
            ...$data,
        ]);

        return back()->with('success', 'Holiday added.');
    }

    public function updateHoliday(Request $request, HrHoliday $holiday)
    {
        $this->authorizeOrg($holiday->organization_id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'holiday_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['public', 'company', 'optional'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $holiday->update($data);

        return back()->with('success', 'Holiday updated.');
    }

    public function destroyHoliday(HrHoliday $holiday)
    {
        $this->authorizeOrg($holiday->organization_id);
        $holiday->delete();

        return back()->with('success', 'Holiday deleted.');
    }

    private function authorizeOrg(int $organizationId): void
    {
        abort_if($organizationId !== $this->orgId(), 403);
    }

    private function ensureDefaultLeaveTypes(): void
    {
        if (LeaveType::where('organization_id', $this->orgId())->exists()) {
            return;
        }

        foreach ([
            ['name' => 'Casual Leave', 'code' => 'casual', 'annual_quota' => 12, 'is_paid' => true],
            ['name' => 'Sick Leave', 'code' => 'sick', 'annual_quota' => 12, 'is_paid' => true],
            ['name' => 'Earned Leave', 'code' => 'earned', 'annual_quota' => 15, 'is_paid' => true],
            ['name' => 'Unpaid Leave', 'code' => 'unpaid', 'annual_quota' => 0, 'is_paid' => false],
        ] as $type) {
            LeaveType::create([
                'organization_id' => $this->orgId(),
                'name' => $type['name'],
                'code' => $type['code'],
                'annual_quota' => $type['annual_quota'],
                'is_paid' => $type['is_paid'],
                'requires_approval' => true,
                'status' => 'active',
            ]);
        }
    }
}
