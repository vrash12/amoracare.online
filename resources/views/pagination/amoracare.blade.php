@if ($paginator->hasPages())
<nav class="ac-pagination" aria-label="Pagination">
    <div class="ac-pagination-controls">
        @if ($paginator->onFirstPage())
            <span class="ac-page-link" aria-disabled="true">Previous</span>
        @else
            <a class="ac-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        @foreach ($elements ?? [] as $element)
            @if (is_string($element))
                <span class="ac-page-gap" aria-hidden="true">{{ $element }}</span>
            @else
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="ac-page-link" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                    @else
                        <a class="ac-page-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="ac-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="ac-page-link" aria-disabled="true">Next</span>
        @endif
    </div>
    @if (method_exists($paginator, 'total'))
        <p class="ac-pagination-summary">Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results</p>
    @endif
</nav>
@endif
