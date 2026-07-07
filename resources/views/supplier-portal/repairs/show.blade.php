@extends('layouts.app')

@section('title', $repair->repair_number)

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('supplier.repairs.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Repair Jobs</a>
        <h4>{{ $repair->repair_number }}</h4>
        <p>{{ $repair->asset?->name }} - {{ $repair->asset?->asset_tag }}</p>
    </div>
    <span class="badge bg-{{ $repair->status_badge }} fs-6">{{ $repair->status_label }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-wrench-adjustable"></i></span>Repair Update</div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('supplier.repairs.update', $repair) }}">
                    @csrf
                    @method('PATCH')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach(['diagnosis_pending','estimate_received','repair_in_progress','repaired'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $repair->status) === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected Return</label>
                            <input type="date" name="expected_return_date" class="form-control" value="{{ old('expected_return_date', $repair->expected_return_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Completed Date</label>
                            <input type="date" name="completed_date" class="form-control" value="{{ old('completed_date', $repair->completed_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" rows="4" placeholder="Fault identified, tests performed, root cause.">{{ old('diagnosis', $repair->diagnosis) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Work Performed</label>
                            <textarea name="work_performed" class="form-control" rows="4" placeholder="Repair actions completed, replaced parts, final result.">{{ old('work_performed', $repair->work_performed) }}</textarea>
                        </div>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Changed Part</th>
                                    <th>Part No.</th>
                                    <th style="width:90px">Qty</th>
                                    <th style="width:140px">Unit Cost</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $parts = old('parts', $repair->parts->map(fn($part) => $part->only(['part_name','part_number','quantity','unit_cost','notes']))->toArray()); @endphp
                                @for($i = 0; $i < 5; $i++)
                                    @php $part = $parts[$i] ?? []; @endphp
                                    <tr>
                                        <td><input type="text" name="parts[{{ $i }}][part_name]" class="form-control form-control-sm" value="{{ $part['part_name'] ?? '' }}"></td>
                                        <td><input type="text" name="parts[{{ $i }}][part_number]" class="form-control form-control-sm" value="{{ $part['part_number'] ?? '' }}"></td>
                                        <td><input type="number" min="1" name="parts[{{ $i }}][quantity]" class="form-control form-control-sm" value="{{ $part['quantity'] ?? 1 }}"></td>
                                        <td><input type="number" min="0" step="0.01" name="parts[{{ $i }}][unit_cost]" class="form-control form-control-sm" value="{{ $part['unit_cost'] ?? '' }}"></td>
                                        <td><input type="text" name="parts[{{ $i }}][notes]" class="form-control form-control-sm" value="{{ $part['notes'] ?? '' }}"></td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-3">
                            <label class="form-label">Service Cost</label>
                            <input type="number" min="0" step="0.01" name="service_cost" class="form-control" value="{{ old('service_cost', $repair->service_cost) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tax</label>
                            <input type="number" min="0" step="0.01" name="tax_amount" class="form-control" value="{{ old('tax_amount', $repair->tax_amount) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discount</label>
                            <input type="number" min="0" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount', $repair->discount_amount) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice No.</label>
                            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $repair->invoice_number) }}">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('supplier.repairs.index') }}" class="btn-cancel">Back</a>
                        <button type="submit" class="btn btn-primary btn-save"><i class="bi bi-check2-circle"></i>Update Repair</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-teal"><i class="bi bi-box-seam"></i></span>Asset Details</div>
            <div class="form-card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Asset</dt><dd class="col-7">{{ $repair->asset?->name }}</dd>
                    <dt class="col-5 text-muted">Tag</dt><dd class="col-7">{{ $repair->asset?->asset_tag }}</dd>
                    <dt class="col-5 text-muted">Serial</dt><dd class="col-7">{{ $repair->asset?->serial_number ?: 'Not set' }}</dd>
                    <dt class="col-5 text-muted">Assigned To</dt><dd class="col-7">{{ $repair->assignment?->user?->name ?? 'Stock asset' }}</dd>
                    <dt class="col-5 text-muted">Repair Type</dt><dd class="col-7">{{ $repair->repair_type_label }}</dd>
                    <dt class="col-5 text-muted">Total</dt><dd class="col-7 fw-bold">&#8377;{{ number_format((float) $repair->total_cost, 2) }}</dd>
                </dl>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-amber"><i class="bi bi-card-text"></i></span>Issue Summary</div>
            <div class="form-card-body small">
                {{ $repair->issue_summary }}
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-paperclip"></i></span>Documents</div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('supplier.repairs.attachments.store', $repair) }}" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Document Type</label>
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="invoice">Invoice</option>
                            <option value="estimate">Repair Estimate</option>
                            <option value="repair_photo">Repair Photo</option>
                            <option value="supporting_document">Supporting Document</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Files</label>
                        <input type="file" name="files[]" class="form-control form-control-sm" multiple required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt,.zip">
                        <div class="form-text">Upload up to 5 files, 10 MB each.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-upload me-1"></i>Upload</button>
                </form>

                @forelse($repair->attachments as $attachment)
                    <div class="border rounded-3 p-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ $attachment->url }}" target="_blank" class="fw-bold text-decoration-none text-truncate d-block">{{ $attachment->original_name }}</a>
                                <div class="text-muted small">{{ $attachment->type_label }} - {{ $attachment->file_size_human }}</div>
                            </div>
                            @if($attachment->uploaded_by === auth()->id())
                            <form method="POST" action="{{ route('supplier.repairs.attachments.destroy', [$repair, $attachment]) }}" onsubmit="return confirm('Remove this document?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-3 small">No documents uploaded yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
