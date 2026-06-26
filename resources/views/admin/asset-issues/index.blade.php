@extends('layouts.app')
@section('title', 'Asset Issues')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Asset Issues</h4>
        <p>Employee-reported damaged, lost or unusable assets for Admin/IT review.</p>
    </div>
    <a href="{{ route('admin.disposals.requests') }}" class="btn btn-outline-primary">
        <i class="bi bi-send me-1"></i>Disposal Requests
    </a>
</div>

<div class="row g-3 mb-3">
    @foreach([['Open', $stats['open'], 'grad-orange', 'Awaiting review'], ['Under Review', $stats['under_review'], 'grad-blue', 'Being checked'], ['Converted', $stats['converted'], 'grad-teal', 'Linked to disposal'], ['Resolved', $stats['resolved'], 'grad-green', 'Closed without disposal']] as [$label, $value, $color, $sub])
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient {{ $color }}"><div class="card-body">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-number">{{ $value }}</div>
            <div class="stat-sub">{{ $sub }}</div>
        </div></div>
    </div>
    @endforeach
</div>

<div class="table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Asset, tag, serial or employee">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Open + Under Review</option>
                    @foreach(['open', 'under_review', 'converted_to_disposal', 'resolved', 'rejected', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Issue Type</label>
                <select name="issue_type" class="form-select">
                    <option value="">All issue types</option>
                    @foreach(['damaged', 'not_working', 'lost', 'stolen', 'obsolete', 'other'] as $type)
                        <option value="{{ $type }}" @selected(request('issue_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if(request()->hasAny(['search','status','issue_type']))
                    <a href="{{ route('admin.asset-issues.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Asset</th>
                    <th>Reported By</th>
                    <th>Issue</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Reported</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($issues as $issue)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">{{ $issue->asset->name }}</div>
                        <div class="text-muted small">{{ $issue->asset->asset_tag }} &middot; {{ $issue->asset->serial_number ?? 'No serial' }}</div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $issue->reportedBy->name }}</div>
                        <div class="text-muted small">{{ $issue->reportedBy->department?->name ?? 'No department' }}</div>
                    </td>
                    <td>{{ $issue->issue_type_label }}</td>
                    <td><span class="badge bg-{{ $issue->severity_badge }}">{{ ucfirst($issue->severity) }}</span></td>
                    <td><span class="badge bg-{{ $issue->status_badge }}">{{ ucwords(str_replace('_', ' ', $issue->status)) }}</span></td>
                    <td>{{ $issue->reported_date->format('d-m-Y') }}</td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.asset-issues.show', $issue) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i> Review
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-check2-circle fs-1 d-block mb-2 opacity-25"></i>
                        No asset issues found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($issues->hasPages())
        <div class="p-3 border-top">{{ $issues->links() }}</div>
    @endif
</div>
@endsection
