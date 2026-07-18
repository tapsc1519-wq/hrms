@extends('layouts.app')
@section('title', 'Asset Requests')

@section('content')

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Asset Requests</h4>
        <p>{{ $requests->total() }} request{{ $requests->total() !== 1 ? 's' : '' }} submitted</p>
    </div>
    @php
        $pending = $requests->where('status','pending')->count();
    @endphp
    @if($pending > 0)
    <div class="rounded-3 px-3 py-2 d-flex align-items-center gap-2" style="background:#fffbeb;border:1.5px solid #fde68a">
        <i class="bi bi-hourglass-split text-warning"></i>
        <span style="font-size:.85rem;font-weight:700;color:#d97706">{{ $pending }} pending review</span>
    </div>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filters --}}
<div class="table-card mb-3" style="padding:0">
    <div class="px-4 py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Request # or requester name…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(['pending','approved','rejected','fulfilled','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Priority</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    @foreach(['urgent','high','normal','low'] as $p)
                        <option value="{{ $p }}" {{ request('priority') == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(request()->anyFilled(['search','status','priority']))
                <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary btn-sm ms-1">
                    <i class="bi bi-x-lg me-1"></i>Clear
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="table-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <th class="px-4 py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Request #</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Requester</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Category</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Type / Qty</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Date</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Priority</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Status</th>
                    <th class="py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td class="px-4">
                        <code style="background:#f1f5f9;color:#3b82f6;padding:.2rem .45rem;border-radius:5px;font-size:.78rem;font-weight:700">
                            {{ $req->request_number }}
                        </code>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-700 text-white flex-shrink-0"
                                 style="width:30px;height:30px;font-size:.7rem;background:#6366f1">
                                {{ strtoupper(substr($req->requester->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-600" style="color:#334155">{{ $req->requester->name }}</div>
                                <small class="text-muted">{{ $req->requester->job_title ?? '' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-500" style="color:#334155">
                            {{ $req->category?->name ?? 'General Request' }}
                        </div>
                        <small class="text-muted">{{ Str::limit($req->reason, 45) }}</small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $req->request_type_label }}</span>
                        <div><small class="text-muted">Qty: {{ $req->quantity ?? 1 }}</small></div>
                    </td>
                    <td>
                        <small class="text-muted">{{ $req->request_date->format('d-m-Y') }}</small>
                    </td>
                    <td><span class="badge bg-{{ $req->priority_badge }}">{{ ucfirst($req->priority) }}</span></td>
                    <td><span class="badge bg-{{ $req->status_badge }}">{{ ucfirst($req->status) }}</span></td>
                    <td class="pe-3">
                        <a href="{{ route('admin.requests.show', $req) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Review
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        @include('partials._empty_state', [
                            'icon' => 'bi-clipboard-check',
                            'title' => request()->hasAny(['search', 'status', 'priority']) ? 'No asset requests match these filters' : 'No asset requests yet',
                            'message' => request()->hasAny(['search', 'status', 'priority'])
                                ? 'Clear filters or search by request number, requester or category.'
                                : 'When employees request assets from their portal, review and fulfilment actions will appear here.',
                            'secondaryRoute' => request()->hasAny(['search', 'status', 'priority']) ? route('admin.requests.index') : null,
                        ])
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
    <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between">
        <small class="text-muted">
            Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of {{ $requests->total() }} requests
        </small>
        {{ $requests->links() }}
    </div>
    @endif
</div>

@endsection
