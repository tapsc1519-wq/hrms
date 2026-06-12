@extends('layouts.app')
@section('title', 'My Support Tickets')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>Support Tickets</h4>
        <p>Track your support requests for assets, ITAM tool, and general help.</p>
    </div>
    <a href="{{ route('staff.tickets.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Ticket
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3" role="alert">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="form-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <th class="px-4 py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Ticket #</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Subject</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Category</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Priority</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Status</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Date</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700"></th>
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
                        <div class="fw-semibold" style="color:#334155">{{ $ticket->subject }}</div>
                        @if($ticket->asset)
                        <small class="text-muted"><i class="bi bi-box-seam me-1"></i>{{ $ticket->asset->name }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $ticket->category_color === 'purple' ? 'secondary' : $ticket->category_color }} bg-opacity-10 text-{{ $ticket->category_color === 'purple' ? 'secondary' : $ticket->category_color }}"
                              style="{{ $ticket->category === 'itam_support' ? 'background:#f5f3ff!important;color:#7c3aed!important' : '' }}">
                            <i class="bi {{ $ticket->category_icon }} me-1"></i>{{ $ticket->category_label }}
                        </span>
                    </td>
                    <td><span class="badge bg-{{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span></td>
                    <td>
                        <span class="badge bg-{{ $ticket->status_badge }}">{{ $ticket->status_label }}</span>
                    </td>
                    <td class="text-muted" style="font-size:.8rem">{{ $ticket->created_at->format('d-m-Y') }}</td>
                    <td>
                        <a href="{{ route('staff.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <i class="bi bi-headset fs-1 d-block mb-3 opacity-25"></i>
                        <div class="fw-semibold mb-1" style="color:#334155">No tickets yet</div>
                        <div class="text-muted mb-3" style="font-size:.85rem">Need help? Raise a support ticket.</div>
                        <a href="{{ route('staff.tickets.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i>Create Your First Ticket
                        </a>
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
