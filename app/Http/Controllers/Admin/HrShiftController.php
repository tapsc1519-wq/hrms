<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrShift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HrShiftController extends Controller
{
    public function index()
    {
        $shifts = HrShift::where('organization_id', $this->orgId())
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return view('admin.hrms-shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['is_night_shift'] = $request->boolean('is_night_shift');
        $data['working_days'] = $data['working_days'] ?? [];
        HrShift::create(array_merge($data, ['organization_id' => $this->orgId()]));

        return back()->with('success', 'Shift created successfully.');
    }

    public function update(Request $request, HrShift $shift)
    {
        $this->authorizeShift($shift);
        $data = $this->validated($request, $shift);
        $data['is_night_shift'] = $request->boolean('is_night_shift');
        $data['working_days'] = $data['working_days'] ?? [];
        $shift->update($data);

        return back()->with('success', 'Shift updated successfully.');
    }

    public function destroy(HrShift $shift)
    {
        $this->authorizeShift($shift);
        abort_if($shift->employees()->exists(), 422, 'This shift is assigned to employees and cannot be deleted.');

        $shift->delete();

        return back()->with('success', 'Shift deleted successfully.');
    }

    private function validated(Request $request, ?HrShift $shift = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('hr_shifts', 'code')->where('organization_id', $this->orgId())->ignore($shift?->id),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'half_day_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'full_day_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'is_night_shift' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function authorizeShift(HrShift $shift): void
    {
        abort_if($shift->organization_id !== $this->orgId(), 403);
    }
}
