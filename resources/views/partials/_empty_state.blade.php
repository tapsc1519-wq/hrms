@php
    $icon = $icon ?? 'bi-inbox';
    $title = $title ?? 'Nothing here yet';
    $message = $message ?? 'Create the first record to get started.';
    $actionRoute = $actionRoute ?? null;
    $actionLabel = $actionLabel ?? 'Get Started';
    $secondaryRoute = $secondaryRoute ?? null;
    $secondaryLabel = $secondaryLabel ?? 'Clear Filters';
@endphp

<div class="text-center py-5 px-3">
    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
         style="width:58px;height:58px;border-radius:16px;background:#eff6ff;color:#2563eb">
        <i class="bi {{ $icon }}" style="font-size:1.65rem"></i>
    </div>
    <div class="fw-bold mb-1" style="color:#0f172a;font-size:.98rem">{{ $title }}</div>
    <div class="text-muted mx-auto" style="max-width:420px;font-size:.84rem;line-height:1.55">{{ $message }}</div>
    @if($actionRoute || $secondaryRoute)
        <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
            @if($actionRoute)
                <a href="{{ $actionRoute }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>{{ $actionLabel }}
                </a>
            @endif
            @if($secondaryRoute)
                <a href="{{ $secondaryRoute }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i>{{ $secondaryLabel }}
                </a>
            @endif
        </div>
    @endif
</div>
