@extends('layouts.app')
@section('title', 'My Software')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-display me-2 text-primary"></i>My Software</h4>
        <p>Software licenses assigned to you by your organization.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('staff.software-requests.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-clock-history me-1"></i>My Requests
        </a>
        <a href="{{ route('staff.software-requests.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Request Software
        </a>
    </div>
</div>

@if($usageReviews->isNotEmpty())
<div class="table-card mb-3" style="border-left:4px solid #f59e0b">
    <div class="card-body p-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <i class="bi bi-question-circle-fill text-warning fs-4"></i>
            <div><h6 class="mb-1">Please confirm your software needs</h6><p class="text-muted small mb-0">IT noticed that the following software has not been used recently. Confirm whether you still need it or release the license for another employee.</p></div>
        </div>
        @foreach($usageReviews as $review)
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
            <div>
                <div class="fw-bold">{{ $review->assignment?->license?->software?->name ?? 'Unknown software' }}</div>
                <div class="text-muted small">Last used: {{ $review->last_used_date?->format('d M Y') ?? 'No recent usage recorded' }} &middot; Respond by {{ $review->due_date?->format('d M Y') ?? 'as soon as possible' }}</div>
                @if($review->notes)<div class="small mt-1">{{ $review->notes }}</div>@endif
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#retainUsage{{ $review->id }}">I Still Need It</button>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#releaseUsage{{ $review->id }}">Release License</button>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Search --}}
<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <div class="input-group" style="max-width:260px">
                <span class="input-group-text bg-white border-end-0" style="border:1.5px solid #e2e8f0;border-radius:9px 0 0 9px">
                    <i class="bi bi-search text-muted" style="font-size:.85rem"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search software…"
                       class="form-control border-start-0" style="border-radius:0 9px 9px 0">
            </div>
            <button class="btn btn-primary btn-sm">Search</button>
            @if(request('search'))
                <a href="{{ route('staff.my-software.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i>Clear
                </a>
            @endif
        </form>
    </div>
</div>

@if($assignments->isEmpty())
    <div class="table-card text-center py-5">
        <i class="bi bi-display" style="font-size:2.5rem;color:#cbd5e1"></i>
        <div class="mt-3 fw-700" style="color:#94a3b8">No software assigned to you yet</div>
        <p class="text-muted small mt-1 mb-0">Your admin will assign software licenses here when needed.</p>
    </div>
@else
    <div class="row g-3">
        @foreach($assignments as $asgn)
        @php $sw = optional($asgn->license)->software; $lic = $asgn->license; @endphp
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:16px;overflow:hidden">
                <div style="height:4px;background:linear-gradient(90deg,#3b82f6,#6366f1)"></div>
                <div class="card-body p-3">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        @if($sw && $sw->icon)
                            <img src="{{ Storage::url($sw->icon) }}" alt="{{ $sw->name }}"
                                 style="width:44px;height:44px;border-radius:10px;object-fit:cover;flex-shrink:0">
                        @else
                            <div style="width:44px;height:44px;border-radius:10px;flex-shrink:0;
                                        background:linear-gradient(135deg,#3b82f6,#6366f1);
                                        display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem">
                                <i class="bi {{ $sw ? $sw->category_icon : 'bi-display-fill' }}"></i>
                            </div>
                        @endif
                        <div style="min-width:0">
                            <div class="fw-700 text-truncate" style="color:#0f172a;font-size:.9rem">{{ optional($sw)->name ?? 'Unknown Software' }}</div>
                            @if($sw && $sw->vendor)
                                <div class="text-muted" style="font-size:.75rem">{{ $sw->vendor }}</div>
                            @endif
                            @if($sw)
                                <span class="badge rounded-pill mt-1" style="font-size:.67rem;background:#eff6ff;color:#2563eb">
                                    {{ $sw->category_label }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row g-2 mb-3" style="font-size:.78rem">
                        <div class="col-6">
                            <div style="color:#94a3b8;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">License Type</div>
                            <div class="fw-500" style="color:#334155">{{ optional($lic)->license_type_label ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div style="color:#94a3b8;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Assigned</div>
                            <div class="fw-500" style="color:#334155">{{ $asgn->assigned_date->format('d-m-Y') }}</div>
                        </div>
                        @if($lic && $lic->expiry_date)
                        <div class="col-6">
                            <div style="color:#94a3b8;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Expires</div>
                            <div class="fw-600" style="color:{{ $lic->is_expired ? '#dc2626' : ($lic->is_expiring_soon ? '#d97706' : '#334155') }}">
                                {{ $lic->expiry_date->format('d-m-Y') }}
                            </div>
                        </div>
                        @endif
                        @if($sw && $sw->version)
                        <div class="col-6">
                            <div style="color:#94a3b8;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Version</div>
                            <div class="fw-500" style="color:#334155">{{ $sw->version }}</div>
                        </div>
                        @endif
                    </div>

                    {{-- Status badge --}}
                    <div class="d-flex align-items-center justify-content-between">
                        @if($lic && $lic->is_expired)
                            <span class="badge bg-danger">Expired</span>
                        @elseif($lic && $lic->is_expiring_soon)
                            <span class="badge bg-warning text-dark">Expiring Soon</span>
                        @else
                            <span class="badge bg-success">Active</span>
                        @endif

                        @if($sw && $sw->publisher_website)
                            <a href="{{ $sw->publisher_website }}" target="_blank"
                               class="btn btn-sm btn-outline-primary" style="font-size:.75rem">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Website
                            </a>
                        @endif
                    </div>

                    @if($asgn->notes)
                    <div class="mt-2 pt-2 border-top" style="font-size:.75rem;color:#64748b">
                        <i class="bi bi-chat-left-text me-1"></i>{{ $asgn->notes }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($assignments->hasPages())
        <div class="d-flex align-items-center justify-content-between mt-4">
            <div class="text-muted" style="font-size:.8rem">
                Showing {{ $assignments->firstItem() }}–{{ $assignments->lastItem() }} of {{ $assignments->total() }}
            </div>
            {{ $assignments->links() }}
        </div>
    @endif
@endif

@foreach($usageReviews as $review)
<div class="modal fade" id="retainUsage{{ $review->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('staff.software-usage-reviews.retain', $review) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Keep {{ $review->assignment?->license?->software?->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Why do you still need this software?</label><textarea name="decision_notes" class="form-control" rows="4" required minlength="5" placeholder="Mention the work, project, or upcoming task that requires it"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Keep My License</button></div></form></div></div>
<div class="modal fade" id="releaseUsage{{ $review->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('staff.software-usage-reviews.release', $review) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Release {{ $review->assignment?->license?->software?->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-info small">The license will be removed from My Software and returned to the available pool.</div><label class="form-label">Release Note</label><textarea name="decision_notes" class="form-control" rows="3" required minlength="5" placeholder="Example: This project is complete and I no longer use the software."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Release License</button></div></form></div></div>
@endforeach
@endsection
