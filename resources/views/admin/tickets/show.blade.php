@extends('layouts.app')
@section('title', $ticket->ticket_number)

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <a href="{{ route('admin.tickets.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Support Tickets</a>
        <h4>{{ $ticket->ticket_number }}</h4>
        <p>{{ $ticket->subject }}</p>
    </div>
    <span class="badge fs-6 mt-1 bg-{{ $ticket->status_badge }}">{{ $ticket->status_label }}</span>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- LEFT: Conversation thread --}}
    <div class="col-lg-8">

        {{-- Original ticket --}}
        <div class="form-card mb-3">
            <div class="form-card-header" style="justify-content:space-between">
                <div style="display:flex;align-items:center;gap:.6rem">
                    <span class="icon-wrap icon-blue"><i class="bi {{ $ticket->category_icon }}"></i></span>
                    <div>
                        <div style="font-weight:700;color:#0f172a;font-size:.9rem">{{ $ticket->subject }}</div>
                        <div style="font-size:.75rem;color:#94a3b8">
                            {{ $ticket->requester->name }}
                            @if($ticket->requester->job_title) — {{ $ticket->requester->job_title }} @endif
                            · {{ $ticket->created_at->format('d-m-Y H:i') }}
                        </div>
                    </div>
                </div>
                <span class="badge bg-{{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span>
            </div>
            <div class="form-card-body">
                <div style="white-space:pre-line;color:#334155;font-size:.875rem;line-height:1.7">{{ $ticket->description }}</div>
                @if($ticket->asset)
                <div class="mt-3 pt-3 border-top d-flex align-items-center gap-2" style="font-size:.82rem;color:#64748b">
                    <i class="bi bi-box-seam text-primary"></i>
                    Related asset: <strong>{{ $ticket->asset->name }}</strong>
                    @if($ticket->asset->asset_tag) ({{ $ticket->asset->asset_tag }}) @endif
                </div>
                @endif
                @include('partials._ticket_attachments', ['attachments' => $ticket->attachments])
            </div>
        </div>

        {{-- Replies --}}
        @foreach($replies as $reply)
        @php
            $isAdmin = in_array($reply->user->role, ['admin', 'super_admin']);
            $isMe    = $reply->user_id === auth()->id();
        @endphp
        <div class="mb-3 d-flex gap-3 {{ $isAdmin ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0" style="width:36px;height:36px;border-radius:50%;background:{{ $isAdmin ? '#dbeafe' : '#f1f5f9' }};color:{{ $isAdmin ? '#1d4ed8' : '#475569' }};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem">
                {{ strtoupper(substr($reply->user->name, 0, 1)) }}
            </div>
            <div style="max-width:82%">
                @if($reply->is_internal)
                <div class="rounded-3 px-3 py-2"
                     style="background:#fffbeb;border:1.5px dashed #fde68a">
                    <div style="font-size:.7rem;color:#d97706;margin-bottom:.2rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px">
                        <i class="bi bi-eye-slash me-1"></i>Internal Note
                    </div>
                    <div style="font-size:.72rem;color:#94a3b8;margin-bottom:.25rem">
                        <strong style="color:#334155">{{ $reply->user->name }}</strong> · {{ $reply->created_at->format('d-m-Y H:i') }}
                    </div>
                    <div style="font-size:.875rem;color:#334155;white-space:pre-line;line-height:1.6">{{ $reply->message }}</div>
                    @include('partials._ticket_attachments', ['attachments' => $reply->attachments])
                </div>
                @else
                <div class="rounded-3 px-3 py-2"
                     style="background:{{ $isAdmin ? '#eff6ff' : '#f8fafc' }};border:1.5px solid {{ $isAdmin ? '#bfdbfe' : '#e2e8f0' }}">
                    <div style="font-size:.72rem;color:#94a3b8;margin-bottom:.25rem">
                        <strong style="color:#334155">{{ $reply->user->name }}</strong>
                        @if($isAdmin) <span class="badge bg-primary" style="font-size:.65rem">Admin</span> @endif
                        · {{ $reply->created_at->format('d-m-Y H:i') }}
                    </div>
                    <div style="font-size:.875rem;color:#334155;white-space:pre-line;line-height:1.6">{{ $reply->message }}</div>
                    @include('partials._ticket_attachments', ['attachments' => $reply->attachments])
                </div>
                @endif
            </div>
        </div>
        @endforeach

        {{-- Admin reply box --}}
        @if($ticket->is_open)
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-green"><i class="bi bi-reply"></i></span>
                Reply to Ticket
            </div>
            <div class="form-card-body">
                <form action="{{ route('admin.tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <textarea name="message" class="form-control @error('message') is-invalid @enderror"
                              rows="4" required
                              placeholder="Type your reply to the staff member…"></textarea>
                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror

                    <div class="mt-3">
                        @include('partials._attachment_upload', ['inputName' => 'attachments[]', 'zoneId' => 'admin-reply'])
                        @error('attachments.*')<div class="text-danger mt-1" style="font-size:.82rem">{{ $message }}</div>@enderror
                    </div>

                    <div class="mt-3 d-flex gap-2 align-items-center justify-content-between">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="isInternal">
                            <label class="form-check-label" for="isInternal" style="font-size:.82rem;color:#64748b">
                                <i class="bi bi-eye-slash me-1"></i>Internal note (only visible to admins)
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @else
        <div class="rounded-3 px-4 py-3 text-center text-muted"
             style="background:#f8fafc;border:1.5px dashed #e2e8f0;font-size:.85rem">
            <i class="bi bi-lock d-block mb-1 fs-5"></i>
            This ticket is <strong>{{ $ticket->status_label }}</strong>.
        </div>
        @endif

    </div>

    {{-- RIGHT: Controls --}}
    <div class="col-lg-4">

        {{-- Status Control --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-amber"><i class="bi bi-toggles"></i></span>
                Update Status
            </div>
            <div class="form-card-body">
                <form action="{{ route('admin.tickets.status', $ticket) }}" method="POST">
                    @csrf @method('PATCH')
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select mb-3">
                        @foreach(['open','in_progress','resolved','closed'] as $s)
                        <option value="{{ $s }}" {{ $ticket->status == $s ? 'selected':'' }}>
                            {{ $s === 'in_progress' ? 'In Progress' : ucfirst($s) }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-warning btn-sm text-dark w-100">
                        <i class="bi bi-check-lg me-1"></i>Update Status
                    </button>
                </form>
            </div>
        </div>

        {{-- Assign --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-purple"><i class="bi bi-person-check"></i></span>
                Assign To
            </div>
            <div class="form-card-body">
                <form action="{{ route('admin.tickets.assign', $ticket) }}" method="POST">
                    @csrf @method('PATCH')
                    <label class="form-label">Admin User</label>
                    <select name="assigned_to" class="form-select mb-3">
                        <option value="">— Unassigned —</option>
                        @foreach($adminUsers as $u)
                        <option value="{{ $u->id }}" {{ $ticket->assigned_to == $u->id ? 'selected':'' }}>
                            {{ $u->name }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-person-check me-1"></i>Save Assignment
                    </button>
                </form>
            </div>
        </div>

        {{-- Ticket Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <span class="icon-wrap icon-slate"><i class="bi bi-info-circle"></i></span>
                Ticket Details
            </div>
            <div class="form-card-body">
                <dl style="font-size:.82rem;margin:0;display:grid;row-gap:.8rem">
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700">Requester</dt>
                        <dd style="margin:0;color:#334155;font-weight:600">{{ $ticket->requester->name }}</dd>
                    </div>
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700">Category</dt>
                        <dd style="margin:0;color:#334155"><i class="bi {{ $ticket->category_icon }} me-1"></i>{{ $ticket->category_label }}</dd>
                    </div>
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700">Priority</dt>
                        <dd style="margin:0"><span class="badge bg-{{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span></dd>
                    </div>
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700">Opened</dt>
                        <dd style="margin:0;color:#64748b">{{ $ticket->created_at->format('d-m-Y H:i') }}</dd>
                    </div>
                    @if($ticket->resolved_at)
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700">Resolved</dt>
                        <dd style="margin:0;color:#64748b">{{ $ticket->resolved_at->format('d-m-Y H:i') }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt style="color:#94a3b8;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700">Replies</dt>
                        <dd style="margin:0;color:#64748b">{{ $replies->count() }} message(s)</dd>
                    </div>
                </dl>
            </div>
        </div>

    </div>
</div>
@endsection
