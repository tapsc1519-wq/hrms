<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $leaveTypes = LeaveType::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $employeeQuery = EmployeeProfile::where('organization_id', $this->orgId())
            ->with(['user.department']);

        if ($request->filled('department_id')) {
            $employeeQuery->whereHas('user', fn($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $employeeQuery->whereHas('user', fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            );
        }

        $employees = $employeeQuery->get();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                LeaveBalance::ensure($employee, $leaveType, $year);
            }
        }

        $balanceQuery = LeaveBalance::where('organization_id', $this->orgId())
            ->where('year', $year)
            ->with(['employee.user.department', 'leaveType'])
            ->whereHas('employee.user');

        if ($request->filled('department_id')) {
            $balanceQuery->whereHas('employee.user', fn($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $balanceQuery->whereHas('employee.user', fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            );
        }

        if ($request->filled('leave_type_id')) {
            $balanceQuery->where('leave_type_id', $request->leave_type_id);
        }

        $balances = $balanceQuery
            ->orderBy('employee_profile_id')
            ->orderBy('leave_type_id')
            ->paginate(40)
            ->withQueryString();

        $departments = Department::where('organization_id', $this->orgId())->orderBy('name')->get();

        return view('admin.leave-balances.index', compact('balances', 'departments', 'leaveTypes', 'year'));
    }

    public function update(Request $request, LeaveBalance $leaveBalance)
    {
        abort_if($leaveBalance->organization_id !== $this->orgId(), 403);

        $data = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'credited' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'used' => ['required', 'numeric', 'min:0', 'max:999.99'],
        ]);

        $leaveBalance->update($data);

        return back()->with('success', 'Leave balance updated.');
    }
}
