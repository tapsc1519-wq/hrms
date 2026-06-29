@extends('layouts.app')
@section('title', 'Normalization Workbench')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div><h4>Normalization Workbench</h4><p>Review unknown software once and apply the decision across every matching installation.</p></div>
    <div class="d-flex gap-2 flex-wrap">
        @if(auth()->user()->hasPermission('software.manage'))
        <a href="{{ route('admin.software-recognition-rules.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-diagram-3 me-1"></i>Recognition Rules</a>
        @endif
        <a href="{{ route('admin.software-discovery.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-hdd-network me-1"></i>Discovery Inventory</a>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Unknown Installations', $stats['records'], 'grad-orange', 'Records waiting for review'],
        ['Software Groups', $stats['signatures'], 'grad-blue', 'Decisions required'],
        ['Affected Devices', $stats['devices'], 'grad-purple', 'Reporting unknown software'],
        ['Publishers', $stats['publishers'], 'grad-teal', 'Reported publisher names'],
    ] as [$label, $value, $color, $sub])
    <div class="col-sm-6 col-xl-3"><div class="stat-card-gradient {{ $color }}"><div class="card-body"><div class="stat-label">{{ $label }}</div><div class="stat-number">{{ $value }}</div><div class="stat-sub">{{ $sub }}</div></div></div></div>
    @endforeach
</div>

<div class="table-card mb-3"><div class="card-body"><form method="GET" class="row g-2 align-items-end">
    <div class="col-lg-5"><label class="form-label">Search Unknown Software</label><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Software name or publisher"></div>
    <div class="col-sm-4 col-lg-2"><label class="form-label">Rows</label><select name="per_page" class="form-select">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button></div>
    @if(request()->hasAny(['search','per_page']))<div class="col-auto"><a href="{{ route('admin.software-normalization.index') }}" class="btn btn-outline-secondary">Clear</a></div>@endif
</form></div></div>

<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Unknown Software Groups</span><span class="badge bg-light text-dark">{{ $groups->total() }}</span></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th class="ps-4">Discovered Software</th><th>Installations</th><th>Devices</th><th>Employees</th><th>Versions</th><th>Latest Report</th><th class="text-end pe-4">Decision</th></tr></thead><tbody>
    @forelse($groups as $group)
    <tr>
        <td class="ps-4"><div class="fw-bold">{{ $group->raw_name }}</div><div class="text-muted small">{{ $group->raw_publisher ?: 'Publisher not reported' }}</div></td>
        <td><span class="fw-bold">{{ number_format($group->installation_count) }}</span></td>
        <td>{{ number_format($group->device_count) }}</td>
        <td>{{ number_format($group->user_count) }}</td>
        <td>{{ number_format($group->version_count) }}</td>
        <td>{{ $group->latest_seen_at ? \Carbon\Carbon::parse($group->latest_seen_at)->diffForHumans() : 'Not reported' }}</td>
        <td class="text-end pe-4"><div class="d-inline-flex gap-1"><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#mapGroup{{ $loop->index }}"><i class="bi bi-diagram-3 me-1"></i>Map</button><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createGroup{{ $loop->index }}"><i class="bi bi-plus-lg me-1"></i>Create</button><form method="POST" action="{{ route('admin.software-normalization.ignore-group') }}" onsubmit="return confirm('Ignore all {{ $group->installation_count }} matching installations?')">@csrf @method('PATCH')<input type="hidden" name="raw_name" value="{{ $group->raw_name }}"><input type="hidden" name="raw_publisher" value="{{ $group->raw_publisher }}"><button class="btn btn-sm btn-outline-secondary">Ignore</button></form></div></td>
    </tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-check2-circle fs-1 d-block mb-2 opacity-25"></i>No unknown software groups are waiting for review.</td></tr>@endforelse
    </tbody></table></div>
    @if($groups->hasPages())<div class="p-3 border-top">{{ $groups->links() }}</div>@endif
</div>

@foreach($groups as $group)
<div class="modal fade" id="mapGroup{{ $loop->index }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('admin.software-normalization.map-group') }}" class="modal-content">@csrf @method('PATCH')
    <input type="hidden" name="raw_name" value="{{ $group->raw_name }}"><input type="hidden" name="raw_publisher" value="{{ $group->raw_publisher }}">
    <div class="modal-header"><div><h5 class="modal-title mb-0">Map Software Group</h5><small class="text-muted">{{ $group->raw_name }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i>This decision updates {{ number_format($group->installation_count) }} matching installations across {{ number_format($group->device_count) }} devices.</div>
        <div class="mb-3"><label class="form-label">Software Catalog Item <span class="req">*</span></label><select name="software_id" class="form-select" required><option value="">Select software</option>@foreach($software as $item)<option value="{{ $item->id }}">{{ $item->name }}{{ $item->vendor ? ' - '.$item->vendor : '' }}{{ $item->edition ? ' - '.$item->edition : '' }}</option>@endforeach</select></div>
        <div class="mb-3"><label class="form-label">Confidence Score</label><input type="number" name="confidence_score" class="form-control" min="1" max="100" value="95"></div>
        <label class="d-flex align-items-start gap-2 border rounded p-3" style="cursor:pointer;background:#f8fafc"><input type="checkbox" name="create_rule" value="1" class="form-check-input mt-1" checked><span><span class="fw-bold d-block">Recognize this automatically next time</span><span class="text-muted small">Future reports with this name and publisher will map to the selected catalog item.</span></span></label>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Apply to All</button></div>
</form></div></div>

<div class="modal fade" id="createGroup{{ $loop->index }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('admin.software-normalization.create-and-map-group') }}" class="modal-content">@csrf
    <input type="hidden" name="raw_name" value="{{ $group->raw_name }}"><input type="hidden" name="raw_publisher" value="{{ $group->raw_publisher }}">
    <div class="modal-header"><div><h5 class="modal-title mb-0">Create Catalog Item & Map Group</h5><small class="text-muted">{{ $group->raw_name }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i>This creates a catalog record, maps {{ number_format($group->installation_count) }} matching installations, and can remember the rule for future reports.</div>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Software Name <span class="req">*</span></label><input type="text" name="name" class="form-control" value="{{ $group->raw_name }}" required maxlength="255"></div>
            <div class="col-md-6"><label class="form-label">Vendor / Publisher</label><input type="text" name="vendor" class="form-control" value="{{ $group->raw_publisher }}" maxlength="255"></div>
            <div class="col-md-4"><label class="form-label">Category <span class="req">*</span></label><select name="category" class="form-select" required><option value="productivity">Productivity</option><option value="security">Security</option><option value="design">Design</option><option value="development">Development</option><option value="communication">Communication</option><option value="database">Database</option><option value="erp">ERP / Business</option><option value="operating_system">Operating System</option><option value="other" selected>Other</option></select></div>
            <div class="col-md-4"><label class="form-label">Software Type <span class="req">*</span></label><select name="software_type" class="form-select" required><option value="commercial" selected>Commercial</option><option value="saas">SaaS</option><option value="open_source">Open Source</option><option value="freeware">Freeware</option><option value="os">Operating System</option></select></div>
            <div class="col-md-4"><label class="form-label">Criticality <span class="req">*</span></label><select name="criticality" class="form-select" required><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
            <div class="col-md-4"><label class="form-label">License Required <span class="req">*</span></label><select name="license_required" class="form-select" required><option value="1" selected>Yes</option><option value="0">No</option></select></div>
            <div class="col-md-4"><label class="form-label">License Metric <span class="req">*</span></label><select name="license_metric" class="form-select" required><option value="per_user" selected>Per User</option><option value="per_device">Per Device</option><option value="concurrent">Concurrent</option><option value="site">Site</option><option value="enterprise">Enterprise</option><option value="usage_based">Usage Based</option></select></div>
            <div class="col-md-4"><label class="form-label">Confidence Score</label><input type="number" name="confidence_score" class="form-control" min="1" max="100" value="95"></div>
        </div>
        <label class="d-flex align-items-start gap-2 border rounded p-3 mt-3" style="cursor:pointer;background:#f8fafc"><input type="checkbox" name="create_rule" value="1" class="form-check-input mt-1" checked><span><span class="fw-bold d-block">Recognize this automatically next time</span><span class="text-muted small">Future reports with this name and publisher will map to the new catalog item.</span></span></label>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Create & Map</button></div>
</form></div></div>
@endforeach
@endsection
