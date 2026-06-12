<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\PayrollRunItem;

class PayrollController extends Controller
{
    public function index()
    {
        $employee = EmployeeProfile::where('user_id', auth()->id())->first();

        if (!$employee) {
            return view('staff.profile.missing');
        }

        $payslips = PayrollRunItem::where('employee_profile_id', $employee->id)
            ->whereHas('run', fn($q) => $q->whereIn('status', ['approved', 'paid']))
            ->with(['run', 'components'])
            ->latest()
            ->paginate(12);

        return view('staff.payroll.index', compact('employee', 'payslips'));
    }

    public function show(PayrollRunItem $item)
    {
        $employee = EmployeeProfile::where('user_id', auth()->id())->firstOrFail();
        abort_if($item->employee_profile_id !== $employee->id, 403);

        $item->load(['run.organization', 'employee.user.department', 'components']);
        abort_if(!in_array($item->run->status, ['approved', 'paid'], true), 403);

        return view('staff.payroll.show', compact('item'));
    }
}
