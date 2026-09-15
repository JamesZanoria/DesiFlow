{{--
    Table + pagination fragment for Menu Management.

    This is rendered two ways from MenuController@index:
      1. Normal page load, included directly inside admin.menu.index.
      2. AJAX refresh, returned alone (as HTML inside a JSON envelope) whenever
         the frontend needs to resync the table after an Add/Edit/Delete, or when
         the admin clicks a Prev/Next pagination link. This is what keeps
         pagination accurate automatically, without the admin reloading the page.

    Because it's swapped in wholesale via container.innerHTML on refresh, every
    element inside here (rows, empty state, pagination links) is safe to
    re-query by id/class after a refresh, nothing outside this fragment should
    depend on a cached reference to something inside it.
--}}
<div class="bg-white border rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="text-gray-500 text-xs border-b">
            <tr>
                <th class="px-4 py-3 font-medium">Name</th>
                <th class="px-4 py-3 font-medium">Category</th>
                <th class="px-4 py-3 font-medium">Price</th>
                <th class="px-4 py-3 font-medium">Inventory</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody id="menu-table-body" class="divide-y">
            @forelse ($menuItems as $item)
                <tr id="menu-row-{{ $item->menu_item_id }}">
                    <td class="px-4 py-3 cell-name-cell">
                        <div class="flex items-center gap-3">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" class="w-10 h-10 rounded-lg object-cover bg-gray-100 cell-thumb">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center cell-thumb">
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 20.25H4.5A2.25 2.25 0 012.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v9.75a2.25 2.25 0 01-2.25 2.25zM9 9.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                                </div>
                            @endif
                            <span class="font-semibold text-gray-800 cell-item-name">{{ $item->item_name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 cell-category">{{ $item->category }}</td>
                    <td class="px-4 py-3 text-gray-600 cell-price">₱{{ number_format($item->price, 2) }}</td>
                    <td class="px-4 py-3">
                        @php $invStatus = $item->inventoryAvailable(); @endphp
                        @if($invStatus === 'available')
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Available
                            </span>
                        @elseif($invStatus === 'warning')
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Low Stock
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Unavailable
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 cell-status">
                        @php $details = $item->getAvailabilityDetails(); @endphp
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium {{ $details['is_available'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $details['is_available'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                {{ $details['is_available'] ? 'Active' : 'Unavailable' }}
                            </span>
                            @if(!$details['is_available'])
                                <button type="button"
                                    class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                                    onclick="showAvailabilityDetails(event, {{ $item->menu_item_id }}, {{ json_encode($details) }})"
                                    title="Why unavailable?">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button type="button"
                            class="edit-menu-item-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                            title="Edit"
                            data-id="{{ $item->menu_item_id }}"
                            data-item-name="{{ $item->item_name }}"
                            data-category="{{ $item->category }}"
                            data-price="{{ $item->price }}"
                            data-description="{{ $item->description }}"
                            data-image-url="{{ $item->image_url }}"
                            data-is-available="{{ $item->is_available ? '1' : '0' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                        </button>
                        <button type="button"
                            class="delete-menu-item-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50"
                            title="Delete"
                            data-id="{{ $item->menu_item_id }}"
                            data-item-name="{{ $item->item_name }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                        </button>
                    </td>
                </tr>
            @empty
            @endforelse
        </tbody>
    </table>
    <p id="menu-empty-state" class="px-4 py-6 text-center text-gray-400 {{ $menuItems->isNotEmpty() ? 'hidden' : '' }}">
        @if (request('search') || request('category'))
            No menu items match your search or filter.
        @else
            No menu items yet. Click "Add New Item" to create one.
        @endif
    </p>

    <div class="flex items-center justify-between px-4 py-3 border-t">
        <p id="menu-pagination-summary" class="text-sm text-gray-500">
            Showing {{ $menuItems->firstItem() ?? 0 }} to {{ $menuItems->lastItem() ?? 0 }} of {{ $menuItems->total() }} items
        </p>
        <div class="flex gap-2">
            {{-- .pagination-link + data-ajax-page: the frontend intercepts clicks on
                 these and re-fetches via AJAX (see refreshTable() in index.blade.php)
                 instead of doing a full browser navigation. --}}
            <a href="{{ $menuItems->onFirstPage() ? '#' : $menuItems->previousPageUrl() }}"
                data-ajax-page="prev"
                class="pagination-link p-2 rounded-lg border {{ $menuItems->onFirstPage() ? 'text-gray-300 border-gray-200 pointer-events-none' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </a>
            <a href="{{ $menuItems->hasMorePages() ? $menuItems->nextPageUrl() : '#' }}"
                data-ajax-page="next"
                class="pagination-link p-2 rounded-lg border {{ $menuItems->hasMorePages() ? 'text-gray-600 border-gray-300 hover:bg-gray-50' : 'text-gray-300 border-gray-200 pointer-events-none' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        </div>
    </div>
</div>