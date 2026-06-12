@extends('layouts.app')

@section('title', 'Salary Setup')

@php
    $earnings = $components->where('type', 'earning');
    $deductions = $components->where('type', 'deduction');
@endphp

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h4>Salary Setup</h4>
        <p>Configure salary components and employee monthly salary structures.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.payroll.runs') }}" class="btn btn-outline-secondary">
            <i class="bi bi-receipt-cutoff me-1"></i> Payroll Runs
        </a>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#componentModal">
            <i class="bi bi-plus-lg me-1"></i> Add Component
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#salaryModal">
            <i class="bi bi-person-plus-fill me-1"></i> Add Salary Structure
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-gradient grad-blue p-3">
            <div class="stat-icon"><i class="bi bi-list-check"></i></div>
            <div class="stat-value">{{ $components->count() }}</div>
            <div class="stat-label">Components</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-green p-3">
            <div class="stat-icon"><i class="bi bi-plus-circle-fill"></i></div>
            <div class="stat-value">{{ $earnings->count() }}</div>
            <div class="stat-label">Earnings</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-orange p-3">
            <div class="stat-icon"><i class="bi bi-dash-circle-fill"></i></div>
            <div class="stat-value">{{ $deductions->count() }}</div>
            <div class="stat-label">Deductions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-purple p-3">
            <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
            <div class="stat-value">{{ $structures->total() }}</div>
            <div class="stat-label">Salary Structures</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Salary Components</span>
                <span class="badge bg-primary">{{ $components->count() }}</span>
            </div>
            <div class="list-group list-group-flush">
                @forelse($components as $component)
                    <div class="list-group-item payroll-component-item px-4 py-3">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="payroll-component-name">{{ $component->name }}</div>
                                <div class="payroll-component-meta">{{ $component->code }} &middot; {{ ucfirst($component->type) }}</div>
                                @if($component->is_statutory)
                                    <span class="badge bg-info-subtle text-info mt-2">Statutory</span>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $component->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($component->status) }}</span>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editComponent{{ $component->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.payroll.components.destroy', $component) }}" class="d-inline" onsubmit="return confirm('Delete this component?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No payroll components configured.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Employee Salary Structures</span>
                <span class="badge bg-primary">{{ $structures->total() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc">
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Effective</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Net Salary</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($structures as $structure)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $structure->employee?->user?->name }}</div>
                                    <div class="text-muted small">
                                        {{ $structure->employee?->employee_code ?? 'No code' }}
                                        @if($structure->employee?->user?->department)
                                            &middot; {{ $structure->employee->user->department->name }}
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $structure->effective_from->format('d-m-Y') }}</td>
                                <td class="text-end">&#8377;{{ number_format((float) $structure->gross_earnings, 2) }}</td>
                                <td class="text-end">&#8377;{{ number_format((float) $structure->total_deductions, 2) }}</td>
                                <td class="text-end fw-bold">&#8377;{{ number_format((float) $structure->net_salary, 2) }}</td>
                                <td><span class="badge bg-{{ $structure->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($structure->status) }}</span></td>
                                <td class="text-end pe-4">
                                    <form method="POST" action="{{ route('admin.payroll.structures.destroy', $structure) }}" onsubmit="return confirm('Delete this salary structure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No salary structures created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($structures->hasPages())
                <div class="p-3 border-top">{{ $structures->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="componentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.payroll.components.store') }}" class="modal-content border-0" style="border-radius:16px">
            @csrf
            @include('admin.payroll._component_form', ['component' => null])
        </form>
    </div>
</div>

@foreach($components as $component)
    <div class="modal fade" id="editComponent{{ $component->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.payroll.components.update', $component) }}" class="modal-content border-0" style="border-radius:16px">
                @csrf
                @method('PATCH')
                @include('admin.payroll._component_form', ['component' => $component])
            </form>
        </div>
    </div>
@endforeach

<div class="modal fade" id="salaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" action="{{ route('admin.payroll.structures.store') }}" class="modal-content border-0" style="border-radius:16px">
            @csrf
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Add Salary Structure</h5>
                    <div class="text-muted small">Enter monthly earnings and deductions for an employee.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-5">
                        <label class="form-label">Employee <span class="req">*</span></label>
                        <select name="employee_profile_id" class="form-select" required>
                            <option value="">Select employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->user?->name }} &middot; {{ $employee->employee_code ?? 'No code' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Effective From <span class="req">*</span></label>
                        <input type="date" name="effective_from" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Currency</label>
                        <input type="text" class="form-control" value="INR &#8377;" disabled>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="form-card mb-0">
                            <div class="form-card-header"><span class="icon-wrap icon-green"><i class="bi bi-plus-circle-fill"></i></span> Earnings</div>
                            <div class="form-card-body">
                                @foreach($earnings->where('status', 'active') as $component)
                                    <div class="mb-3">
                                        <label class="form-label">{{ $component->name }}</label>
                                        <input type="number" min="0" step="0.01" name="components[{{ $component->id }}]" class="form-control payroll-amount earning-amount" placeholder="0.00">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-card mb-0">
                            <div class="form-card-header"><span class="icon-wrap icon-red"><i class="bi bi-dash-circle-fill"></i></span> Deductions</div>
                            <div class="form-card-body">
                                @foreach($deductions->where('status', 'active') as $component)
                                    <div class="mb-3">
                                        <label class="form-label">{{ $component->name }}</label>
                                        <input type="number" min="0" step="0.01" name="components[{{ $component->id }}]" class="form-control payroll-amount deduction-amount" placeholder="0.00">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border d-flex flex-wrap justify-content-between gap-3 mb-0">
                            <strong>Gross: &#8377;<span id="salaryGross">0.00</span></strong>
                            <strong>Deductions: &#8377;<span id="salaryDeductions">0.00</span></strong>
                            <strong>Net Salary: &#8377;<span id="salaryNet">0.00</span></strong>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional salary notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Salary Structure</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('input', function (event) {
    if (!event.target.classList.contains('payroll-amount')) return;

    const sum = selector => Array.from(document.querySelectorAll(selector))
        .reduce((total, input) => total + (parseFloat(input.value) || 0), 0);

    const gross = sum('.earning-amount');
    const deductions = sum('.deduction-amount');
    document.getElementById('salaryGross').textContent = gross.toFixed(2);
    document.getElementById('salaryDeductions').textContent = deductions.toFixed(2);
    document.getElementById('salaryNet').textContent = (gross - deductions).toFixed(2);
});
</script>
@endpush
