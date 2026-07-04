@extends('layouts.app')

@section('title', $assetRepair->repair_number)

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('admin.asset-repairs.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Asset Repairs</a>
        <h4>{{ $assetRepair->repair_number }}</h4>
        <p>{{ $assetRepair->asset->name }} · {{ $assetRepair->asset->asset_tag }}</p>
    </div>
    <span class="badge bg-{{ $assetRepair->status_badge }} fs-6">{{ $assetRepair->status_label }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-clipboard2-pulse"></i></span>Repair Workflow</div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('admin.asset-repairs.update', $assetRepair) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="asset_id" value="{{ $assetRepair->asset_id }}">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Repair Type</label>
                            <select name="repair_type" class="form-select" required>
                                @foreach(['internal' => 'Internal IT', 'amc' => 'AMC Vendor', 'vendor' => 'Onboarded Vendor', 'market' => 'Market Repair', 'warranty' => 'Warranty'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('repair_type', $assetRepair->repair_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select" required>
                                @foreach(['low','medium','high','critical'] as $priority)
                                    <option value="{{ $priority }}" @selected(old('priority', $assetRepair->priority) === $priority)>{{ ucfirst($priority) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach(['request_raised','under_review','approved','assigned_to_it','assigned_to_vendor','sent_for_repair','diagnosis_pending','estimate_received','estimate_approved','repair_in_progress','repaired','qc_pending','ready_to_return','rejected','not_repairable'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $assetRepair->status) === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select">
                                <option value="">Internal or market repair</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('vendor_id', $assetRepair->vendor_id) == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Only records added in Vendors will appear here.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">AMC Contract</label>
                            <select name="amc_contract_id" class="form-select">
                                <option value="">No AMC selected</option>
                                @foreach($amcContracts as $contract)
                                    <option value="{{ $contract->id }}" @selected(old('amc_contract_id', $assetRepair->amc_contract_id) == $contract->id)>{{ $contract->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Market Vendor</label>
                            <input type="text" name="market_vendor_name" class="form-control" value="{{ old('market_vendor_name', $assetRepair->market_vendor_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Market Contact</label>
                            <input type="text" name="market_vendor_contact" class="form-control" value="{{ old('market_vendor_contact', $assetRepair->market_vendor_contact) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Market Phone</label>
                            <input type="text" name="market_vendor_phone" class="form-control" value="{{ old('market_vendor_phone', $assetRepair->market_vendor_phone) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Requested</label>
                            <input type="date" name="requested_date" class="form-control" value="{{ old('requested_date', $assetRepair->requested_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sent</label>
                            <input type="date" name="sent_date" class="form-control" value="{{ old('sent_date', $assetRepair->sent_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Expected Return</label>
                            <input type="date" name="expected_return_date" class="form-control" value="{{ old('expected_return_date', $assetRepair->expected_return_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Completed</label>
                            <input type="date" name="completed_date" class="form-control" value="{{ old('completed_date', $assetRepair->completed_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Issue Summary</label>
                            <textarea name="issue_summary" class="form-control" rows="3" required>{{ old('issue_summary', $assetRepair->issue_summary) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" rows="4">{{ old('diagnosis', $assetRepair->diagnosis) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Work Performed</label>
                            <textarea name="work_performed" class="form-control" rows="4">{{ old('work_performed', $assetRepair->work_performed) }}</textarea>
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
                                @php $parts = old('parts', $assetRepair->parts->map(fn($part) => $part->only(['part_name','part_number','quantity','unit_cost','notes']))->toArray()); @endphp
                                @for($i = 0; $i < 5; $i++)
                                    @php $part = $parts[$i] ?? []; @endphp
                                    <tr>
                                        <td><input type="text" name="parts[{{ $i }}][part_name]" class="form-control form-control-sm" value="{{ $part['part_name'] ?? '' }}" placeholder="Keyboard, SSD, Display..."></td>
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
                            <input type="number" min="0" step="0.01" name="service_cost" class="form-control" value="{{ old('service_cost', $assetRepair->service_cost) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tax</label>
                            <input type="number" min="0" step="0.01" name="tax_amount" class="form-control" value="{{ old('tax_amount', $assetRepair->tax_amount) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discount</label>
                            <input type="number" min="0" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount', $assetRepair->discount_amount) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice No.</label>
                            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $assetRepair->invoice_number) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Admin Notes</label>
                            <textarea name="admin_notes" class="form-control" rows="3">{{ old('admin_notes', $assetRepair->admin_notes) }}</textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('admin.asset-repairs.index') }}" class="btn-cancel">Back</a>
                        <button type="submit" class="btn btn-primary btn-save"><i class="bi bi-check2-circle"></i>Update Repair</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-teal"><i class="bi bi-box-seam"></i></span>Asset & Cost</div>
            <div class="form-card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Asset</dt><dd class="col-7"><a href="{{ route('admin.assets.show', $assetRepair->asset) }}">{{ $assetRepair->asset->name }}</a></dd>
                    <dt class="col-5 text-muted">Assigned To</dt><dd class="col-7">{{ $assetRepair->assignment?->user?->name ?? $assetRepair->asset->activeAssignment?->user?->name ?? 'Stock asset' }}</dd>
                    <dt class="col-5 text-muted">Vendor</dt><dd class="col-7">{{ $assetRepair->vendor?->name ?? $assetRepair->market_vendor_name ?? 'Internal' }}</dd>
                    <dt class="col-5 text-muted">Parts Cost</dt><dd class="col-7">&#8377;{{ number_format((float) $assetRepair->parts_cost, 2) }}</dd>
                    <dt class="col-5 text-muted">Service Cost</dt><dd class="col-7">&#8377;{{ number_format((float) $assetRepair->service_cost, 2) }}</dd>
                    <dt class="col-5 text-muted">Tax</dt><dd class="col-7">&#8377;{{ number_format((float) $assetRepair->tax_amount, 2) }}</dd>
                    <dt class="col-5 text-muted">Discount</dt><dd class="col-7">&#8377;{{ number_format((float) $assetRepair->discount_amount, 2) }}</dd>
                    <dt class="col-5 text-muted">Total</dt><dd class="col-7 fw-bold">&#8377;{{ number_format((float) $assetRepair->total_cost, 2) }}</dd>
                </dl>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-amber"><i class="bi bi-clipboard2-check"></i></span>Quality Check</div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('admin.asset-repairs.quality-check', $assetRepair) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label">QC Result</label>
                        <select name="qc_status" class="form-select" required>
                            <option value="passed" @selected($assetRepair->qc_status === 'passed')>Passed</option>
                            <option value="failed" @selected($assetRepair->qc_status === 'failed')>Failed</option>
                        </select>
                    </div>
                    @foreach(['Power on test','Hardware test','OS/software test','Network test','Physical condition','Data/security check','Accessories verified'] as $check)
                        <label class="form-check small mb-2">
                            <input type="checkbox" name="qc_checks[]" value="{{ $check }}" class="form-check-input" @checked(in_array($check, $assetRepair->qc_checks ?? [], true))>
                            <span class="form-check-label">{{ $check }}</span>
                        </label>
                    @endforeach
                    <div class="mb-3 mt-3">
                        <label class="form-label">QC Notes</label>
                        <textarea name="qc_notes" class="form-control" rows="3">{{ $assetRepair->qc_notes }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100"><i class="bi bi-clipboard-check me-1"></i>Save QC</button>
                </form>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-green"><i class="bi bi-check2-square"></i></span>Close Repair</div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('admin.asset-repairs.close', $assetRepair) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label">Return Asset To</label>
                        <select name="return_to" class="form-select" required>
                            <option value="employee">Employee / Current Assignment</option>
                            <option value="stock">Available Stock</option>
                            <option value="not_repairable">Not Repairable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Returned Date</label>
                        <input type="date" name="returned_date" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Closing Notes</label>
                        <textarea name="admin_notes" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100" @disabled(!in_array($assetRepair->status, ['ready_to_return', 'not_repairable'], true))>
                        <i class="bi bi-check-circle me-1"></i>Close & Return
                    </button>
                    @if(!in_array($assetRepair->status, ['ready_to_return', 'not_repairable'], true))
                        <div class="form-text">QC must pass, or the job must be marked not repairable before closing.</div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
