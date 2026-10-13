{{--
    Reusable search box, search icon, text input, inline clear (x) button,
    and an optional live suggestions dropdown.

    Usage (unchanged, no suggestions):
        @include('admin.partials._filter_search', [
            'name' => 'search',
            'placeholder' => 'Search ingredients...',
            'value' => request('search'),
        ])

    Usage (with suggestions):
        @include('admin.partials._filter_search', [
            'name' => 'search',
            'placeholder' => 'Search menu items...',
            'value' => request('search'),
            'suggestions' => $allItemNames,   // flat list of strings
        ])

    Suggestions only appear once the admin has typed at least one character,
    not on empty focus, and are filtered entirely client-side against the
    full list passed in, so there's no per-keystroke network round trip. That
    fits a small single-location menu's realistic size (SRS scopes this to
    one restaurant) and keeps search working even if the connection to the
    local server is briefly slow.

    Must be placed inside the page's <form method="GET">, the clear button
    and clicking a suggestion both submit that surrounding form. Behavior
    lives in admin.js (delegated), not here.
--}}
@php
    $suggestions = $suggestions ?? collect();
@endphp
<div class="relative" @if ($suggestions->isNotEmpty()) data-search-suggest @endif>
    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>

    <input type="text" name="{{ $name }}" value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        class="{{ $suggestions->isNotEmpty() ? 'search-suggest-input' : '' }} border rounded-lg pl-9 {{ $value ? 'pr-8' : 'pr-3' }} py-2 text-sm w-56">

    @if ($value)
        <button type="button"
            class="filter-chip-clear absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            data-clear-field="{{ $name }}"
            title="Clear search">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    @endif

    @if ($suggestions->isNotEmpty())
        <div class="search-suggest-list hidden absolute left-0 mt-1 w-full bg-white border rounded-lg shadow-lg z-20 max-h-64 overflow-y-auto py-1">
            @include('admin.partials._search_suggestions', ['suggestions' => $suggestions])
        </div>
    @endif
</div>