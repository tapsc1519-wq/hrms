@extends('layouts.app')
@section('title', 'Welcome to OpsBridge')

@section('content')
@php
    $organization = $user->organization;
    $summary = $wizard['summary'];
    $next = $summary['next'];
@endphp

<style>
    .welcome-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 56%, #0f766e 100%);
        border-radius: 8px;
        color: #fff;
        margin-bottom: 1rem;
        padding: 1.35rem;
    }
    .welcome-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 18px rgba(15,23,42,.06);
        height: 100%;
        padding: 1rem;
    }
    .welcome-icon {
        align-items: center;
        border-radius: 8px;
        display: inline-flex;
        height: 42px;
        justify-content: center;
        margin-bottom: .75rem;
        width: 42px;
    }
    .welcome-step-number {
        align-items: center;
        background: #eff6ff;
        border-radius: 999px;
        color: #1d4ed8;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 800;
        height: 26px;
        justify-content: center;
        width: 26px;
    }
    .welcome-progress {
        background: rgba(255,255,255,.18);
        border-radius: 999px;
        height: 8px;
        overflow: hidden;
    }
    .welcome-progress span {
        background: #22c55e;
        display: block;
        height: 100%;
    }
</style>

<div class="welcome-hero" data-tour="admin-welcome-hero">
    <div class="row g-3 align-items-center">
        <div class="col-lg-8">
            <div class="small text-uppercase fw-bold opacity-75 mb-2">First login</div>
            <h1 class="mb-2">Welcome to OpsBridge, {{ $user->name }}</h1>
            <p class="lead mb-0">{{ $organization?->name ?? 'Your organization' }} is ready. Start with these steps so the portal feels clear from day one.</p>
        </div>
        <div class="col-lg-4">
            <div class="d-flex justify-content-between small fw-bold mb-2">
                <span>Setup progress</span>
                <span>{{ $summary['percent'] }}%</span>
            </div>
            <div class="welcome-progress"><span style="width: {{ $summary['percent'] }}%"></span></div>
            <div class="small opacity-75 mt-2">{{ $summary['complete'] }} of {{ $summary['total'] }} setup steps complete</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3" data-tour="admin-welcome-steps">
    <div class="col-lg-4">
        <div class="welcome-card">
            <span class="welcome-icon bg-primary-subtle text-primary"><i class="bi bi-shield-lock"></i></span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="welcome-step-number">1</span>
                <h5 class="mb-0">Secure Your Login</h5>
            </div>
            <p class="text-muted small">If you received a temporary password, change it before inviting more users or configuring modules.</p>
            <a href="{{ route('account.password.edit') }}" class="btn {{ $user->must_change_password ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                <i class="bi bi-key me-1"></i>Change Password
            </a>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="welcome-card">
            <span class="welcome-icon bg-success-subtle text-success"><i class="bi bi-signpost-split"></i></span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="welcome-step-number">2</span>
                <h5 class="mb-0">Open Setup Wizard</h5>
            </div>
            <p class="text-muted small">The wizard shows facilities, departments, employees, assets, software and go-live steps in the right order.</p>
            <a href="{{ route('admin.onboarding-wizard.index') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-right-circle me-1"></i>{{ $next['action'] ?? 'Start Setup' }}
            </a>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="welcome-card">
            <span class="welcome-icon bg-info-subtle text-info"><i class="bi bi-hdd-network"></i></span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="welcome-step-number">3</span>
                <h5 class="mb-0">Prepare Device Agent</h5>
            </div>
            <p class="text-muted small">Download the agent when you are ready to enroll Windows, macOS or Linux endpoints for inventory.</p>
            <a href="{{ route('admin.agent-sources.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-download me-1"></i>Open Agent Download
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="welcome-card" data-tour="admin-welcome-checklist">
            <h5 class="mb-3">Recommended First Day Checklist</h5>
            <div class="d-grid gap-2">
                @foreach([
                    'Confirm organization name, contact and billing details with Niyantron team.',
                    'Create facilities and departments before adding employees.',
                    'Add employees and assign clear roles before sharing portal access.',
                    'Load assets and software licenses before endpoint rollout.',
                    'Use Production Readiness before live usage starts.'
                ] as $item)
                    <div class="d-flex align-items-start gap-2 p-2 rounded border bg-light">
                        <i class="bi bi-check2-circle text-success"></i>
                        <div class="small">{{ $item }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="welcome-card" data-tour="admin-welcome-actions">
            <h5 class="mb-3">Continue</h5>
            <div class="d-grid gap-2">
                <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-people me-1"></i>Add Employees
                </a>
                <a href="{{ route('admin.production-readiness.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-shield-check me-1"></i>Review Production Readiness
                </a>
                <form method="POST" action="{{ route('admin.welcome.dismiss') }}">
                    @csrf
                    <button class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-speedometer2 me-1"></i>Continue to Dashboard
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
