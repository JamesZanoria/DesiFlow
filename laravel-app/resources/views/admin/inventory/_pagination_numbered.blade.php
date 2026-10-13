{{--
    Numbered pagination (1 2 3 ... N), a step up from the plain prev/next
    arrows used elsewhere, for pages where seeing "how many pages total" and
    jumping directly to one is actually useful (Current Stock can run to
    hundreds of ingredients; the Ledger's chronological list doesn't benefit
    from the same treatment the same way, so it keeps plain prev/next).

    Expects: $paginator (any LengthAwarePaginator).

    Every link reuses the existing .pagination-link class and data-ajax-page
    convention, attachPaginationListeners() (already defined in the page's
    own <script>) picks these up automatically after every AJAX refresh, so
    this partial needed zero JS changes to become clickable.
--}}
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    // Always show page 1 and the last page, plus one page on either side of
    // the current one; collapse any gap into a single "..." rather than
    // listing every page, which is what makes this usable at 241 pages.
    $pageNumbers = collect();
    for ($p = 1; $p <= $last; $p++) {
        if ($p === 1 || $p === $last || abs($p - $current) <= 1) {
            $pageNumbers->push($p);
        } elseif ($pageNumbers->last() !== '...') {
            $pageNumbers->push('...');
        }
    }
@endphp
<div class="flex gap-1.5">
    <a href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}"
        data-ajax-page="prev"
        class="pagination-link p-2 rounded-lg border {{ $paginator->onFirstPage() ? 'text-gray-300 border-gray-200 pointer-events-none' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
    </a>

    @foreach ($pageNumbers as $page)
        @if ($page === '...')
            <span class="w-9 h-9 flex items-center justify-center text-sm text-gray-400">…</span>
        @else
            <a href="{{ $paginator->url($page) }}"
                data-ajax-page="{{ $page }}"
                class="pagination-link w-9 h-9 flex items-center justify-center rounded-lg border text-sm font-medium {{ $page === $current ? 'bg-amber-500 text-white border-amber-500' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                {{ $page }}
            </a>
        @endif
    @endforeach

    <a href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}"
        data-ajax-page="next"
        class="pagination-link p-2 rounded-lg border {{ $paginator->hasMorePages() ? 'text-gray-600 border-gray-300 hover:bg-gray-50' : 'text-gray-300 border-gray-200 pointer-events-none' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
    </a>
</div>