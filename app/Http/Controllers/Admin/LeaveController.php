<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::where('organization_id', $this->orgId())
            ->with(['user.department', 'employee', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('user', fn($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $leaves = $query->latest()->paginate(30)->withQueryString();
        $departments = Department::where('organization_id', $this->orgId())->orderBy('name')->get();

        return view('admin.leaves.index', compact('leaves', 'departments'));
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        $this->authorizeLeave($leave);

        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($leave, $data) {
            if ($leave->leaveType) {
                $balance = LeaveBalance::ensure($leave->employee, $leave->leaveType, (int) $leave->from_date->format('Y'));
                abort_if($balance->available < (float) $leave->total_days, 422, 'Insufficient leave balance for this leave type.');
                $balance->increment('used', (float) $leave->total_days);
            }

            $leave->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $this->authorizeLeave($leave);

        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);

        $leave->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        return back()->with('success', 'Leave request rejected.');
    }

    private function authorizeLeave(LeaveRequest $leave): void
    {
        abort_if($leave->organization_id !== $this->orgId(), 403);
        abort_if($leave->status !== 'pending', 422, 'Only pending leave requests can be reviewed.');
    }
}
