@extends('layouts.app')

@section('title', 'Payroll Runs')

@push('styles')
<style>
    .payroll-ready-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .055);
        overflow: hidden;
    }
    .ready-header {
        align-items: center;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-bottom: 1px solid #eef2f7;
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        justify-content: space-between;
        padding: .72rem .9rem;
    }
    .ready-body {
        padding: .85rem .95rem .95rem;
    }
    .ready-summary-grid,
    .ready-check-grid {
        display: grid;
        gap: .55rem;
    }
    .ready-summary-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .ready-check-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .ready-check {
        align-items: flex-start;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        display: flex;
        gap: .55rem;
        min-height: 78px;
        padding: .62rem;
    }
    .ready-check.ready-pass {
        background: #f8fffb;
        border-color: #dcfce7;
    }
    .ready-check.ready-fail {
        background: #fffbeb;
        border-color: #fde68a;
    }
    .ready-icon {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        flex-shrink: 0;
        font-size: .72rem;
        height: 24px;
        justify-content: center;
        margin-top: .05rem;
        width: 24px;
    }
    .ready-title { color: #0f172a; font-size: .78rem; font-weight: 800; line-height: 1.25; }
    .ready-help { color: #64748b; font-size: .68rem; line-height: 1.35; margin-top: .12rem; }
    .ready-stat {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: .55rem .65rem;
    }
    .ready-stat-value { color: #0f172a; font-size: .98rem; font-weight: 850; line-height: 1.1; }
    .ready-stat-label {
        color: #64748b;
        font-size: .62rem;
        font-weight: 800;
        letter-spacing: .04em;
        margin-top: .18rem;
        text-transform: uppercase;
    }
    .ready-fix-btn {
        border-radius: 7px;
        font-size: .68rem;
        line-height: 1.1;
        padding: .22rem .48rem;
    }
    .ready-missing-alert {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-top: .65rem;
        padding: .6rem .72rem;
    }
    @media (max-width: 991.98px) {
        .ready-summary-grid,
        .ready-check-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        .ready-summary-grid,
        .ready-check-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h4>Payroll Runs</h4>
        <p>Generate monthly payroll from locked attendance and active salary structures.</p>
    </div>
    <a href="{{ route('admin.payroll.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-sliders me-1"></i> Salary Setup
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4">
        <div class="form-card h-100 mb-0">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-play-fill"></i></span>
                Generate Payroll
            </div>
            <div class="form-card-body">
                <form method="GET" action="{{ route('admin.payroll.runs') }}" class="mb-3">
                    <label class="form-label">Payroll Month</label>
                    <div class="input-group">
                        <input type="month" name="month" value="{{ $month }}" class="form-control">
                        <button class="btn btn-outline-primary">Check</button>
                    </div>
                </form>

                @if($attendanceLock)
                    <div class="alert alert-success border-0">
                        <div class="fw-bold"><i class="bi bi-lock-fill me-1"></i> Attendance Locked</div>
                        <div class="small">Locked by {{ $attendanceLock->locker?->name ?? 'System' }} on {{ $attendanceLock->locked_at?->format('d-m-Y h:i A') }}</div>
                    </div>
                @else
                    <div class="alert alert-warning border-0">
                        <div class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Attendance Not Locked</div>
                        <div class="small">Lock attendance summary for {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }} before generating payroll.</div>
                        <a href="{{ route('admin.attendance.summary', ['month' => $month]) }}" class="btn btn-sm btn-outline-warning mt-2">
                            <i class="bi bi-calendar-check me-1"></i> Go to Attendance Summary
                        </a>
                    </div>
                @endif

                @if($existingRun)
                    <div class="alert alert-info border-0">
                        <div class="fw-bold">Payroll already generated.</div>
                        <a href="{{ route('admin.payroll.runs.show', $existingRun) }}" class="alert-link">Open existing run</a>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.payroll.runs.generate') }}">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control mb-3" rows="3" placeholder="Optional payroll processing note"></textarea>
                        <button class="btn btn-primary w-100" @disabled(!$readiness['is_ready'])>
                            <i class="bi bi-calculator-fill me-1"></i> Generate Payroll
                        </button>
                        @unless($readiness['is_ready'])
                            <div class="form-text text-danger mt-2">
                                Resolve failed pre-checks before generating payroll.
                            </div>
                        @endunless
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="payroll-ready-card mb-3">
            <div class="ready-header">
                <div>
                    <div class="fw-bold" style="font-size:.9rem;color:#0f172a">Payroll Readiness</div>
                    <div class="text-muted" style="font-size:.74rem">Pre-check for {{ $readiness['month_label'] }}</div>
                </div>
                <span class="badge bg-{{ $readiness['is_ready'] ? 'success' : 'warning' }}" style="font-size:.72rem">
                    {{ $readiness['is_ready'] ? 'Ready to Generate' : 'Action Required' }}
                </span>
            </div>
            <div class="ready-body">
                <div class="ready-summary-grid mb-3">
                    <div class="ready-stat">
                        <div class="ready-stat-value">{{ $readiness['active_employees'] }}</div>
                        <div class="ready-stat-label">Active Employees</div>
                    </div>
                    <div class="ready-stat">
                        <div class="ready-stat-value">{{ $readiness['salary_ready'] }}</div>
                        <div class="ready-stat-label">Salary Ready</div>
                    </div>
                    <div class="ready-stat">
                        <div class="ready-stat-value">{{ $readiness['pending_leaves'] }}</div>
                        <div class="ready-stat-label">Pending Leaves</div>
                    </div>
                    <div class="ready-stat">
                        <div class="ready-stat-value">{{ $readiness['pending_regularizations'] }}</div>
                        <div class="ready-stat-label">Corrections</div>
                    </div>
                </div>

                <div class="ready-check-grid">
                    @foreach($readiness['checks'] as $check)
                        <div class="ready-check {{ $check['passed'] ? 'ready-pass' : 'ready-fail' }}">
                            <span class="ready-icon bg-{{ $check['passed'] ? 'success' : 'warning' }} text-white">
                                <i class="bi bi-{{ $check['passed'] ? 'check-lg' : 'exclamation-triangle-fill' }}"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="ready-title">{{ $check['label'] }}</div>
                                        <div class="ready-help">{{ $check['help'] }}</div>
                                    </div>
                                    @unless($check['passed'])
                                        <a href="{{ $check['route'] }}" class="btn btn-outline-primary ready-fix-btn">Fix</a>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($readiness['missing_salary'] > 0)
                    <div class="ready-missing-alert">
                        <div class="fw-bold mb-1" style="font-size:.82rem">Employees missing salary setup</div>
                        <div class="text-muted" style="font-size:.76rem">
                            {{ $readiness['missing_salary_employees']->map(fn($employee) => trim(($employee->employee_code ? $employee->employee_code . ' - ' : '') . ($employee->user?->name ?? 'Employee')))->implode(', ') }}
                            @if($readiness['missing_salary'] > $readiness['missing_salary_employees']->count())
                                and {{ $readiness['missing_salary'] - $readiness['missing_salary_employees']->count() }} more.
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="stat-card-gradient grad-blue p-3">
                    <div class="stat-icon"><i class="bi bi-receipt-cutoff"></i></div>
                    <div class="stat-value">{{ $runs->total() }}</div>
                    <div class="stat-label">Total Runs</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-gradient grad-green p-3">
                    <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
                    <div class="stat-value">&#8377;{{ number_format((float) $runs->sum('total_net'), 0) }}</div>
                    <div class="stat-label">Listed Net Pay</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-gradient grad-purple p-3">
                    <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-value">{{ $runs->sum('employee_count') }}</div>
                    <div class="stat-label">Listed Employees</div>
                </div>
            </div>
        </div>

        <div class="table-card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Generated Payroll</span>
                <span class="badge bg-primary">{{ $runs->total() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc">
                        <tr>
                            <th class="ps-4">Month</th>
                            <th>Employees</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Net Pay</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($runs as $run)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ \Carbon\Carbon::createFromFormat('Y-m', $run->month)->format('F Y') }}</div>
                                    <div class="text-muted small">Generated {{ $run->generated_at?->format('d-m-Y h:i A') }}</div>
                                </td>
                                <td>{{ $run->employee_count }}</td>
                                <td class="text-end">&#8377;{{ number_format((float) $run->total_gross, 2) }}</td>
                                <td class="text-end">&#8377;{{ number_format((float) $run->total_deductions, 2) }}</td>
                                <td class="text-end fw-bold">&#8377;{{ number_format((float) $run->total_net, 2) }}</td>
                                <td><span class="badge bg-{{ $run->status_badge }}">{{ ucfirst($run->status) }}</span></td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('admin.payroll.runs.show', $run) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No payroll runs generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($runs->hasPages())
                <div class="p-3 border-top">{{ $runs->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
