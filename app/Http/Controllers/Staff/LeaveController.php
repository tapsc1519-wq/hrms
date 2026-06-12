<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index()
    {
        $leaves = LeaveRequest::where('user_id', auth()->id())->latest()->paginate(20);
        $employee = EmployeeProfile::where('user_id', auth()->id())->first();
        $balances = collect();

        if ($employee) {
            $balances = LeaveType::where('organization_id', $employee->organization_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(fn($type) => LeaveBalance::ensure($employee, $type, (int) now()->format('Y')));
        }

        return view('staff.leaves.index', compact('leaves', 'balances'));
    }

    public function create()
    {
        $leaveTypes = LeaveType::where('organization_id', auth()->user()->organization_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('staff.leaves.create', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        $employee = EmployeeProfile::where('user_id', auth()->id())->firstOrFail();

        $data = $request->validate([
            'leave_type' => ['required', 'exists:leave_types,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $leaveType = LeaveType::where('organization_id', $employee->organization_id)
            ->where('status', 'active')
            ->whereKey($data['leave_type'])
            ->firstOrFail();

        $from = \Carbon\Carbon::parse($data['from_date']);
        $to = \Carbon\Carbon::parse($data['to_date']);

        LeaveRequest::create([
            'organization_id' => $employee->organization_id,
            'employee_profile_id' => $employee->id,
            'user_id' => auth()->id(),
            'leave_type_id' => $leaveType->id,
            'leave_type' => strtolower($leaveType->code),
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'total_days' => $from->diffInDays($to) + 1,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('staff.leaves.index')->with('success', 'Leave request submitted successfully.');
    }

    public function cancel(LeaveRequest $leave)
    {
        abort_if($leave->user_id !== auth()->id(), 403);
        abort_if($leave->status !== 'pending', 422, 'Only pending leave requests can be cancelled.');

        $leave->update(['status' => 'cancelled']);

        return back()->with('success', 'Leave request cancelled.');
    }
}
