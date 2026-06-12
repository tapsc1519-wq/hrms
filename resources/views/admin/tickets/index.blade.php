@extends('layouts.app')
@section('title', 'Support Tickets')

@section('content')
<div class="page-header">
    <h4>Support Tickets</h4>
    <p>Manage and respond to staff support requests.</p>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Summary chips --}}
<div class="row g-3 mb-4">
    <div class="col-auto">
        <div class="rounded-3 px-4 py-2 d-flex align-items-center gap-2"
             style="background:#eff6ff;border:1.5px solid #bfdbfe">
            <span style="font-size:1.3rem;font-weight:800;color:#1d4ed8">{{ $counts['open'] }}</span>
            <span style="font-size:.8rem;font-weight:600;color:#3b82f6">Open</span>
        </div>
    </div>
    <div class="col-auto">
        <div class="rounded-3 px-4 py-2 d-flex align-items-center gap-2"
             style="background:#fffbeb;border:1.5px solid #fde68a">
            <span style="font-size:1.3rem;font-weight:800;color:#d97706">{{ $counts['in_progress'] }}</span>
            <span style="font-size:.8rem;font-weight:600;color:#f59e0b">In Progress</span>
        </div>
    </div>
    <div class="col-auto">
        <div class="rounded-3 px-4 py-2 d-flex align-items-center gap-2"
             style="background:#f0fdf4;border:1.5px solid #bbf7d0">
            <span style="font-size:1.3rem;font-weight:800;color:#166534">{{ $counts['resolved'] }}</span>
            <span style="font-size:.8rem;font-weight:600;color:#22c55e">Resolved</span>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="form-card mb-3" style="padding:0">
    <div class="px-4 py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search ticket # or subject…" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(['open','in_progress','resolved','closed'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected':'' }}>
                        {{ $s === 'in_progress' ? 'In Progress' : ucfirst($s) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <option value="fixed_assets"  {{ request('category') == 'fixed_assets'  ? 'selected':'' }}>Fixed Assets</option>
                    <option value="itam_support"  {{ request('category') == 'itam_support'  ? 'selected':'' }}>ITAM Tool Support</option>
                    <option value="other_support" {{ request('category') == 'other_support' ? 'selected':'' }}>Other Support</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    @foreach(['urgent','high','normal','low'] as $p)
                    <option value="{{ $p }}" {{ request('priority') == $p ? 'selected':'' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="form-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <th class="px-4 py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Ticket #</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Subject</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Category</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Requester</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Priority</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Status</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Assigned</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Opened</th>
                    <th class="py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                <tr>
                    <td class="px-4">
                        <span style="font-family:monospace;font-size:.82rem;font-weight:700;color:#3b82f6">
                            {{ $ticket->ticket_number }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold" style="color:#334155;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ $ticket->subject }}
                        </div>
                    </td>
                    <td>
                        @php
                        $catColors = ['fixed_assets'=>['bg'=>'#dbeafe','fg'=>'#1d4ed8'],'itam_support'=>['bg'=>'#f5f3ff','fg'=>'#7c3aed'],'other_support'=>['bg'=>'#f0fdf4','fg'=>'#166534']];
                        $cc = $catColors[$ticket->category] ?? ['bg'=>'#f1f5f9','fg'=>'#475569'];
                        @endphp
                        <span class="badge rounded-pill"
                              style="background:{{ $cc['bg'] }};color:{{ $cc['fg'] }};font-weight:600">
                            <i class="bi {{ $ticket->category_icon }} me-1"></i>{{ $ticket->category_label }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:600;color:#334155">{{ $ticket->requester->name }}</div>
                        <small class="text-muted">{{ $ticket->requester->job_title ?? '' }}</small>
                    </td>
                    <td><span class="badge bg-{{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span></td>
                    <td><span class="badge bg-{{ $ticket->status_badge }}">{{ $ticket->status_label }}</span></td>
                    <td>
                        @if($ticket->assignedTo)
                        <span style="font-size:.8rem;color:#334155">{{ $ticket->assignedTo->name }}</span>
                        @else
                        <span class="text-muted" style="font-size:.8rem">Unassigned</span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:.8rem">{{ $ticket->created_at->format('d M') }}</td>
                    <td>
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <i class="bi bi-headset fs-1 d-block mb-2 opacity-25"></i>
                        <div class="text-muted">No tickets found.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tickets->hasPages())
    <div class="px-4 py-3 border-top">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
