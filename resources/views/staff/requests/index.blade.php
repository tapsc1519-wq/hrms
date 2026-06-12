@extends('layouts.app')
@section('title', 'My Requests')

@section('content')

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4>My Asset Requests</h4>
        <p>{{ $requests->total() }} request{{ $requests->total() !== 1 ? 's' : '' }} submitted</p>
    </div>
    <a href="{{ route('staff.requests.create') }}" class="btn btn-primary">
        <i class="bi bi-clipboard-plus me-1"></i>New Request
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="table-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <th class="px-4 py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Request #</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Category</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Type / Qty</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Requested</th>
                    <th class="py-3" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700">Required By</th>
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
                        <div class="fw-600" style="color:#334155">
                            {{ $req->category?->name ?? 'General Request' }}
                        </div>
                        <small class="text-muted">{{ Str::limit($req->reason, 45) }}</small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $req->request_type_label }}</span>
                        <div><small class="text-muted">Qty: {{ $req->quantity ?? 1 }}</small></div>
                    </td>
                    <td><small class="text-muted">{{ $req->request_date->format('d-m-Y') }}</small></td>
                    <td>
                        @if($req->required_date)
                            <small class="{{ $req->required_date->isPast() && $req->status === 'pending' ? 'text-danger fw-600' : 'text-muted' }}">
                                {{ $req->required_date->format('d-m-Y') }}
                            </small>
                        @else
                            <small class="text-muted">—</small>
                        @endif
                    </td>
                    <td><span class="badge bg-{{ $req->priority_badge }}">{{ ucfirst($req->priority) }}</span></td>
                    <td><span class="badge bg-{{ $req->status_badge }}">{{ ucfirst($req->status) }}</span></td>
                    <td class="pe-3">
                        <div class="d-flex gap-1">
                            <a href="{{ route('staff.requests.show', $req) }}"
                               class="btn btn-sm btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($req->status === 'pending')
                            <form action="{{ route('staff.requests.cancel', $req) }}" method="POST"
                                  onsubmit="return confirm('Cancel this request?')">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-danger" title="Cancel">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-clipboard fs-1 d-block mb-2 opacity-25"></i>
                        <div class="fw-500 mb-1">No requests yet</div>
                        <small class="d-block mb-3">Need an asset? Submit a request and we'll get back to you.</small>
                        <a href="{{ route('staff.requests.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-clipboard-plus me-1"></i>Submit a Request
                        </a>
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
