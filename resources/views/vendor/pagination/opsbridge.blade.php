@if ($paginator->hasPages())
    <nav class="ops-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="ops-pagination-actions">
            @if ($paginator->onFirstPage())
                <span class="ops-page-step disabled" aria-disabled="true">
                    <i class="bi bi-chevron-left"></i>
                    <span>@lang('pagination.previous')</span>
                </span>
            @else
                <a class="ops-page-step" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    <i class="bi bi-chevron-left"></i>
                    <span>@lang('pagination.previous')</span>
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="ops-page-step" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    <span>@lang('pagination.next')</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            @else
                <span class="ops-page-step disabled" aria-disabled="true">
                    <span>@lang('pagination.next')</span>
                    <i class="bi bi-chevron-right"></i>
                </span>
            @endif
        </div>

        <div class="ops-pagination-meta">
            <div class="ops-pagination-summary">
                {!! __('Showing') !!}
                <span>{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span>{{ $paginator->lastItem() }}</span>
                {!! __('of') !!}
                <span>{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </div>

            <div class="ops-page-list" aria-label="{{ __('Pagination pages') }}">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="ops-page-link disabled" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="ops-page-link active" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="ops-page-link" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>
        </div>
    </nav>
@endif
