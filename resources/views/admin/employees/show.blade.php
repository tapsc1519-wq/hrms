@extends('layouts.app')

@section('title', 'Employee Profile')

@section('content')
<div class="page-header">
    <a href="{{ route('admin.employees.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Employees</a>
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-3">
            <div class="topbar-avatar" style="width:54px;height:54px;font-size:1.15rem">{{ strtoupper(substr($employee->user->name, 0, 1)) }}</div>
            <div>
                <h4>{{ $employee->user->name }}</h4>
                <p>{{ $employee->user->job_title ?: 'No job title' }}{{ $employee->user->department ? ' • '.$employee->user->department->name : '' }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> Edit Profile</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-gradient grad-blue">
            <div class="card-body">
                <div class="stat-label">Employee Code</div>
                <div class="stat-number" style="font-size:1.35rem">{{ $employee->employee_code ?: '—' }}</div>
                <div class="stat-sub">{{ $employee->employment_type_label }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-green">
            <div class="card-body">
                <div class="stat-label">Status</div>
                <div class="stat-number" style="font-size:1.35rem">{{ $employee->employment_status_label }}</div>
                <div class="stat-sub">Employment status</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-orange">
            <div class="card-body">
                <div class="stat-label">IT Assets</div>
                <div class="stat-number">{{ $assetAssignments->count() }}</div>
                <div class="stat-sub">Active assignments</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-purple">
            <div class="card-body">
                <div class="stat-label">Software</div>
                <div class="stat-number">{{ $softwareAssignments->count() }}</div>
                <div class="stat-sub">Active licenses</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-person-lines-fill"></i></span> Profile</div>
            <div class="form-card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Email</dt><dd class="col-7">{{ $employee->user->email }}</dd>
                    <dt class="col-5 text-muted">Phone</dt><dd class="col-7">{{ $employee->user->phone ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Personal Email</dt><dd class="col-7">{{ $employee->personal_email ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Joining</dt><dd class="col-7">{{ $employee->joining_date?->format('d-m-Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Manager</dt><dd class="col-7">{{ $employee->manager?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Shift</dt><dd class="col-7">{{ $employee->shift?->name ?? '—' }}{{ $employee->shift ? ' ('.$employee->shift->time_range.')' : '' }}</dd>
                    <dt class="col-5 text-muted">Facility</dt><dd class="col-7">{{ $employee->facility?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Location</dt><dd class="col-7">{{ $employee->location?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Emergency</dt><dd class="col-7">{{ $employee->emergency_contact_name ?: '—' }}<br>{{ $employee->emergency_contact_phone }}</dd>
                </dl>
            </div>
        </div>
        @if($employee->notes)
            <div class="form-card">
                <div class="form-card-header"><span class="icon-wrap icon-amber"><i class="bi bi-sticky"></i></span> Notes</div>
                <div class="form-card-body small text-muted">{{ $employee->notes }}</div>
            </div>
        @endif
        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-teal"><i class="bi bi-bank"></i></span> Payroll Details</div>
            <div class="form-card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Bank</dt><dd class="col-7">{{ $employee->bank_name ?: 'â€”' }}</dd>
                    <dt class="col-5 text-muted">Account Name</dt><dd class="col-7">{{ $employee->bank_account_name ?: 'â€”' }}</dd>
                    <dt class="col-5 text-muted">Account No.</dt><dd class="col-7">{{ $employee->bank_account_number ?: 'â€”' }}</dd>
                    <dt class="col-5 text-muted">IFSC</dt><dd class="col-7">{{ $employee->ifsc_code ?: 'â€”' }}</dd>
                    <dt class="col-5 text-muted">PAN</dt><dd class="col-7">{{ $employee->pan_number ?: 'â€”' }}</dd>
                    <dt class="col-5 text-muted">UAN</dt><dd class="col-7">{{ $employee->uan_number ?: 'â€”' }}</dd>
                    <dt class="col-5 text-muted">PF No.</dt><dd class="col-7">{{ $employee->pf_number ?: 'â€”' }}</dd>
                    <dt class="col-5 text-muted">ESI No.</dt><dd class="col-7">{{ $employee->esi_number ?: 'â€”' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="table-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>HR Documents</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#requestDocumentModal">
                        <i class="bi bi-send me-1"></i> Request Document
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                        <i class="bi bi-upload me-1"></i> Upload Document
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc">
                        <tr>
                            <th class="ps-4">Document</th>
                            <th>Type</th>
                            <th>Expiry</th>
                            <th>Uploaded</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->documents as $document)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="icon-wrap icon-slate"><i class="bi {{ $document->file_icon }}"></i></span>
                                        <div>
                                            <div class="fw-bold">{{ $document->title }}</div>
                                            <div class="text-muted small">{{ $document->original_name }} • {{ $document->file_size_human }}</div>
                                            @if($document->notes)
                                                <div class="text-muted small mt-1">{{ $document->notes }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $document->document_type_label }}</span></td>
                                <td>
                                    @if($document->expiry_date)
                                        <span class="{{ $document->expiry_date->isPast() ? 'text-danger fw-bold' : '' }}">
                                            {{ $document->expiry_date->format('d-m-Y') }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $document->created_at->format('d-m-Y') }}</div>
                                    <div class="text-muted small">{{ $document->uploader?->name }}</div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ $document->url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.employees.documents.destroy', [$employee, $document]) }}" class="d-inline"
                                          onsubmit="return confirm('Delete this document?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-folder2-open d-block fs-3 mb-2"></i>
                                    No HR documents uploaded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Document Requests</span>
                <span class="badge bg-warning text-dark">{{ $employee->documentRequests->whereIn('status', ['pending', 'rejected'])->count() }} pending</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc">
                        <tr>
                            <th class="ps-4">Request</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Submitted File</th>
                            <th class="text-end pe-4">Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->documentRequests as $request)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $request->title }}</div>
                                    <div class="text-muted small">{{ $request->document_type_label }} • Requested by {{ $request->requester?->name }}</div>
                                    @if($request->notes)
                                        <div class="text-muted small mt-1">{{ $request->notes }}</div>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $request->status_badge }}">{{ $request->status_label }}</span></td>
                                <td>{{ $request->due_date?->format('d-m-Y') ?? '—' }}</td>
                                <td>
                                    @if($request->fulfilledDocument)
                                        <a href="{{ $request->fulfilledDocument->url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download me-1"></i> View
                                        </a>
                                    @else
                                        <span class="text-muted small">Not submitted</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($request->status === 'submitted')
                                        <div class="d-flex justify-content-end gap-2">
                                            <form method="POST" action="{{ route('admin.employees.document-requests.approve', [$employee, $request]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-success">
                                                    <i class="bi bi-check2 me-1"></i> Approve
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectDocumentRequest{{ $request->id }}">
                                                <i class="bi bi-x-lg me-1"></i> Reject
                                            </button>
                                        </div>
                                    @elseif($request->reviewed_at)
                                        <div class="small">
                                            <div class="fw-semibold">Reviewed by {{ $request->reviewer?->name ?? 'Admin' }}</div>
                                            <div class="text-muted">{{ $request->reviewed_at->format('d-m-Y h:i A') }}</div>
                                            @if($request->review_notes)
                                                <div class="text-muted mt-1">{{ $request->review_notes }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">Awaiting submission</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No document requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @foreach($employee->documentRequests->where('status', 'submitted') as $request)
            <div class="modal fade" id="rejectDocumentRequest{{ $request->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('admin.employees.document-requests.reject', [$employee, $request]) }}" class="modal-content border-0" style="border-radius:16px">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title fw-bold">Reject Document</h5>
                                <div class="text-muted small">Tell the employee what needs to be corrected.</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Rejection Notes <span class="req">*</span></label>
                            <textarea name="review_notes" class="form-control" rows="4" required placeholder="Example: Uploaded file is unclear. Please upload a clearer copy."></textarea>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-danger"><i class="bi bi-x-lg me-1"></i> Reject Document</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach

        <div class="table-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Assigned IT Assets</span>
                <span class="badge bg-primary">{{ $assetAssignments->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc">
                        <tr>
                            <th class="ps-4">Asset</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Assigned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assetAssignments as $assignment)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $assignment->asset?->name }}</div>
                                    <div class="text-muted small">{{ $assignment->asset?->asset_tag }}</div>
                                </td>
                                <td>{{ $assignment->asset?->category?->name ?? '—' }}</td>
                                <td>{{ $assignment->asset?->location?->name ?? '—' }}</td>
                                <td>{{ $assignment->assigned_date?->format('d-m-Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No active assets assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Assigned Software</span>
                <span class="badge bg-primary">{{ $softwareAssignments->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc">
                        <tr>
                            <th class="ps-4">Software</th>
                            <th>License Type</th>
                            <th>Assigned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($softwareAssignments as $assignment)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $assignment->license?->software?->name }}</div>
                                    <div class="text-muted small">{{ $assignment->license?->software?->vendor ?: 'Publisher not set' }}</div>
                                </td>
                                <td>{{ $assignment->license?->license_type_label ?? '—' }}</td>
                                <td>{{ $assignment->assigned_date?->format('d-m-Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No active software licenses assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="requestDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.employees.document-requests.store', $employee) }}" class="modal-content border-0" style="border-radius:16px">
            @csrf
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Request Document</h5>
                    <div class="text-muted small">Ask {{ $employee->user->name }} to upload a pending HR document from the staff portal.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Document Type <span class="req">*</span></label>
                        <select name="document_type" class="form-select" required>
                            <option value="id_proof">ID Proof</option>
                            <option value="address_proof">Address Proof</option>
                            <option value="education">Education</option>
                            <option value="experience">Experience</option>
                            <option value="policy_acknowledgement">Policy Acknowledgement</option>
                            <option value="offer_letter">Offer Letter</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Title <span class="req">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Aadhaar Card, Degree Certificate" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Instructions</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional instructions for the employee"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Send Request</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.employees.documents.store', $employee) }}" enctype="multipart/form-data" class="modal-content border-0" style="border-radius:16px">
            @csrf
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Upload HR Document</h5>
                    <div class="text-muted small">Attach documents to {{ $employee->user->name }}'s HR profile.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Document Type <span class="req">*</span></label>
                        <select name="document_type" class="form-select" required>
                            <option value="offer_letter">Offer Letter</option>
                            <option value="id_proof">ID Proof</option>
                            <option value="address_proof">Address Proof</option>
                            <option value="education">Education</option>
                            <option value="experience">Experience</option>
                            <option value="policy_acknowledgement">Policy Acknowledgement</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Title <span class="req">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Aadhaar Card, Offer Letter, Degree Certificate" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">File <span class="req">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt,.zip" required>
                        <div class="form-text">PDF, image, Word, Excel, TXT, or ZIP. Max 10 MB.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control">
                        <div class="form-text">Useful for ID proofs, contracts, or certificates.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Upload</button>
            </div>
        </form>
    </div>
</div>
@endsection
