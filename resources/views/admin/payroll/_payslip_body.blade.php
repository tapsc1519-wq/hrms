<div class="table-card payslip-sheet">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-bold">{{ $run->organization?->name ?? config('app.name') }}</div>
            <div class="text-muted small">Payslip for {{ \Carbon\Carbon::createFromFormat('Y-m', $run->month)->format('F Y') }}</div>
        </div>
        <span class="badge bg-{{ $run->status_badge }}">{{ ucfirst($run->status) }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="text-muted small">Employee</div>
                <div class="fw-bold">{{ $item->employee?->user?->name }}</div>
                <div class="small text-muted">{{ $item->employee?->employee_code ?? 'No code' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Department</div>
                <div class="fw-bold">{{ $item->employee?->user?->department?->name ?? 'Not assigned' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">PAN / UAN</div>
                <div class="fw-bold">{{ $item->employee?->pan_number ?: 'NA' }}</div>
                <div class="small text-muted">{{ $item->employee?->uan_number ?: 'UAN not set' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Payable Days</div>
                <div class="fw-bold">{{ number_format((float) $item->payable_days, 2) }} / {{ $item->days_in_month }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Bank</div>
                <div class="fw-bold">{{ $item->employee?->bank_name ?: 'Not set' }}</div>
                <div class="small text-muted">{{ $item->employee?->bank_account_number ?: 'Account not set' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Net Salary</div>
                <div class="fw-bold text-success">&#8377;{{ number_format((float) $item->net_salary, 2) }}</div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="border rounded-3 overflow-hidden">
                    <div class="px-3 py-2 bg-light fw-bold">Earnings</div>
                    <table class="table mb-0">
                        <tbody>
                            @forelse($earnings as $component)
                                <tr>
                                    <td>{{ $component->name }}</td>
                                    <td class="text-end">&#8377;{{ number_format((float) $component->payable_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center">No earnings</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Earnings</td>
                                <td class="text-end">&#8377;{{ number_format((float) $item->gross_earnings, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-3 overflow-hidden">
                    <div class="px-3 py-2 bg-light fw-bold">Deductions</div>
                    <table class="table mb-0">
                        <tbody>
                            @forelse($deductions as $component)
                                <tr>
                                    <td>{{ $component->name }}</td>
                                    <td class="text-end">&#8377;{{ number_format((float) $component->payable_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center">No deductions</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Deductions</td>
                                <td class="text-end">&#8377;{{ number_format((float) $item->total_deductions, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-success border-0 mt-4 d-flex justify-content-between align-items-center">
            <span class="fw-bold">Net Pay</span>
            <span class="fw-bold fs-5">&#8377;{{ number_format((float) $item->net_salary, 2) }}</span>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    #sidebar, #topbar, .page-header, .px-4.pt-3 { display: none !important; }
    #main-content { margin-left: 0 !important; }
    .content-area { padding: 0 !important; }
    body { background: #fff !important; }
    .payslip-sheet { box-shadow: none !important; border: 1px solid #e2e8f0; }
}
</style>
@endpush
