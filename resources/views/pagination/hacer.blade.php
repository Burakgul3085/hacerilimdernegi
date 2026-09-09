@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalama" class="flex flex-col items-center justify-between gap-5 border-t border-line pt-8 sm:flex-row">
        <p class="text-sm text-muted">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }} kayıt
        </p>

        <div class="flex flex-wrap items-center justify-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="page-btn page-btn-disabled" aria-disabled="true">
                    <span class="sr-only">Önceki</span>
                    <x-ui.icon name="chevron-right" class="h-4 w-4 rotate-180" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="page-btn">
                    <span class="sr-only">Önceki</span>
                    <x-ui.icon name="chevron-right" class="h-4 w-4 rotate-180" />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-sm text-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="page-btn page-btn-active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="page-btn">
                    <span class="sr-only">Sonraki</span>
                    <x-ui.icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <span class="page-btn page-btn-disabled" aria-disabled="true">
                    <span class="sr-only">Sonraki</span>
                    <x-ui.icon name="chevron-right" class="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
