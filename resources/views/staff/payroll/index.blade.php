@extends('layouts.app')

@section('title', 'My Payslips')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h4>My Payslips</h4>
        <p>View approved and paid salary slips shared by HR.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card-gradient grad-blue p-3">
            <div class="stat-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="stat-value">{{ $payslips->total() }}</div>
            <div class="stat-label">Payslips</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-gradient grad-green p-3">
            <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-value">&#8377;{{ number_format((float) $payslips->sum('net_salary'), 0) }}</div>
            <div class="stat-label">Listed Net Pay</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-gradient grad-purple p-3">
            <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
            <div class="stat-value">{{ $employee->employee_code ?? 'NA' }}</div>
            <div class="stat-label">Employee Code</div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Salary Slips</span>
        <span class="badge bg-primary">{{ $payslips->total() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Month</th>
                    <th>Payable Days</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Deductions</th>
                    <th class="text-end">Net</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payslips as $item)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ \Carbon\Carbon::createFromFormat('Y-m', $item->run->month)->format('F Y') }}</div>
                            <div class="small text-muted">Generated {{ $item->run->generated_at?->format('d-m-Y') }}</div>
                        </td>
                        <td>{{ number_format((float) $item->payable_days, 2) }} / {{ $item->days_in_month }}</td>
                        <td class="text-end">&#8377;{{ number_format((float) $item->gross_earnings, 2) }}</td>
                        <td class="text-end">&#8377;{{ number_format((float) $item->total_deductions, 2) }}</td>
                        <td class="text-end fw-bold">&#8377;{{ number_format((float) $item->net_salary, 2) }}</td>
                        <td><span class="badge bg-{{ $item->run->status_badge }}">{{ ucfirst($item->run->status) }}</span></td>
                        <td class="text-end pe-4">
                            <a href="{{ route('staff.payslips.show', $item) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-receipt d-block fs-3 mb-2"></i>
                            No payslips are available yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payslips->hasPages())
        <div class="p-3 border-top">{{ $payslips->links() }}</div>
    @endif
</div>
@endsection
