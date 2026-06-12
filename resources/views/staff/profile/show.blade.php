@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <div class="topbar-avatar" style="width:58px;height:58px;font-size:1.2rem">{{ strtoupper(substr($employee->user->name, 0, 1)) }}</div>
        <div>
            <h4>My Profile</h4>
            <p>{{ $employee->user->name }} • {{ $employee->employee_code ?: 'Employee code not set' }}</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card-gradient grad-blue">
            <div class="card-body">
                <div class="stat-label">Status</div>
                <div class="stat-number" style="font-size:1.35rem">{{ $employee->employment_status_label }}</div>
                <div class="stat-sub">{{ $employee->employment_type_label }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card-gradient grad-green">
            <div class="card-body">
                <div class="stat-label">IT Assets</div>
                <div class="stat-number">{{ $assetAssignments->count() }}</div>
                <div class="stat-sub">Assigned to you</div>
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
    <div class="col-md-3">
        <div class="stat-card-gradient grad-orange">
            <div class="card-body">
                <div class="stat-label">Pending Docs</div>
                <div class="stat-number">{{ $employee->documentRequests->whereIn('status', ['pending', 'rejected'])->count() }}</div>
                <div class="stat-sub">Need upload</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-blue"><i class="bi bi-person-lines-fill"></i></span> Work Details</div>
            <div class="form-card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Official Email</dt><dd class="col-7">{{ $employee->user->email }}</dd>
                    <dt class="col-5 text-muted">Phone</dt><dd class="col-7">{{ $employee->user->phone ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Department</dt><dd class="col-7">{{ $employee->user->department?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Job Title</dt><dd class="col-7">{{ $employee->user->job_title ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Manager</dt><dd class="col-7">{{ $employee->manager?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Shift</dt><dd class="col-7">{{ $employee->shift?->name ?? '—' }}{{ $employee->shift ? ' ('.$employee->shift->time_range.')' : '' }}</dd>
                    <dt class="col-5 text-muted">Joining</dt><dd class="col-7">{{ $employee->joining_date?->format('d-m-Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Facility</dt><dd class="col-7">{{ $employee->facility?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Location</dt><dd class="col-7">{{ $employee->location?->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-header"><span class="icon-wrap icon-amber"><i class="bi bi-heart-pulse"></i></span> Personal & Emergency</div>
            <div class="form-card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Personal Email</dt><dd class="col-7">{{ $employee->personal_email ?: '—' }}</dd>
                    <dt class="col-5 text-muted">DOB</dt><dd class="col-7">{{ $employee->date_of_birth?->format('d-m-Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Gender</dt><dd class="col-7">{{ $employee->gender ? ucwords(str_replace('_', ' ', $employee->gender)) : '—' }}</dd>
                    <dt class="col-5 text-muted">Emergency</dt><dd class="col-7">{{ $employee->emergency_contact_name ?: '—' }}<br>{{ $employee->emergency_contact_phone }}</dd>
                    <dt class="col-5 text-muted">Address</dt><dd class="col-7">{{ $employee->address ?: '—' }}</dd>
                </dl>
            </div>
        </div>

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
                <span>Pending Document Requests</span>
                <span class="badge bg-warning text-dark">{{ $employee->documentRequests->whereIn('status', ['pending', 'rejected'])->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc">
                        <tr>
                            <th class="ps-4">Document</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Upload</th>
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
                                    @if($request->review_notes)
                                        <div class="alert alert-{{ $request->status === 'rejected' ? 'danger' : 'success' }} py-2 px-3 mt-2 mb-0 small">
                                            <strong>HR Review:</strong> {{ $request->review_notes }}
                                            @if($request->reviewer)
                                                <span class="d-block text-muted mt-1">Reviewed by {{ $request->reviewer->name }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $request->due_date?->format('d-m-Y') ?? '—' }}</td>
                                <td><span class="badge bg-{{ $request->status_badge }}">{{ $request->status_label }}</span></td>
                                <td class="text-end pe-4">
                                    @if(in_array($request->status, ['pending', 'rejected'], true))
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadRequest{{ $request->id }}">
                                            <i class="bi bi-upload me-1"></i> Upload
                                        </button>
                                    @elseif($request->fulfilledDocument)
                                        <a href="{{ $request->fulfilledDocument->url }}" target="_blank" class="btn btn-sm btn-outline-primary">View Submitted</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No pending document requests.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card mb-4">
            <div class="card-header">My HR Documents</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc">
                        <tr>
                            <th class="ps-4">Document</th>
                            <th>Type</th>
                            <th>Uploaded</th>
                            <th class="text-end pe-4">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->documents as $document)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $document->title }}</div>
                                    <div class="text-muted small">{{ $document->original_name }} • {{ $document->file_size_human }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $document->document_type_label }}</span></td>
                                <td>{{ $document->created_at->format('d-m-Y') }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ $document->url }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No HR documents available yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="table-card h-100">
                    <div class="card-header">Assigned Assets</div>
                    <div class="card-body">
                        @forelse($assetAssignments as $assignment)
                            <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                                <div>
                                    <div class="fw-bold small">{{ $assignment->asset?->name }}</div>
                                    <div class="text-muted small">{{ $assignment->asset?->asset_tag }}</div>
                                </div>
                                <span class="badge bg-light text-dark border">{{ $assignment->asset?->category?->name ?? 'Asset' }}</span>
                            </div>
                        @empty
                            <div class="text-muted small text-center py-4">No assets assigned.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="table-card h-100">
                    <div class="card-header">Assigned Software</div>
                    <div class="card-body">
                        @forelse($softwareAssignments as $assignment)
                            <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                                <div>
                                    <div class="fw-bold small">{{ $assignment->license?->software?->name }}</div>
                                    <div class="text-muted small">{{ $assignment->license?->software?->vendor ?: 'Publisher not set' }}</div>
                                </div>
                                <span class="badge bg-light text-dark border">{{ $assignment->license?->license_type_label ?? 'License' }}</span>
                            </div>
                        @empty
                            <div class="text-muted small text-center py-4">No software assigned.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($employee->documentRequests as $request)
    @if(in_array($request->status, ['pending', 'rejected'], true))
        <div class="modal fade" id="uploadRequest{{ $request->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form method="POST" action="{{ route('staff.profile.document-requests.upload', $request) }}" enctype="multipart/form-data" class="modal-content border-0" style="border-radius:16px">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title fw-bold">Upload {{ $request->title }}</h5>
                            <div class="text-muted small">{{ $request->document_type_label }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">File <span class="req">*</span></label>
                                <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt,.zip" required>
                                <div class="form-text">PDF, image, Word, Excel, TXT, or ZIP. Max 10 MB.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Optional note for HR/Admin"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Submit Document</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach
@endsection
