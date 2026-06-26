@props([
    'title' => 'Need help?',
    'what' => null,
    'how' => [],
    'next' => null,
])

@php
    $helpId = 'helpModal'.md5($title.$what.json_encode($how).$next);
@endphp

<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $helpId }}">
        <i class="bi bi-question-circle me-1"></i>Page Help
    </button>
</div>

<div class="modal fade" id="{{ $helpId }}" tabindex="-1" aria-labelledby="{{ $helpId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="help-panel-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="bi bi-question-circle-fill"></i>
                    </div>
                    <h5 class="modal-title" id="{{ $helpId }}Label">{{ $title }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body help-panel">
                @if($what)
                    <p class="mb-3">{{ $what }}</p>
                @endif
                @if(!empty($how))
                    <div class="help-panel-section-title mb-2">How to use this section</div>
                    <ul>
                        @foreach($how as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ul>
                @endif
                @if($next)
                    <div class="mt-3"><strong>Next:</strong> {{ $next }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
