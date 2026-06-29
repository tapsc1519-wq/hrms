@extends('layouts.app')
@section('title', 'License — ' . optional($softwareLicense->software)->name)

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.software.show', $softwareLicense->software_id) }}" class="back-link">
            <i class="bi bi-arrow-left"></i> {{ optional($softwareLicense->software)->name }}
        </a>
        <h4>License Record</h4>
        <p>
            <span class="badge bg-{{ $softwareLicense->status_badge }} me-1">{{ $softwareLicense->status_label }}</span>
            {{ $softwareLicense->license_type_label }} ·
            {{ $softwareLicense->seats }} {{ Str::plural('seat', $softwareLicense->seats) }}
        </p>
    </div>
    <div class="d-flex gap-2">
        @if($softwareLicense->purchaseOrder)
        <a href="{{ route('admin.purchase-orders.show', $softwareLicense->purchaseOrder) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-cart-check me-1"></i>View Source PO
        </a>
        @endif
        <form method="POST" action="{{ route('admin.software-licenses.destroy', $softwareLicense) }}"
              onsubmit="return confirm('Delete this license record? All assignment history will be lost.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
        </form>
    </div>
</div>

<div class="row g-4">
    {{-- Left column --}}
    <div class="col-lg-7">

        {{-- Compliance banner --}}
        @if($softwareLicense->is_over_licensed)
        <div class="alert alert-danger border-0 rounded-3 d-flex align-items-center gap-2 mb-3" style="font-size:.875rem">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><strong>Over-licensed!</strong> This license has {{ $softwareLicense->used_seats - $softwareLicense->seats }} more active user(s) than seats purchased. Review assignments below.</div>
        </div>
        @elseif($softwareLicense->is_expiring_soon)
        <div class="alert alert-warning border-0 rounded-3 d-flex align-items-center gap-2 mb-3" style="font-size:.875rem">
            <i class="bi bi-clock-history fs-5"></i>
            <div><strong>Expiring {{ $softwareLicense->expiry_date->diffForHumans() }}!</strong> Renew or cancel this license before {{ $softwareLicense->expiry_date->format('d-m-Y') }}.</div>
        </div>
        @endif

        {{-- License details --}}
        <div class="table-card mb-4">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>License Details</div>
            <div class="card-body">
                <div class="row g-3">
                    @php
                    $details = [
                        ['Software',      optional($softwareLicense->software)->name ?? '—'],
                        ['License Type',  $softwareLicense->license_type_label],
                        ['Total Seats',   $softwareLicense->seats],
                        ['Used Seats',    $softwareLicense->used_seats],
                        ['Available',     $softwareLicense->available_seats],
                        ['Supplier',        optional($softwareLicense->supplier)->name ?? '—'],
                        ['Purchase Batch', $softwareLicense->purchase_batch ?? '—'],
                        ['PO Number',     $softwareLicense->po_number ?? '—'],
                        ['Invoice Number', $softwareLicense->invoice_number ?? '—'],
                        ['Agreement Number', $softwareLicense->agreement_number ?? '—'],
                        ['Purchase Date', $softwareLicense->purchase_date ? $softwareLicense->purchase_date->format('d-m-Y') : '—'],
                        ['Expiry Date',   $softwareLicense->expiry_date ? $softwareLicense->expiry_date->format('d-m-Y') : '—'],
                        ['Renewal Date',  $softwareLicense->renewal_date ? $softwareLicense->renewal_date->format('d-m-Y') : '—'],
                        ['Unit Cost',     $softwareLicense->unit_cost ? 'Rs. '.number_format((float) $softwareLicense->unit_cost, 2) : '—'],
                        ['Purchase Price', $softwareLicense->total_cost ? 'Rs. '.number_format($softwareLicense->total_cost, 2) : '—'],
                        ['Renewal Recommendation', $softwareLicense->renewal_recommendation_label],
                    ];
                    @endphp
                    @foreach($details as [$label, $value])
                    <div class="col-6">
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:.2rem">{{ $label }}</div>
                        <div style="font-size:.875rem;color:#1e293b;font-weight:500">{{ $value }}</div>
                    </div>
                    @endforeach
                    @if($softwareLicense->license_key)
                    <div class="col-12">
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:.2rem">License Key</div>
                        <code style="font-size:.82rem;color:#1e293b;background:#f1f5f9;padding:4px 8px;border-radius:6px;display:inline-block">{{ $softwareLicense->license_key }}</code>
                    </div>
                    @endif
                    @if($softwareLicense->evidence_document)
                    <div class="col-12">
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:.2rem">Evidence Document</div>
                        <a href="{{ Storage::url($softwareLicense->evidence_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-paperclip me-1"></i>Open Evidence
                        </a>
                    </div>
                    @endif
                    @if($softwareLicense->notes)
                    <div class="col-12">
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:.2rem">Notes</div>
                        <div style="font-size:.875rem;color:#475569">{{ $softwareLicense->notes }}</div>
                    </div>
                    @endif
                </div>

                {{-- Seat usage bar --}}
                @php $pct = $softwareLicense->seats > 0 ? min(100, round($softwareLicense->used_seats/$softwareLicense->seats*100)) : 0; @endphp
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;color:#64748b">
                        <span>Seat Utilisation</span>
                        <span class="fw-600">{{ $softwareLicense->used_seats }} / {{ $softwareLicense->seats }} ({{ $pct }}%)</span>
                    </div>
                    <div class="progress" style="height:8px;border-radius:99px">
                        <div class="progress-bar {{ $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success') }}"
                             style="width:{{ $pct }}%;border-radius:99px"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Assignments --}}
        <div class="table-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="bi bi-people-fill me-2 text-primary"></i>Active Assignments</div>
                <span class="badge bg-primary rounded-pill">{{ $softwareLicense->activeAssignments->count() }}</span>
            </div>
            <div class="table-responsive">
                @if($softwareLicense->assignments->where('status','active')->isEmpty())
                    <div class="text-center py-4 text-muted" style="font-size:.875rem">
                        No active assignments. Use the form to assign this license.
                    </div>
                @else
                    <table class="table table-hover mb-0" style="font-size:.855rem">
                        <thead style="background:#f8fafc;border-bottom:2px solid #e9ecef">
                            <tr>
                                <th class="px-4 py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Employee</th>
                                <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Assigned Date</th>
                                <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Assigned By</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($softwareLicense->assignments->where('status','active') as $asgn)
                            <tr style="border-bottom:1px solid #f1f5f9">
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#8b5cf6);
                                                    display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:700;flex-shrink:0">
                                            {{ strtoupper(substr(optional($asgn->user)->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-600" style="color:#1e293b">{{ optional($asgn->user)->name }}</div>
                                            <div class="text-muted" style="font-size:.72rem">{{ optional($asgn->user)->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">{{ $asgn->assigned_date->format('d-m-Y') }}</td>
                                <td class="py-3">{{ optional($asgn->assignedBy)->name ?? '—' }}</td>
                                <td class="py-3 pe-3">
                                    <form method="POST"
                                          action="{{ route('admin.software-licenses.return', [$softwareLicense, $asgn]) }}"
                                          onsubmit="return confirm('Return license from {{ addslashes(optional($asgn->user)->name) }}?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-warning" style="font-size:.75rem">
                                            <i class="bi bi-arrow-return-left me-1"></i>Return
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Return History --}}
        @if($softwareLicense->assignments->where('status','returned')->isNotEmpty())
        <div class="table-card mt-4">
            <div class="card-header"><i class="bi bi-clock-history me-2 text-muted"></i>Return History</div>
            <div class="table-responsive">
                <table class="table mb-0" style="font-size:.855rem">
                    <thead style="background:#f8fafc;border-bottom:2px solid #e9ecef">
                        <tr>
                            <th class="px-4 py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Employee</th>
                            <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Assigned</th>
                            <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Returned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($softwareLicense->assignments->where('status','returned') as $asgn)
                        <tr style="border-bottom:1px solid #f1f5f9">
                            <td class="px-4 py-2">
                                <span class="text-muted">{{ optional($asgn->user)->name }}</span>
                            </td>
                            <td class="py-2 text-muted">{{ $asgn->assigned_date->format('d-m-Y') }}</td>
                            <td class="py-2 text-muted">{{ $asgn->returned_date?->format('d-m-Y') ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- Right column — Assign form --}}
    <div class="col-lg-5">
        @if(auth()->user()->hasPermission('software.manage'))
        <div class="form-card mb-4">
            <div class="form-card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-file-earmark-check-fill me-2 text-primary"></i>Evidence Quality</span>
                <span class="badge bg-{{ $softwareLicense->evidence_badge }}">{{ $softwareLicense->evidence_score }}%</span>
            </div>
            <div class="form-card-body">
                @if(count($softwareLicense->evidence_issues))
                    <div class="alert alert-warning small border-0 mb-3">
                        <div class="fw-bold mb-1">Missing for audit readiness</div>
                        {{ implode(', ', $softwareLicense->evidence_issues) }}
                    </div>
                @else
                    <div class="alert alert-success small border-0 mb-3">
                        <i class="bi bi-check-circle me-1"></i>This license has complete evidence for the SAM Audit Pack.
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.software-licenses.evidence.update', $softwareLicense) }}" enctype="multipart/form-data">
                    @csrf @method('PATCH')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
                                <option value="">No supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('vendor_id', $softwareLicense->vendor_id) == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PO Number</label>
                            <input type="text" name="po_number" value="{{ old('po_number', $softwareLicense->po_number) }}" class="form-control @error('po_number') is-invalid @enderror">
                            @error('po_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" name="invoice_number" value="{{ old('invoice_number', $softwareLicense->invoice_number) }}" class="form-control @error('invoice_number') is-invalid @enderror">
                            @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Agreement Number</label>
                            <input type="text" name="agreement_number" value="{{ old('agreement_number', $softwareLicense->agreement_number) }}" class="form-control @error('agreement_number') is-invalid @enderror">
                            @error('agreement_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Cost</label>
                            <input type="number" name="unit_cost" value="{{ old('unit_cost', $softwareLicense->unit_cost) }}" min="0" step="0.01" class="form-control @error('unit_cost') is-invalid @enderror">
                            @error('unit_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purchase Price</label>
                            <input type="number" name="purchase_price" value="{{ old('purchase_price', $softwareLicense->purchase_price) }}" min="0" step="0.01" class="form-control @error('purchase_price') is-invalid @enderror">
                            @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date" value="{{ old('purchase_date', $softwareLicense->purchase_date?->toDateString()) }}" class="form-control @error('purchase_date') is-invalid @enderror">
                            @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" value="{{ old('expiry_date', $softwareLicense->expiry_date?->toDateString()) }}" class="form-control @error('expiry_date') is-invalid @enderror">
                            @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Renewal Date</label>
                            <input type="date" name="renewal_date" value="{{ old('renewal_date', $softwareLicense->renewal_date?->toDateString()) }}" class="form-control @error('renewal_date') is-invalid @enderror">
                            @error('renewal_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Evidence Document</label>
                            <input type="file" name="evidence_document" class="form-control @error('evidence_document') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <div class="form-text">Upload PO, invoice or agreement. PDF, image or Word file up to 5 MB.</div>
                            @error('evidence_document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @if($softwareLicense->evidence_document)
                        <div class="col-12 d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <a href="{{ Storage::url($softwareLicense->evidence_document) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip me-1"></i>Open Current Evidence</a>
                            <label class="form-check m-0">
                                <input type="checkbox" name="remove_evidence_document" value="1" class="form-check-input">
                                <span class="form-check-label small text-muted">Remove current file</span>
                            </label>
                        </div>
                        @endif
                    </div>
                    <button class="btn btn-primary w-100 mt-3"><i class="bi bi-save me-1"></i>Update Evidence</button>
                </form>
            </div>
        </div>
        @endif

        @if($softwareLicense->status === 'active' && !$softwareLicense->is_expired)
        <div class="form-card">
            <div class="form-card-header">
                <div class="icon-wrap icon-blue"><i class="bi bi-person-plus-fill"></i></div>
                Assign to Employee
            </div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('admin.software-licenses.assign', $softwareLicense) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Employee <span class="req">*</span></label>
                    <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                        <option value="">Select employee…</option>
                        @foreach($employees as $emp)
                            @php $alreadyActive = $softwareLicense->assignments->where('status','active')->where('user_id',$emp->id)->isNotEmpty(); @endphp
                            <option value="{{ $emp->id }}" @selected(old('user_id') == $emp->id)
                                    {{ $alreadyActive ? 'disabled' : '' }}>
                                {{ $emp->name }}{{ $alreadyActive ? ' (already assigned)' : '' }}
                                @if($emp->job_title) — {{ $emp->job_title }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Assign Date <span class="req">*</span></label>
                    <input type="date" name="assigned_date" value="{{ old('assigned_date', now()->toDateString()) }}"
                           class="form-control @error('assigned_date') is-invalid @enderror" required>
                    @error('assigned_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" class="form-control" placeholder="Optional notes…">{{ old('notes') }}</textarea>
                </div>

                {{-- Seat check --}}
                @if($softwareLicense->available_seats <= 0)
                <div class="alert alert-warning border-0 rounded-3 py-2 px-3 mb-3" style="font-size:.8rem">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    No available seats. Assigning will put this license over its seat limit.
                </div>
                @else
                <div class="alert alert-success border-0 rounded-3 py-2 px-3 mb-3" style="font-size:.8rem;background:#f0fdf4">
                    <i class="bi bi-check-circle me-1 text-success"></i>
                    <span class="text-success fw-600">{{ $softwareLicense->available_seats }}</span> seat(s) available.
                </div>
                @endif

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-person-plus me-1"></i>Assign License
                </button>
                </form>
            </div>
        </div>
        @else
        <div class="form-card">
            <div class="form-card-body text-center py-4">
                <i class="bi bi-lock-fill" style="font-size:2rem;color:#cbd5e1"></i>
                <div class="mt-2 text-muted">This license is {{ $softwareLicense->is_expired ? 'expired' : 'inactive' }} and cannot be assigned.</div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
