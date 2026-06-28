@extends('layouts.app')
@section('title', 'Software Policies')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div><h4>Software Policies</h4><p>Decide which catalog applications are approved, restricted, or prohibited across the organization.</p></div>
    <a href="{{ route('admin.software.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-display me-1"></i>Software Catalog</a>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Unreviewed', $stats['unreviewed'], 'grad-blue', 'Awaiting a policy decision'],
        ['Approved', $stats['approved'], 'grad-green', 'Allowed for normal use'],
        ['Restricted', $stats['restricted'], 'grad-orange', 'Allowed with conditions'],
        ['Prohibited Installs', $stats['prohibited_installs'], 'grad-red', 'Requires remediation'],
    ] as [$label, $value, $color, $sub])
    <div class="col-sm-6 col-xl-3"><div class="stat-card-gradient {{ $color }}"><div class="card-body"><div class="stat-label">{{ $label }}</div><div class="stat-number">{{ $value }}</div><div class="stat-sub">{{ $sub }}</div></div></div></div>
    @endforeach
</div>

<div class="table-card mb-3"><div class="card-body"><form method="GET" class="row g-2 align-items-end">
    <div class="col-lg-4"><label class="form-label">Search</label><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Software or publisher"></div>
    <div class="col-sm-6 col-lg-2"><label class="form-label">Policy</label><select name="policy_status" class="form-select"><option value="">All policy states</option>@foreach($policyStatuses as $key => $meta)<option value="{{ $key }}" @selected(request('policy_status')===$key)>{{ $meta['label'] }}</option>@endforeach</select></div>
    <div class="col-sm-6 col-lg-2"><label class="form-label">Discovery</label><select name="installed" class="form-select"><option value="">All catalog titles</option><option value="yes" @selected(request('installed')==='yes')>Installed</option><option value="no" @selected(request('installed')==='no')>Not detected</option></select></div>
    <div class="col-sm-6 col-lg-1"><label class="form-label">Rows</label><select name="per_page" class="form-select">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button></div>
    @if(request()->hasAny(['search','policy_status','installed','per_page']))<div class="col-auto"><a href="{{ route('admin.software-policies.index') }}" class="btn btn-outline-secondary">Clear</a></div>@endif
</form></div></div>

<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Policy Register</span><span class="badge bg-light text-dark">{{ $software->total() }}</span></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th class="ps-4">Software</th><th>Type</th><th>Criticality</th><th>Detected Installs</th><th>Policy</th><th>Last Reviewed</th><th class="text-end pe-4">Action</th></tr></thead><tbody>
    @forelse($software as $item)
    <tr><td class="ps-4"><div class="fw-bold">{{ $item->name }}</div><div class="text-muted small">{{ $item->vendor ?: 'Publisher not recorded' }}</div></td><td>{{ $item->software_type_label }}</td><td><span class="badge bg-{{ $item->criticality_badge }}">{{ ucfirst($item->criticality) }}</span></td><td><span class="fw-bold">{{ number_format($item->installed_count) }}</span></td><td><span class="badge bg-{{ $item->policy_status_badge }}">{{ $item->policy_status_label }}</span>@if($item->policy_notes)<div class="text-muted small mt-1 text-truncate" style="max-width:260px" title="{{ $item->policy_notes }}">{{ $item->policy_notes }}</div>@endif</td><td><div>{{ $item->policy_reviewed_at?->format('d M Y') ?? 'Not reviewed' }}</div><div class="text-muted small">{{ $item->policyReviewedBy?->name }}</div></td><td class="text-end pe-4"><div class="d-inline-flex gap-1"><button class="btn btn-sm btn-outline-primary policy-review-button" data-bs-toggle="modal" data-bs-target="#policyReviewModal" data-action="{{ route('admin.software-policies.update', $item) }}" data-name="{{ $item->name }}" data-status="{{ $item->policy_status }}" data-notes="{{ $item->policy_notes }}"><i class="bi bi-pencil me-1"></i>Review</button>@if($item->policy_status==='prohibited' && $item->installed_count>0)<form method="POST" action="{{ route('admin.software-policies.remediation', $item) }}" onsubmit="return confirm('Create remediation tasks for detected installations of {{ addslashes($item->name) }}?')">@csrf<button class="btn btn-sm btn-danger"><i class="bi bi-shield-x me-1"></i>Create Tasks</button></form>@endif</div></td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-shield-check fs-1 d-block mb-2 opacity-25"></i>No software titles match these filters.</td></tr>@endforelse
    </tbody></table></div>
    @if($software->hasPages())<div class="p-3 border-top">{{ $software->links() }}</div>@endif
</div>

<div class="modal fade" id="policyReviewModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form id="policyReviewForm" method="POST" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><div><h5 class="modal-title mb-0">Review Software Policy</h5><small id="policySoftwareName" class="text-muted"></small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Policy Decision</label><select id="policyStatus" name="policy_status" class="form-select" required>@foreach($policyStatuses as $key => $meta)<option value="{{ $key }}">{{ $meta['label'] }}</option>@endforeach</select></div><div><label class="form-label">Conditions or Reason</label><textarea id="policyNotes" name="policy_notes" rows="4" maxlength="2000" class="form-control" placeholder="Explain restrictions, approval conditions, or why this software is prohibited."></textarea></div><div class="alert alert-info small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Marking software as prohibited highlights it in Compliance. Remediation tasks are created separately after review.</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save Policy</button></div></form></div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.policy-review-button').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('policyReviewForm').action = button.dataset.action;
            document.getElementById('policySoftwareName').textContent = button.dataset.name;
            document.getElementById('policyStatus').value = button.dataset.status;
            document.getElementById('policyNotes').value = button.dataset.notes || '';
        });
    });
});
</script>
@endpush
