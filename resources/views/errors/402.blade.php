@extends('layouts.app')
@section('title', 'Subscription Required')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="form-card text-center">
            <div class="form-card-body py-5">
                <div class="icon-wrap icon-amber mx-auto mb-3" style="width:52px;height:52px;font-size:1.35rem">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <h4 style="font-size:1.15rem;font-weight:800;color:#0f172a">Subscription Required</h4>
                <p class="text-muted mx-auto" style="font-size:.88rem;max-width:520px">
                    {{ $exception->getMessage() ?: 'This module requires an active subscription.' }}
                </p>
                @php($user = auth()->user())
                <div class="d-flex justify-content-center gap-2 mt-3">
                    @if($user?->isAdmin() && !request()->routeIs('admin.dashboard'))
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-grid-1x2-fill me-1"></i>Back to Dashboard
                        </a>
                    @elseif($user?->isStaff() && !request()->routeIs('staff.dashboard'))
                        <a href="{{ route('staff.dashboard') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-grid-1x2-fill me-1"></i>Back to Dashboard
                        </a>
                    @else
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="btn btn-primary btn-sm">
                                <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
