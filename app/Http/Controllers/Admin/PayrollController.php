<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLock;
use App\Models\AttendanceRecord;
use App\Models\EmployeeProfile;
use App\Models\EmployeeSalaryStructure;
use App\Models\LeaveRequest;
use App\Models\PayrollComponent;
use App\Models\PayrollRun;
use App\Support\AttendanceDayResolver;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    public function index()
    {
        $components = PayrollComponent::where('organization_id', $this->orgId())
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $employees = EmployeeProfile::where('organization_id', $this->orgId())
            ->with(['user.department'])
            ->whereHas('user')
            ->orderBy('employee_code')
            ->get();

        $structures = EmployeeSalaryStructure::where('organization_id', $this->orgId())
            ->with(['employee.user.department', 'components.payrollComponent'])
            ->latest('effective_from')
            ->paginate(20);

        return view('admin.payroll.index', compact('components', 'employees', 'structures'));
    }

    public function runs(Request $request)
    {
        $month = $request->input('month', now()->subMonth()->format('Y-m'));
        abort_if(!preg_match('/^\d{4}-\d{2}$/', $month), 422, 'Invalid month format.');

        $readiness = $this->payrollReadiness($month);
        $attendanceLock = $readiness['attendance_lock'];

        $runs = PayrollRun::where('organization_id', $this->orgId())
            ->with('generator')
            ->latest('month')
            ->latest()
            ->paginate(15);

        $existingRun = PayrollRun::where('organization_id', $this->orgId())
            ->where('month', $month)
            ->first();

        return view('admin.payroll.runs', compact('runs', 'month', 'attendanceLock', 'existingRun', 'readiness'));
    }

    public function generateRun(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_if(
            !AttendanceLock::isLocked($this->orgId(), $data['month']),
            422,
            'Please lock attendance for this month before generating payroll.'
        );

        $readiness = $this->payrollReadiness($data['month']);
        abort_if(
            !$readiness['is_ready'],
            422,
            'Payroll is not ready. Please resolve pending HR actions and missing salary setup before generating payroll.'
        );

        abort_if(
            PayrollRun::where('organization_id', $this->orgId())->where('month', $data['month'])->exists(),
            422,
            'Payroll for this month already exists. Delete it first if you need to regenerate.'
        );

        $run = DB::transaction(fn() => $this->createPayrollRun($data['month'], $data['notes'] ?? null));

        return redirect()->route('admin.payroll.runs.show', $run)->with('success', 'Payroll run generated.');
    }

    public function showRun(PayrollRun $run)
    {
        $this->authorizeRun($run);

        $run->load([
            'generator',
            'approver',
            'payer',
            'items.employee.user.department',
            'items.salaryStructure',
            'items.components',
        ]);

        return view('admin.payroll.show', compact('run'));
    }

    public function approveRun(PayrollRun $run)
    {
        $this->authorizeRun($run);
        abort_if($run->status !== 'draft', 422, 'Only draft payroll runs can be approved.');

        $run->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Payroll run approved. Payslips are now visible to employees.');
    }

    public function markRunPaid(PayrollRun $run)
    {
        $this->authorizeRun($run);
        abort_if($run->status !== 'approved', 422, 'Only approved payroll runs can be marked as paid.');

        $run->update([
            'status' => 'paid',
            'paid_by' => auth()->id(),
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Payroll run marked as paid.');
    }

    public function payslip(PayrollRun $run, \App\Models\PayrollRunItem $item)
    {
        $this->authorizeRun($run);
        abort_if($item->payroll_run_id !== $run->id, 404);

        $item->load(['run.organization', 'employee.user.department', 'components']);

        return view('admin.payroll.payslip', compact('run', 'item'));
    }

    public function exportRun(PayrollRun $run)
    {
        $this->authorizeRun($run);
        $run->load(['items.employee.user.department', 'items.components']);
        $filename = "payroll-{$run->month}.csv";

        return response()->streamDownload(function () use ($run) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Employee Code',
                'Employee Name',
                'Department',
                'Month',
                'Days In Month',
                'Present Days',
                'Leave Days',
                'Holiday Days',
                'Weekly Off Days',
                'Payable Days',
                'Gross Earnings',
                'Total Deductions',
                'Net Salary',
                'Remarks',
            ]);

            foreach ($run->items as $item) {
                fputcsv($out, [
                    $item->employee?->employee_code,
                    $item->employee?->user?->name,
                    $item->employee?->user?->department?->name,
                    $run->month,
                    $item->days_in_month,
                    $item->present_days,
                    $item->leave_days,
                    $item->holiday_days,
                    $item->weekly_off_days,
                    $item->payable_days,
                    $item->gross_earnings,
                    $item->total_deductions,
                    $item->net_salary,
                    $item->remarks,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportBankPayment(PayrollRun $run)
    {
        $this->authorizeRun($run);
        abort_if(!in_array($run->status, ['approved', 'paid'], true), 422, 'Approve payroll before exporting bank payment file.');

        $run->load(['items.employee.user.department']);
        $filename = "bank-payment-{$run->month}.csv";

        return response()->streamDownload(function () use ($run) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Employee Code',
                'Employee Name',
                'Bank Name',
                'Account Holder Name',
                'Account Number',
                'IFSC Code',
                'Amount',
                'Narration',
                'Status',
                'Remarks',
            ]);

            foreach ($run->items as $item) {
                $employee = $item->employee;
                $missing = collect([
                    'Bank Name' => $employee?->bank_name,
                    'Account Holder Name' => $employee?->bank_account_name,
                    'Account Number' => $employee?->bank_account_number,
                    'IFSC Code' => $employee?->ifsc_code,
                ])->filter(fn($value) => blank($value))->keys()->implode(', ');

                fputcsv($out, [
                    $employee?->employee_code,
                    $employee?->user?->name,
                    $employee?->bank_name,
                    $employee?->bank_account_name ?: $employee?->user?->name,
                    $employee?->bank_account_number,
                    $employee?->ifsc_code,
                    number_format((float) $item->net_salary, 2, '.', ''),
                    'Salary '.$run->month,
                    $missing ? 'Incomplete' : 'Ready',
                    $missing ? 'Missing: '.$missing : '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroyRun(PayrollRun $run)
    {
        $this->authorizeRun($run);
        abort_if($run->status !== 'draft', 422, 'Only draft payroll runs can be deleted.');
        $run->delete();

        return redirect()->route('admin.payroll.runs')->with('success', 'Payroll run deleted.');
    }

    public function storeComponent(Request $request)
    {
        $data = $this->componentData($request);
        PayrollComponent::create(array_merge($data, ['organization_id' => $this->orgId()]));

        return back()->with('success', 'Payroll component created.');
    }

    public function updateComponent(Request $request, PayrollComponent $component)
    {
        $this->authorizeComponent($component);
        $component->update($this->componentData($request, $component));

        return back()->with('success', 'Payroll component updated.');
    }

    public function destroyComponent(PayrollComponent $component)
    {
        $this->authorizeComponent($component);
        abort_if($component->salaryComponents()->exists(), 422, 'This component is already used in salary structures.');

        $component->delete();

        return back()->with('success', 'Payroll component deleted.');
    }

    public function storeStructure(Request $request)
    {
        $components = PayrollComponent::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        $data = $request->validate([
            'employee_profile_id' => ['required', 'exists:employee_profiles,id'],
            'effective_from' => ['required', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'components' => ['nullable', 'array'],
            'components.*' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $employee = EmployeeProfile::where('organization_id', $this->orgId())->whereKey($data['employee_profile_id'])->firstOrFail();
        $amounts = collect($data['components'] ?? [])
            ->mapWithKeys(fn($amount, $id) => [(int) $id => (float) ($amount ?: 0)])
            ->filter(fn($amount, $id) => $amount > 0 && $components->has($id));

        $gross = $amounts->filter(fn($amount, $id) => $components[$id]->type === 'earning')->sum();
        $deductions = $amounts->filter(fn($amount, $id) => $components[$id]->type === 'deduction')->sum();

        DB::transaction(function () use ($employee, $data, $amounts, $gross, $deductions) {
            if ($data['status'] === 'active') {
                EmployeeSalaryStructure::where('organization_id', $this->orgId())
                    ->where('employee_profile_id', $employee->id)
                    ->update(['status' => 'inactive']);
            }

            $structure = EmployeeSalaryStructure::create([
                'organization_id' => $this->orgId(),
                'employee_profile_id' => $employee->id,
                'effective_from' => $data['effective_from'],
                'gross_earnings' => $gross,
                'total_deductions' => $deductions,
                'net_salary' => $gross - $deductions,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($amounts as $componentId => $amount) {
                $structure->components()->create([
                    'payroll_component_id' => $componentId,
                    'amount' => $amount,
                ]);
            }
        });

        return back()->with('success', 'Employee salary structure saved.');
    }

    public function destroyStructure(EmployeeSalaryStructure $structure)
    {
        abort_if($structure->organization_id !== $this->orgId(), 403);
        $structure->delete();

        return back()->with('success', 'Salary structure deleted.');
    }

    public function updateStructure(Request $request, EmployeeSalaryStructure $structure)
    {
        abort_if($structure->organization_id !== $this->orgId(), 403);

        $components = PayrollComponent::where('organization_id', $this->orgId())
            ->get()
            ->keyBy('id');

        $data = $request->validate([
            'employee_profile_id' => ['required', 'exists:employee_profiles,id'],
            'effective_from' => ['required', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'components' => ['nullable', 'array'],
            'components.*' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $employee = EmployeeProfile::where('organization_id', $this->orgId())
            ->whereKey($data['employee_profile_id'])
            ->firstOrFail();

        $amounts = collect($data['components'] ?? [])
            ->mapWithKeys(fn($amount, $id) => [(int) $id => (float) ($amount ?: 0)])
            ->filter(fn($amount, $id) => $amount > 0 && $components->has($id));

        $gross = $amounts->filter(fn($amount, $id) => $components[$id]->type === 'earning')->sum();
        $deductions = $amounts->filter(fn($amount, $id) => $components[$id]->type === 'deduction')->sum();

        DB::transaction(function () use ($structure, $employee, $data, $amounts, $gross, $deductions) {
            if ($data['status'] === 'active') {
                EmployeeSalaryStructure::where('organization_id', $this->orgId())
                    ->where('employee_profile_id', $employee->id)
                    ->whereKeyNot($structure->id)
                    ->update(['status' => 'inactive']);
            }

            $structure->update([
                'employee_profile_id' => $employee->id,
                'effective_from' => $data['effective_from'],
                'gross_earnings' => $gross,
                'total_deductions' => $deductions,
                'net_salary' => $gross - $deductions,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            $structure->components()->delete();

            foreach ($amounts as $componentId => $amount) {
                $structure->components()->create([
                    'payroll_component_id' => $componentId,
                    'amount' => $amount,
                ]);
            }
        });

        return back()->with('success', 'Salary structure updated.');
    }

    private function componentData(Request $request, ?PayrollComponent $component = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('payroll_components', 'code')->where('organization_id', $this->orgId())->ignore($component?->id),
            ],
            'type' => ['required', Rule::in(['earning', 'deduction'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_statutory' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['is_statutory'] = $request->boolean('is_statutory');

        return $data;
    }

    private function authorizeComponent(PayrollComponent $component): void
    {
        abort_if($component->organization_id !== $this->orgId(), 403);
    }

    private function authorizeRun(PayrollRun $run): void
    {
        abort_if($run->organization_id !== $this->orgId(), 403);
    }

    private function payrollReadiness(string $month): array
    {
        [$year, $monthNumber] = array_map('intval', explode('-', $month));
        $start = now()->setDate($year, $monthNumber, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $attendanceLock = AttendanceLock::where('organization_id', $this->orgId())
            ->where('month', $month)
            ->with('locker')
            ->first();

        $activeEmployees = EmployeeProfile::where('organization_id', $this->orgId())
            ->whereHas('user')
            ->whereIn('employment_status', ['active', 'probation', 'notice'])
            ->with('user.department')
            ->get();

        $activeEmployeeIds = $activeEmployees->pluck('id');

        $salaryEmployeeIds = EmployeeSalaryStructure::where('organization_id', $this->orgId())
            ->whereIn('employee_profile_id', $activeEmployeeIds)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $end->toDateString())
            ->pluck('employee_profile_id')
            ->unique();

        $missingSalaryEmployees = $activeEmployees
            ->whereNotIn('id', $salaryEmployeeIds)
            ->values();

        $pendingLeaves = LeaveRequest::where('organization_id', $this->orgId())
            ->where('status', 'pending')
            ->whereDate('from_date', '<=', $end->toDateString())
            ->whereDate('to_date', '>=', $start->toDateString())
            ->count();

        $pendingRegularizations = \App\Models\AttendanceRegularizationRequest::where('organization_id', $this->orgId())
            ->where('status', 'pending')
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->count();

        $attendanceRecords = AttendanceRecord::where('organization_id', $this->orgId())
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->count();

        $checks = [
            [
                'key' => 'attendance_locked',
                'label' => 'Attendance locked',
                'passed' => (bool) $attendanceLock,
                'help' => $attendanceLock
                    ? 'Locked by ' . ($attendanceLock->locker?->name ?? 'System') . ' on ' . $attendanceLock->locked_at?->format('d-m-Y h:i A')
                    : 'Lock attendance summary before payroll generation.',
                'route' => route('admin.attendance.summary', ['month' => $month]),
            ],
            [
                'key' => 'employees_found',
                'label' => 'Active employees found',
                'passed' => $activeEmployees->isNotEmpty(),
                'help' => $activeEmployees->count() . ' active employees eligible for payroll.',
                'route' => route('admin.employees.index'),
            ],
            [
                'key' => 'salary_setup',
                'label' => 'Salary setup complete',
                'passed' => $missingSalaryEmployees->isEmpty() && $activeEmployees->isNotEmpty(),
                'help' => $missingSalaryEmployees->isEmpty()
                    ? $salaryEmployeeIds->count() . ' employees have active salary structures.'
                    : $missingSalaryEmployees->count() . ' employees are missing active salary structures.',
                'route' => route('admin.payroll.index'),
            ],
            [
                'key' => 'pending_leaves',
                'label' => 'No pending leave approvals',
                'passed' => $pendingLeaves === 0,
                'help' => $pendingLeaves . ' pending leave requests overlap this month.',
                'route' => route('admin.leaves.index', ['status' => 'pending']),
            ],
            [
                'key' => 'pending_regularizations',
                'label' => 'No pending attendance corrections',
                'passed' => $pendingRegularizations === 0,
                'help' => $pendingRegularizations . ' pending regularization requests in this month.',
                'route' => route('admin.attendance.regularizations', ['status' => 'pending']),
            ],
        ];

        return [
            'month_label' => $start->format('F Y'),
            'period_start' => $start,
            'period_end' => $end,
            'attendance_lock' => $attendanceLock,
            'active_employees' => $activeEmployees->count(),
            'salary_ready' => $salaryEmployeeIds->count(),
            'missing_salary' => $missingSalaryEmployees->count(),
            'missing_salary_employees' => $missingSalaryEmployees->take(6),
            'pending_leaves' => $pendingLeaves,
            'pending_regularizations' => $pendingRegularizations,
            'attendance_records' => $attendanceRecords,
            'checks' => $checks,
            'is_ready' => collect($checks)->every(fn (array $check) => $check['passed']),
        ];
    }

    private function createPayrollRun(string $month, ?string $notes): PayrollRun
    {
        [$year, $monthNumber] = array_map('intval', explode('-', $month));
        $start = now()->setDate($year, $monthNumber, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();
        $daysInMonth = (int) $start->daysInMonth;

        $run = PayrollRun::create([
            'organization_id' => $this->orgId(),
            'month' => $month,
            'status' => 'draft',
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'notes' => $notes,
        ]);

        $employees = EmployeeProfile::where('organization_id', $this->orgId())
            ->with(['user.department', 'shift'])
            ->whereHas('user')
            ->whereIn('employment_status', ['active', 'probation', 'notice'])
            ->get();

        $attendance = AttendanceRecord::where('organization_id', $this->orgId())
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('employee_profile_id');

        $leaves = LeaveRequest::where('organization_id', $this->orgId())
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $end->toDateString())
            ->whereDate('to_date', '>=', $start->toDateString())
            ->get()
            ->groupBy('employee_profile_id');

        $period = collect(iterator_to_array(CarbonPeriod::create($start->copy(), $end->copy())));

        foreach ($employees as $employee) {
            $structure = EmployeeSalaryStructure::where('organization_id', $this->orgId())
                ->where('employee_profile_id', $employee->id)
                ->where('status', 'active')
                ->whereDate('effective_from', '<=', $end->toDateString())
                ->with('components.payrollComponent')
                ->latest('effective_from')
                ->first();

            $records = $attendance->get($employee->id, collect());
            $employeeLeaves = $leaves->get($employee->id, collect());
            $dayTypes = $period->map(fn($date) => AttendanceDayResolver::resolve($employee, $date)['day_type']);

            $presentDays = (float) $records->count();
            $leaveDays = (float) $employeeLeaves->sum('total_days');
            $holidayDays = (float) $dayTypes->filter(fn($type) => $type === 'holiday')->count();
            $weeklyOffDays = (float) $dayTypes->filter(fn($type) => $type === 'weekly_off')->count();
            $payableDays = min($daysInMonth, $presentDays + $leaveDays + $holidayDays + $weeklyOffDays);
            $factor = $daysInMonth > 0 ? $payableDays / $daysInMonth : 0;

            $item = $run->items()->create([
                'organization_id' => $this->orgId(),
                'employee_profile_id' => $employee->id,
                'user_id' => $employee->user_id,
                'employee_salary_structure_id' => $structure?->id,
                'days_in_month' => $daysInMonth,
                'present_days' => $presentDays,
                'leave_days' => $leaveDays,
                'holiday_days' => $holidayDays,
                'weekly_off_days' => $weeklyOffDays,
                'payable_days' => $payableDays,
                'work_minutes' => (int) $records->sum('work_minutes'),
                'late_minutes' => (int) $records->sum('late_minutes'),
                'early_leave_minutes' => (int) $records->sum('early_leave_minutes'),
                'overtime_minutes' => (int) $records->sum('overtime_minutes'),
                'remarks' => $structure ? null : 'No active salary structure found.',
            ]);

            $gross = 0;
            $deductions = 0;

            foreach (($structure?->components ?? collect()) as $component) {
                $payrollComponent = $component->payrollComponent;
                if (!$payrollComponent) {
                    continue;
                }

                $monthlyAmount = (float) $component->amount;
                $payableAmount = round($monthlyAmount * $factor, 2);
                $item->components()->create([
                    'payroll_component_id' => $payrollComponent->id,
                    'name' => $payrollComponent->name,
                    'code' => $payrollComponent->code,
                    'type' => $payrollComponent->type,
                    'monthly_amount' => $monthlyAmount,
                    'payable_amount' => $payableAmount,
                ]);

                if ($payrollComponent->type === 'earning') {
                    $gross += $payableAmount;
                } else {
                    $deductions += $payableAmount;
                }
            }

            $item->update([
                'gross_earnings' => $gross,
                'total_deductions' => $deductions,
                'net_salary' => $gross - $deductions,
            ]);
        }

        $run->update([
            'employee_count' => $run->items()->count(),
            'total_gross' => $run->items()->sum('gross_earnings'),
            'total_deductions' => $run->items()->sum('total_deductions'),
            'total_net' => $run->items()->sum('net_salary'),
        ]);

        return $run;
    }
}
