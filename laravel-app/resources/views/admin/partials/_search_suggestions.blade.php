{{--
    Suggestion button list for the search-with-suggestions dropdown, split
    out from _filter_search.blade.php so a controller can re-render just this
    piece after data changes (a new/renamed/deleted item), using the exact
    same template as the initial page load, rather than duplicating this
    markup as a JavaScript string (which is exactly the kind of thing that
    quietly drifts out of sync the next time this template changes).

    Expects: $suggestions (flat list of strings).
--}}
@foreach ($suggestions as $suggestion)
    <button type="button" class="search-suggestion w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 truncate" data-value="{{ $suggestion }}">{{ $suggestion }}</button>
@endforeach
<p class="search-suggest-empty hidden px-3 py-4 text-center text-xs text-gray-400">No matches.</p>