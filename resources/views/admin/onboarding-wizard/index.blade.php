@extends('layouts.app')
@section('title', 'Setup Wizard')

@section('content')
@php
    $summary = $wizard['summary'];
    $next = $summary['next'];
    $organization = $wizard['organization'];
@endphp

<style>
    .wizard-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e40af 55%, #0f766e 100%);
        border-radius: 8px;
        color: #fff;
        overflow: hidden;
        padding: 1.35rem;
        position: relative;
    }
    .wizard-hero::after {
        background: linear-gradient(135deg, rgba(255,255,255,.18), rgba(255,255,255,0));
        content: '';
        height: 220px;
        position: absolute;
        right: -60px;
        top: -70px;
        transform: rotate(18deg);
        width: 220px;
    }
    .wizard-hero > * { position: relative; z-index: 1; }
    .wizard-progress-ring {
        align-items: center;
        background: conic-gradient(#22c55e calc(var(--progress) * 1%), rgba(255,255,255,.22) 0);
        border-radius: 50%;
        display: inline-flex;
        height: 104px;
        justify-content: center;
        width: 104px;
    }
    .wizard-progress-ring span {
        align-items: center;
        background: rgba(15,23,42,.92);
        border-radius: 50%;
        display: inline-flex;
        font-size: 1.18rem;
        font-weight: 800;
        height: 78px;
        justify-content: center;
        width: 78px;
    }
    .wizard-stage {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 18px rgba(15,23,42,.06);
        height: 100%;
    }
    .wizard-stage-icon {
        align-items: center;
        background: #eff6ff;
        border-radius: 8px;
        color: #2563eb;
        display: inline-flex;
        height: 42px;
        justify-content: center;
        width: 42px;
    }
    .wizard-item {
        align-items: flex-start;
        border-top: 1px solid #eef2f7;
        display: flex;
        gap: .75rem;
        padding: .85rem 0;
    }
    .wizard-item:first-child { border-top: 0; }
    .wizard-status {
        align-items: center;
        border-radius: 50%;
        display: inline-flex;
        flex: 0 0 30px;
        height: 30px;
        justify-content: center;
        width: 30px;
    }
    .wizard-status.done {
        background: #dcfce7;
        color: #15803d;
    }
    .wizard-status.todo {
        background: #fff7ed;
        color: #c2410c;
    }
    .wizard-metric {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        color: #475569;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 700;
        padding: .24rem .55rem;
        white-space: nowrap;
    }
    .wizard-stepbar {
        background: #e2e8f0;
        border-radius: 999px;
        height: 7px;
        overflow: hidden;
    }
    .wizard-stepbar span {
        background: linear-gradient(90deg, #2563eb, #22c55e);
        display: block;
        height: 100%;
    }
</style>

<div class="wizard-hero mb-4" data-tour="onboarding-summary">
    <div class="row g-3 align-items-center">
        <div class="col-lg-8">
            <div class="text-uppercase small fw-bold opacity-75 mb-2">Admin onboarding</div>
            <h1 class="mb-2">Setup Wizard</h1>
            <p class="lead mb-3">{{ $organization?->name ?? 'Your organization' }} can follow these steps to become ready for daily operations and production rollout.</p>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-light text-dark">{{ $summary['complete'] }} of {{ $summary['total'] }} steps complete</span>
                <span class="badge bg-light text-dark">{{ $summary['remaining'] }} remaining</span>
                @forelse($wizard['enabled_modules'] as $module)
                    <span class="badge bg-primary-subtle text-primary-emphasis">{{ strtoupper($module) }}</span>
                @empty
                    <span class="badge bg-warning text-dark">No module list found</span>
                @endforelse
            </div>
        </div>
        <div class="col-lg-4 text-lg-end">
            <div class="wizard-progress-ring mb-3" style="--progress: {{ $summary['percent'] }}"><span>{{ $summary['percent'] }}%</span></div>
            @if($next && $next['url'])
                <div>
                    <a href="{{ $next['url'] }}" class="btn btn-light">
                        <i class="bi bi-arrow-right-circle me-1"></i>{{ $next['action'] }}
                    </a>
                    <div class="small opacity-75 mt-2">Next: {{ $next['title'] }}</div>
                </div>
            @else
                <div class="small opacity-75">All visible setup steps are complete.</div>
            @endif
        </div>
    </div>
</div>

<div class="row g-3" data-tour="onboarding-stages">
    @foreach($wizard['stages'] as $stage)
        <div class="col-xl-6">
            <div class="wizard-stage p-3">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="wizard-stage-icon"><i class="bi {{ $stage['icon'] }}"></i></div>
                        <div>
                            <h5 class="mb-1">{{ $stage['title'] }}</h5>
                            <p class="text-muted mb-0">{{ $stage['subtitle'] }}</p>
                        </div>
                    </div>
                    <span class="badge {{ $stage['percent'] === 100 ? 'bg-success' : 'bg-primary' }}">{{ $stage['complete'] }}/{{ $stage['total'] }}</span>
                </div>
                <div class="wizard-stepbar mb-2"><span style="width: {{ $stage['percent'] }}%"></span></div>

                @foreach($stage['items'] as $item)
                    <div class="wizard-item">
                        <span class="wizard-status {{ $item['complete'] ? 'done' : 'todo' }}">
                            <i class="bi {{ $item['complete'] ? 'bi-check-lg' : 'bi-arrow-right' }}"></i>
                        </span>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="fw-semibold">{{ $item['title'] }}</div>
                                <span class="wizard-metric">{{ $item['metric'] }}</span>
                            </div>
                            <p class="text-muted small mb-2">{{ $item['description'] }}</p>
                            @if(! $item['complete'] && $item['url'])
                                <a href="{{ $item['url'] }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>{{ $item['action'] }}
                                </a>
                            @elseif($item['complete'])
                                <span class="badge bg-success-subtle text-success-emphasis">Done</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $item['action'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex flex-wrap gap-2 mt-4" data-tour="onboarding-actions">
    @if(auth()->user()->hasPermission('production.view'))
    <a href="{{ route('admin.production-readiness.index') }}" class="btn btn-primary">
        <i class="bi bi-shield-check me-1"></i>Open Production Readiness
    </a>
    @endif
    @if(auth()->user()->hasPermission('tasks.create'))
    <a href="{{ route('admin.tasks.create') }}" class="btn btn-outline-secondary">
        <i class="bi bi-plus-circle me-1"></i>Create Setup Task
    </a>
    @endif
</div>
@endsection
