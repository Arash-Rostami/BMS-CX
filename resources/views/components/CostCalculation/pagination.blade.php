@props(['paginator'])

@if ($paginator->hasPages())
    <div class="flex items-center justify-between">
        <div class="text-xs">
            Showing {{ $costCalculations->firstItem() ?? 0 }} to {{ $costCalculations->lastItem() ?? 0 }}
            of {{ $costCalculations->total() }} results
        </div>
        <div>
            <nav class="custom-paginator" role="navigation" aria-label="Pagination Navigation">
                <button
                    @if (!$paginator->onFirstPage())
                        wire:click="previousPage"
                    @else
                        disabled
                    @endif
                    class="page-btn prev-btn"
                >
                    «
                </button>

                @foreach (range(1, $paginator->lastPage()) as $page)
                    <button
                        wire:click="gotoPage({{ $page }})"
                        @if ($page === $paginator->currentPage()) aria-current="page" @endif
                        class="page-btn {{ $page === $paginator->currentPage() ? 'active' : '' }}"
                    >
                        {{ $page }}
                    </button>
                @endforeach

                {{-- Next --}}
                <button
                    @if ($paginator->hasMorePages())
                        wire:click="nextPage"
                    @else
                        disabled
                    @endif
                    class="page-btn next-btn"
                >
                    »
                </button>
            </nav>
        </div>
    </div>
@endif
