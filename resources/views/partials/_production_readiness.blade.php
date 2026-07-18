@php
    $totals = $readiness['totals'];
    $score = $readiness['score'];
    $scoreClass = $totals['fail'] > 0 ? 'danger' : ($totals['warn'] > 0 ? 'warning' : 'success');
    $statusMeta = [
        'pass' => ['label' => 'Ready', 'class' => 'success', 'icon' => 'bi-check-circle-fill'],
        'warn' => ['label' => 'Needs Review', 'class' => 'warning', 'icon' => 'bi-exclamation-triangle-fill'],
        'fail' => ['label' => 'Blocker', 'class' => 'danger', 'icon' => 'bi-x-circle-fill'],
    ];
@endphp

<style>
    .readiness-wrap { font-size: .84rem; }
    .readiness-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 54%, #0f766e 100%);
        border-radius: 14px;
        color: #fff;
        padding: 1.25rem 1.35rem;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .18);
    }
    .readiness-hero h4 { font-size: 1.18rem; font-weight: 850; margin: 0; }
    .readiness-hero p { color: rgba(226, 232, 240, .86); font-size: .8rem; margin: .25rem 0 0; }
    .readiness-score {
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.24);
        border-radius: 13px;
        min-width: 160px;
        padding: .75rem;
        text-align: right;
    }
    .readiness-score .value { font-size: 1.55rem; font-weight: 900; line-height: 1; }
    .readiness-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .055);
    }
    .readiness-check {
        align-items: flex-start;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        gap: .75rem;
        padding: .86rem 1rem;
    }
    .readiness-check:last-child { border-bottom: 0; }
    .readiness-icon {
        align-items: center;
        border-radius: 10px;
        display: inline-flex;
        flex-shrink: 0;
        height: 34px;
        justify-content: center;
        width: 34px;
    }
    .readiness-title { color: #0f172a; font-size: .84rem; font-weight: 850; line-height: 1.25; }
    .readiness-message { color: #64748b; font-size: .74rem; line-height: 1.42; margin-top: .16rem; }
    .summary-tile {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: .9rem 1rem;
    }
    .summary-label { color: #64748b; font-size: .68rem; font-weight: 850; letter-spacing: .05em; text-transform: uppercase; }
    .summary-value { color: #0f172a; font-size: 1.25rem; font-weight: 900; line-height: 1.1; margin-top: .25rem; }
</style>

<div class="readiness-wrap">
    <div class="readiness-hero mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h4>{{ $title }}</h4>
                <p>{{ $subtitle }}</p>
            </div>
            <div class="readiness-score">
                <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;color:rgba(226,232,240,.82)">Readiness Score</div>
                <div class="value">{{ $score }}%</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">Total Checks</div><div class="summary-value">{{ $totals['total'] }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">Ready</div><div class="summary-value text-success">{{ $totals['pass'] }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">Needs Review</div><div class="summary-value text-warning">{{ $totals['warn'] }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="summary-tile"><div class="summary-label">Blockers</div><div class="summary-value text-danger">{{ $totals['fail'] }}</div></div></div>
    </div>

    @if($totals['fail'] > 0)
        <div class="alert alert-danger border-0 rounded-3 shadow-sm">
            <i class="bi bi-shield-exclamation me-2"></i>Resolve blocker items before production launch.
        </div>
    @elseif($totals['warn'] > 0)
        <div class="alert alert-warning border-0 rounded-3 shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>No hard blockers found, but review warning items before onboarding real users.
        </div>
    @else
        <div class="alert alert-success border-0 rounded-3 shadow-sm">
            <i class="bi bi-check2-circle me-2"></i>All readiness checks are passing.
        </div>
    @endif

    <div class="row g-3">
        @foreach($readiness['groups'] as $group => $checks)
            <div class="col-xl-6">
                <div class="readiness-card h-100">
                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <div class="fw-bold" style="color:#0f172a">{{ $group }}</div>
                        <span class="badge bg-light text-dark">{{ count($checks) }} checks</span>
                    </div>
                    @foreach($checks as $check)
                        @php $meta = $statusMeta[$check['status']] ?? $statusMeta['warn']; @endphp
                        <div class="readiness-check">
                            <span class="readiness-icon bg-{{ $meta['class'] }}-subtle text-{{ $meta['class'] }}">
                                <i class="bi {{ $meta['icon'] }}"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between gap-2 align-items-start">
                                    <div>
                                        <div class="readiness-title">{{ $check['title'] }}</div>
                                        <div class="readiness-message">{{ $check['message'] }}</div>
                                    </div>
                                    <span class="badge bg-{{ $meta['class'] }} {{ $check['status'] === 'warn' ? 'text-dark' : '' }}">{{ $meta['label'] }}</span>
                                </div>
                                @if($check['url'])
                                    <a href="{{ $check['url'] }}" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="bi bi-arrow-right me-1"></i>Fix / Review
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
