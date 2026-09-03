{{--
    Reusable dropdown filter chip, funnel icon + "Label: Value" pill that
    opens a custom popover (not a native <select>) with an optional search box
    and a scroll-capped option list.

    Rebuilt from a native-<select>-based version after long option labels
    (e.g. "Main Course") were visually overlapping the chevron/clear icons.
    Root cause: the text span was a flex child without min-w-0, so it refused
    to shrink and `truncate` never actually engaged, a native <select>'s
    rendered value also doesn't reliably respect text-overflow: ellipsis
    across browsers regardless. This version sidesteps both: the chevron is
    now a normal (non-absolute) flex sibling that can never be overlapped,
    and a hidden <input type="hidden"> carries the actual submitted value, so
    the surrounding GET filter form works exactly as before, no controller
    or route changes needed.

    Usage (identical to the previous version):
        @include('admin.partials._filter_select', [
            'name' => 'role',
            'label' => 'Role',
            'value' => request('role'),
            'options' => ['admin' => 'Admin / Manager', 'cashier' => 'Cashier'],
        ])

    Must be placed inside the page's <form method="GET">, both the clear (x)
    button and option selection submit that surrounding form. The actual
    open/close/search/select behavior lives in admin.js (delegated, so this
    partial can be included any number of times on one page without each
    instance needing its own <script> block).
--}}
@php
    $currentLabel = $value !== null && $value !== '' ? ($options[$value] ?? $value) : 'All';
@endphp
<div class="relative inline-block" data-filter-select data-name="{{ $name }}">
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">

    <button type="button"
        class="filter-select-trigger relative inline-flex items-center gap-1.5 border rounded-full bg-white pl-3 {{ $value ? 'pr-8' : 'pr-3' }} py-1.5 text-sm max-w-[14rem] {{ $value ? 'border-gray-400' : 'border-gray-300' }} hover:border-gray-400"
        aria-haspopup="listbox" aria-expanded="false">
        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
        {{-- min-w-0 is the fix: without it, a flex child won't shrink below
             its content size no matter what truncate/overflow classes it has. --}}
        <span class="min-w-0 truncate">
            <span class="text-gray-500">{{ $label }}:</span>
            <span class="text-gray-800 font-medium">{{ $currentLabel }}</span>
        </span>
        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>

    @if ($value)
        {{-- Sibling of the trigger button, not nested inside it (a <button>
             inside a <button> is invalid HTML and breaks click handling),
             layered on top via DOM order so it always catches its own
             clicks before they'd reach the trigger underneath. --}}
        <button type="button"
            class="filter-chip-clear absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            data-clear-field="{{ $name }}"
            title="Clear {{ strtolower($label) }} filter">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    @endif

    <div class="filter-select-popover hidden absolute left-0 mt-2 w-56 bg-white border rounded-lg shadow-lg z-20 overflow-hidden">
        @include('admin.partials._filter_select_popover', ['name' => $name, 'label' => $label, 'value' => $value, 'options' => $options])
    </div>
</div>