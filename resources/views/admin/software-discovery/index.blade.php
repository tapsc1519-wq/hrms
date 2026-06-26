@extends('layouts.app')
@section('title', $workbench ? 'Normalization Workbench' : 'Discovery Inventory')

@section('content')
@php
    $clearRoute = $workbench ? route('admin.software-normalization.index') : route('admin.software-discovery.index');
@endphp
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4>{{ $workbench ? 'Normalization Workbench' : 'Discovery Inventory' }}</h4>
        <p>{{ $workbench ? 'Review unknown discovered software and map it to the catalogue.' : 'Raw discovered software from CSV imports and future device agents.' }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.software-discovery.import') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-upload me-1"></i>Import CSV
        </a>
        @if(!$workbench)
        <a href="{{ route('admin.software-normalization.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-diagram-3 me-1"></i>Open Workbench
        </a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([['Total', $stats['total'], 'grad-blue'], ['Unknown', $stats['unknown'], 'grad-orange'], ['Mapped', $stats['mapped'], 'grad-green'], ['Ignored', $stats['ignored'], 'grad-teal']] as [$label, $value, $color])
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-gradient {{ $color }}"><div class="card-body">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-number">{{ $value }}</div>
            <div class="stat-sub">Discovery records</div>
        </div></div>
    </div>
    @endforeach
</div>

<div class="table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Software, publisher, asset tag or employee">
            </div>
            @unless($workbench)
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['unknown', 'mapped', 'ignored'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            @endunless
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            @if(request()->hasAny(['search','status']))
            <div class="col-md-2">
                <a href="{{ $clearRoute }}" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg me-1"></i>Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Discovered Software</th>
                    <th>Device / User</th>
                    <th>Usage</th>
                    <th>Status</th>
                    <th>Mapped To</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($discoveries as $item)
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">{{ $item->raw_name }}</div>
                        <div class="text-muted small">
                            {{ $item->raw_publisher ?: 'Unknown publisher' }}
                            @if($item->raw_version) &middot; v{{ $item->raw_version }} @endif
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $item->asset?->asset_tag ?? 'No device' }}</div>
                        <div class="text-muted small">{{ $item->user?->name ?? $item->user?->email ?? 'No user' }}</div>
                    </td>
                    <td>
                        <div>{{ $item->last_used_date?->format('d-m-Y') ?? 'No usage date' }}</div>
                        <div class="text-muted small">{{ $item->usage_count ?? 0 }} launches</div>
                    </td>
                    <td><span class="badge bg-{{ $item->status_badge }}">{{ ucfirst($item->status) }}</span></td>
                    <td>
                        @if($item->software)
                            <div class="fw-bold">{{ $item->software->name }}</div>
                            <div class="text-muted small">{{ $item->confidence_score ?? '-' }}% confidence</div>
                        @else
                            <span class="text-muted">Not mapped</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        @if($item->status !== 'ignored')
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mapModal{{ $item->id }}">
                                <i class="bi bi-diagram-3 me-1"></i>Map
                            </button>
                            @if($item->status === 'unknown')
                            <form method="POST" action="{{ route('admin.software-discovery.ignore', $item) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary">
                                    Ignore
                                </button>
                            </form>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>

                <div class="modal fade" id="mapModal{{ $item->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('admin.software-discovery.normalize', $item) }}">
                            @csrf @method('PATCH')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h5 class="modal-title mb-0">Map Discovery Record</h5>
                                        <small class="text-muted">{{ $item->raw_name }}</small>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">Software Catalogue Item <span class="req">*</span></label>
                                    <select name="software_id" class="form-select mb-3" required>
                                        <option value="">Select software</option>
                                        @foreach($software as $sw)
                                            <option value="{{ $sw->id }}" @selected($item->software_id === $sw->id)>
                                                {{ $sw->name }}{{ $sw->vendor ? ' - '.$sw->vendor : '' }}{{ $sw->edition ? ' - '.$sw->edition : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label class="form-label">Confidence Score</label>
                                    <input type="number" name="confidence_score" class="form-control mb-3" min="1" max="100" value="{{ $item->confidence_score ?? 95 }}">
                                    <label class="d-flex align-items-center gap-2 border rounded-3 p-3" style="cursor:pointer;background:#f8fafc">
                                        <input type="checkbox" name="create_rule" value="1" class="form-check-input m-0" checked>
                                        <span class="fw-bold">Create recognition rule for future imports</span>
                                    </label>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary">Save Mapping</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-hdd-network fs-1 d-block mb-2 opacity-25"></i>
                        {{ $workbench ? 'No unknown software waiting for normalization.' : 'No discovery records found.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($discoveries->hasPages())
        <div class="p-3 border-top">{{ $discoveries->links() }}</div>
    @endif
</div>
@endsection
