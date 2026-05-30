{{-- Loft Quiet pagination for Livewire lists. Usage: {{ $items->links('livewire.quiet-pagination') }} --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Навігація сторінками" class="pager">
        <p class="pager__info">
            Показано <b>{{ $paginator->firstItem() }}</b>–<b>{{ $paginator->lastItem() }}</b> з <b>{{ $paginator->total() }}</b>
        </p>

        <div class="pager__nav">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="pager__btn pager__btn--disabled" aria-disabled="true" aria-label="Попередня">
                    <x-icon.chevron-left width="15" height="15" />
                </span>
            @else
                <button type="button" class="pager__btn pager__arrow" aria-label="Попередня"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">
                    <x-icon.chevron-left width="15" height="15" />
                </button>
            @endif

            {{-- Page numbers (with "…" separators) --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager__dots" aria-hidden="true">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager__btn is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <button type="button" class="pager__btn"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <button type="button" class="pager__btn pager__arrow" aria-label="Наступна"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">
                    <x-icon.chevron-right width="15" height="15" />
                </button>
            @else
                <span class="pager__btn pager__btn--disabled" aria-disabled="true" aria-label="Наступна">
                    <x-icon.chevron-right width="15" height="15" />
                </span>
            @endif
        </div>
    </nav>
@endif
