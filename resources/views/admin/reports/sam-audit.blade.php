@extends('layouts.app')
@section('title', 'SAM Audit Pack')

@section('content')
<div class="page-header"><h4>SAM Audit Pack</h4><p>Export a point-in-time evidence package for internal review, license true-up, or external audit.</p></div>

<div class="row g-3 mb-3">
    @foreach([
        ['Catalog Titles', $stats['catalog'], 'grad-blue', 'Normalized software register'],
        ['Active Installations', $stats['installations'], 'grad-purple', 'Current discovery evidence'],
        ['License Seats', $stats['license_seats'], 'grad-green', 'Active purchased entitlement'],
        ['Open Actions', $stats['open_actions'], 'grad-orange', 'Remediation still in progress'],
    ] as [$label,$value,$color,$sub])
    <div class="col-sm-6 col-xl-3"><div class="stat-card-gradient {{ $color }}"><div class="card-body"><div class="stat-label">{{ $label }}</div><div class="stat-number">{{ number_format($value) }}</div><div class="stat-sub">{{ $sub }}</div></div></div></div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-5"><div class="form-card h-100"><div class="form-card-header"><div class="icon-wrap icon-blue"><i class="bi bi-file-earmark-zip-fill"></i></div>Build Audit Package</div><div class="form-card-body">
        <form method="POST" action="{{ route('admin.sam-audit.download') }}">@csrf
            <div class="mb-3"><label class="form-label">Activity History From</label><input type="date" name="activity_from" class="form-control" max="{{ today()->toDateString() }}" value="{{ old('activity_from', today()->subYear()->toDateString()) }}" required><div class="form-text">Controls historical exceptions and remediation actions. Current catalog, entitlement, devices, and compliance are always included.</div></div>
            <label class="d-flex align-items-start gap-2 border rounded p-3 mb-3" style="cursor:pointer;background:#f8fafc"><input type="checkbox" name="include_removed" value="1" class="form-check-input mt-1"><span><span class="fw-bold d-block">Include removed software records</span><span class="text-muted small">Adds historical installations that the device agent has since reported as removed.</span></span></label>
            <button class="btn btn-primary w-100"><i class="bi bi-file-earmark-arrow-down me-1"></i>Build and Download Audit Pack</button>
        </form>
    </div></div></div>
    <div class="col-xl-7"><div class="table-card h-100"><div class="card-header"><span class="fw-semibold">Package Contents</span></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Evidence File</th><th>What It Proves</th></tr></thead><tbody>
        @foreach([
            ['Compliance Snapshot','Required versus purchased seats, policy violations, exceptions, risk and exposure.'],
            ['Software Catalog','Normalized titles, license metrics, criticality and reviewed policy decisions.'],
            ['License Entitlements','Purchased seats, dates, supplier references and masked key references.'],
            ['License Allocations','Employee allocations, assignment dates, returns and responsible administrator.'],
            ['Discovered Installations','Device-level software evidence, versions, mapping and usage information.'],
            ['Device Coverage','Agent health, inventory recency and asset or employee matching.'],
            ['Recognition Rules','Approved rules used to normalize raw software evidence.'],
            ['Policy Exceptions','Time-bound approvals, reasons, conditions and revocation history.'],
            ['Remediation Actions','Compliance tasks, ownership, due dates, completion and audit notes.'],
        ] as [$name,$description])<tr><td class="ps-4 fw-semibold">{{ $name }}</td><td class="text-muted">{{ $description }}</td></tr>@endforeach
    </tbody></table></div></div></div>
</div>
@endsection
