{{--
    Inner content of a filter-select popover (the optional search box + the
    option list) — split out from _filter_select.blade.php so a controller
    can re-render just this piece after data changes (e.g. a new menu
    category from an AJAX-created item) without a full page reload, using
    the exact same template as the initial page load. Rendering this
    server-side both times, rather than duplicating the option markup in
    JavaScript, is what guarantees the two can never drift out of sync.

    Expects: $name, $label, $value, $options (same as _filter_select).
--}}
@php
    $showSearch = count($options) > 7;
@endphp
@if ($showSearch)
    <div class="p-2 border-b">
        <input type="text" class="filter-select-search w-full border rounded-md px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-amber-400" placeholder="Search {{ strtolower($label) }}...">
    </div>
@endif

<div class="filter-select-list max-h-64 overflow-y-auto py-1">
    <button type="button" class="filter-select-option w-full flex items-center justify-between gap-2 px-3 py-2 text-xs text-left hover:bg-gray-50 {{ !$value ? 'font-semibold text-gray-800' : 'text-gray-600' }}" data-option-value="" data-option-label="all">
        <span>All</span>
        @if (!$value)
            <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
        @endif
    </button>
    @foreach ($options as $optValue => $optLabel)
        <button type="button" class="filter-select-option w-full flex items-center justify-between gap-2 px-3 py-2 text-xs text-left hover:bg-gray-50 {{ (string) $value === (string) $optValue ? 'font-semibold text-gray-800' : 'text-gray-600' }}" data-option-value="{{ $optValue }}" data-option-label="{{ strtolower($optLabel) }}">
            <span class="truncate">{{ $optLabel }}</span>
            @if ((string) $value === (string) $optValue)
                <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
            @endif
        </button>
    @endforeach
    <p class="filter-select-empty hidden px-3 py-4 text-center text-xs text-gray-400">No matches.</p>
</div>