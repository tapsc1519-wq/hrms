@extends('layouts.app')

@section('title', 'Payroll Run')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <a href="{{ route('admin.payroll.runs') }}" class="back-link"><i class="bi bi-arrow-left"></i> Payroll Runs</a>
        <h4>{{ \Carbon\Carbon::createFromFormat('Y-m', $run->month)->format('F Y') }} Payroll</h4>
        <p>{{ $run->employee_count }} employees · Generated {{ $run->generated_at?->format('d-m-Y h:i A') }} by {{ $run->generator?->name ?? 'System' }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.payroll.runs.export', $run) }}" class="btn btn-outline-success">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
        @if(in_array($run->status, ['approved', 'paid'], true))
            <a href="{{ route('admin.payroll.runs.bank-payment', $run) }}" class="btn btn-outline-primary">
                <i class="bi bi-bank me-1"></i> Bank Payment CSV
            </a>
        @endif
        @if($run->status === 'draft')
            <form method="POST" action="{{ route('admin.payroll.runs.approve', $run) }}" onsubmit="return confirm('Approve this payroll run? Employees will be able to view payslips.')">
                @csrf
                @method('PATCH')
                <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Approve Payroll</button>
            </form>
            <form method="POST" action="{{ route('admin.payroll.runs.destroy', $run) }}" onsubmit="return confirm('Delete this draft payroll run?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> Delete Draft</button>
            </form>
        @elseif($run->status === 'approved')
            <form method="POST" action="{{ route('admin.payroll.runs.paid', $run) }}" onsubmit="return confirm('Mark this payroll run as paid?')">
                @csrf
                @method('PATCH')
                <button class="btn btn-primary"><i class="bi bi-cash-coin me-1"></i> Mark Paid</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-gradient grad-blue p-3">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value">{{ $run->employee_count }}</div>
            <div class="stat-label">Employees</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-green p-3">
            <div class="stat-icon"><i class="bi bi-plus-circle-fill"></i></div>
            <div class="stat-value">&#8377;{{ number_format((float) $run->total_gross, 0) }}</div>
            <div class="stat-label">Gross Earnings</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-orange p-3">
            <div class="stat-icon"><i class="bi bi-dash-circle-fill"></i></div>
            <div class="stat-value">&#8377;{{ number_format((float) $run->total_deductions, 0) }}</div>
            <div class="stat-label">Deductions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-purple p-3">
            <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-value">&#8377;{{ number_format((float) $run->total_net, 0) }}</div>
            <div class="stat-label">Net Pay</div>
        </div>
    </div>
</div>

@if($run->notes)
    <div class="alert alert-light border">{{ $run->notes }}</div>
@endif

@php
    $missingBankCount = $run->items->filter(function ($item) {
        $employee = $item->employee;
        return blank($employee?->bank_name)
            || blank($employee?->bank_account_name)
            || blank($employee?->bank_account_number)
            || blank($employee?->ifsc_code);
    })->count();
@endphp

@if($missingBankCount)
    <div class="alert alert-warning border-0">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        {{ $missingBankCount }} employee(s) have incomplete bank details. The bank payment CSV will still export, but those rows will be marked as incomplete.
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="alert alert-light border h-100 mb-0">
            <div class="text-muted small">Generated</div>
            <div class="fw-bold">{{ $run->generator?->name ?? 'System' }}</div>
            <div class="small text-muted">{{ $run->generated_at?->format('d-m-Y h:i A') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="alert alert-light border h-100 mb-0">
            <div class="text-muted small">Approved</div>
            <div class="fw-bold">{{ $run->approver?->name ?? 'Pending' }}</div>
            <div class="small text-muted">{{ $run->approved_at?->format('d-m-Y h:i A') ?? 'Not approved yet' }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="alert alert-light border h-100 mb-0">
            <div class="text-muted small">Paid</div>
            <div class="fw-bold">{{ $run->payer?->name ?? 'Pending' }}</div>
            <div class="small text-muted">{{ $run->paid_at?->format('d-m-Y h:i A') ?? 'Not paid yet' }}</div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Employee Payroll Details</span>
        <span class="badge bg-{{ $run->status_badge }}">{{ ucfirst($run->status) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Employee</th>
                    <th>Attendance</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Deductions</th>
                    <th class="text-end">Net</th>
                    <th class="pe-4 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($run->items as $item)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $item->employee?->user?->name }}</div>
                            <div class="text-muted small">
                                {{ $item->employee?->employee_code ?? 'No code' }}
                                @if($item->employee?->user?->department)
                                    · {{ $item->employee->user->department->name }}
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ number_format((float) $item->payable_days, 2) }} / {{ $item->days_in_month }} payable</div>
                            <div class="text-muted small">
                                P {{ $item->present_days }} · L {{ $item->leave_days }} · H {{ $item->holiday_days }} · WO {{ $item->weekly_off_days }}
                            </div>
                        </td>
                        <td class="text-end">&#8377;{{ number_format((float) $item->gross_earnings, 2) }}</td>
                        <td class="text-end">&#8377;{{ number_format((float) $item->total_deductions, 2) }}</td>
                        <td class="text-end fw-bold">&#8377;{{ number_format((float) $item->net_salary, 2) }}</td>
                        <td class="pe-4 text-end">
                            @if($item->remarks)
                                <span class="badge bg-warning text-dark">{{ $item->remarks }}</span>
                            @endif
                            <div class="d-inline-flex gap-1 ms-1">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#components{{ $item->id }}">
                                    Components
                                </button>
                                <a href="{{ route('admin.payroll.payslip', [$run, $item]) }}" class="btn btn-sm btn-outline-primary">
                                    Payslip
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr class="collapse" id="components{{ $item->id }}">
                        <td colspan="6" class="bg-light px-4">
                            <div class="row g-2 py-3">
                                @foreach($item->components as $component)
                                    <div class="col-md-4">
                                        <div class="border rounded-3 bg-white p-3 h-100">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $component->name }}</strong>
                                                <span class="badge bg-{{ $component->type === 'earning' ? 'success' : 'danger' }}">{{ ucfirst($component->type) }}</span>
                                            </div>
                                            <div class="text-muted small">{{ $component->code }}</div>
                                            <div class="mt-2 d-flex justify-content-between small">
                                                <span>Monthly: &#8377;{{ number_format((float) $component->monthly_amount, 2) }}</span>
                                                <strong>Payable: &#8377;{{ number_format((float) $component->payable_amount, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
