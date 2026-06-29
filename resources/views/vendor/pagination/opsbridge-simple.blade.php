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
    </nav>
@endif
