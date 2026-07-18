@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h5 class="mb-1 fw-bold">Notifications</h5>
            <p class="text-muted mb-0">Updates, approvals, requests, and handover decisions for your account.</p>
        </div>
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            @method('PATCH')
            <button class="btn btn-sm btn-outline-primary" type="submit">
                <i class="bi bi-check2-all me-1"></i> Mark all read
            </button>
        </form>
    </div>

    <div class="list-group list-group-flush">
        @forelse($notifications as $notification)
            <form action="{{ route('notifications.read', $notification) }}" method="POST" class="list-group-item px-0 py-3">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn w-100 text-start p-0 border-0 bg-transparent">
                    <div class="d-flex gap-3 align-items-start">
                        <span class="notification-icon {{ $notification->read_at ? 'bg-light text-muted' : 'bg-primary-subtle text-primary' }}">
                            <i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i>
                        </span>
                        <span class="flex-grow-1">
                            <span class="d-flex justify-content-between gap-3">
                                <span class="fw-bold text-dark">{{ $notification->title }}</span>
                                <span class="small text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                            </span>
                            @if($notification->message)
                                <span class="d-block text-muted small mt-1">{{ $notification->message }}</span>
                            @endif
                        </span>
                    </div>
                </button>
            </form>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-check2-circle d-block fs-1 text-success mb-2"></i>
                No notifications yet.
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
