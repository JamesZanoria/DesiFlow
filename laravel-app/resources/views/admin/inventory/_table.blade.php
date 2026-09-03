{{--
    Table + pagination fragment for Inventory Management (Current Stock tab).

    Rendered two ways from InventoryController@index:
      1. Normal page load, included directly inside admin.inventory.index.
      2. AJAX refresh, returned alone (as HTML inside a JSON envelope) after
         an Add/Edit/Delete, or when a pagination link is clicked. This keeps
         pagination accurate automatically, without a manual reload.
--}}
@php
    $statusMeta = [
        'active' => ['label' => 'Active', 'badge' => 'bg-green-100 text-green-700'],
        'low_stock' => ['label' => 'Low Stock', 'badge' => 'bg-amber-100 text-amber-700'],
        'out_of_stock' => ['label' => 'Out of Stock', 'badge' => 'bg-red-100 text-red-700'],
        'expiring_soon' => ['label' => 'Expiring Soon', 'badge' => 'bg-orange-100 text-orange-700'],
        'expired' => ['label' => 'Expired', 'badge' => 'bg-rose-200 text-rose-800'],
    ];
@endphp
<div class="bg-white border rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="text-gray-500 text-xs border-b bg-gray-50">
            <tr>
                <th class="px-4 py-3 font-medium">ID</th>
                <th class="px-4 py-3 font-medium">Ingredient</th>
                <th class="px-4 py-3 font-medium">Unit</th>
                <th class="px-4 py-3 font-medium">Current Stock</th>
                <th class="px-4 py-3 font-medium">Unit Cost</th>
                <th class="px-4 py-3 font-medium">Threshold</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody id="inventory-table-body" class="divide-y">
            @forelse ($ingredients as $ingredient)
                @php $status = $ingredient->stockStatus(); @endphp
                <tr id="ingredient-row-{{ $ingredient->ingredient_id }}">
                    <td class="px-4 py-3 text-gray-400 cell-id">#ITM-{{ str_pad($ingredient->ingredient_id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3 cell-name">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                @if ($ingredient->image_url)
                                    <img src="{{ $ingredient->image_url }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 10.5h.008v.008H18V10.5zm-12 9h12a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018 4.5H6a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 006 19.5z" /></svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 cell-name-text truncate">{{ $ingredient->ingredient_name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $ingredient->category ?? 'Uncategorized' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 cell-unit">{{ $ingredient->unit_of_measurement }}</td>
                    <td class="px-4 py-3 cell-stock {{ $status !== 'active' ? 'text-red-600 font-semibold' : 'text-gray-600' }}">{{ number_format($ingredient->current_stock ?? 0, 2) }}</td>
                    <td class="px-4 py-3 text-gray-600 cell-cost">₱{{ number_format($ingredient->unit_cost, 2) }}</td>
                    <td class="px-4 py-3 text-gray-600 cell-threshold">{{ number_format($ingredient->restock_threshold, 2) }}</td>
                    <td class="px-4 py-3 cell-status">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $statusMeta[$status]['badge'] }}">{{ $statusMeta[$status]['label'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if ($status === 'expired')
                            <button type="button"
                                class="mark-waste-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-rose-700 hover:bg-rose-50"
                                title="Mark expired stock as waste"
                                data-id="{{ $ingredient->ingredient_id }}"
                                data-name="{{ $ingredient->ingredient_name }}"
                                data-current-stock="{{ $ingredient->current_stock ?? 0 }}"
                                data-unit="{{ $ingredient->unit_of_measurement }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </button>
                        @endif
                        <button type="button"
                            class="edit-ingredient-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                            title="Edit"
                            data-id="{{ $ingredient->ingredient_id }}"
                            data-name="{{ $ingredient->ingredient_name }}"
                            data-category="{{ $ingredient->category }}"
                            data-unit="{{ $ingredient->unit_of_measurement }}"
                            data-cost="{{ $ingredient->unit_cost }}"
                            data-threshold="{{ $ingredient->restock_threshold }}"
                            data-expiration-date="{{ optional($ingredient->expiryDate())->toDateString() }}"
                            data-current-stock="{{ $ingredient->current_stock ?? 0 }}"
                            data-image-url="{{ $ingredient->image_url }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                        </button>
                        <button type="button"
                            class="delete-ingredient-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50"
                            title="Delete"
                            data-id="{{ $ingredient->ingredient_id }}"
                            data-name="{{ $ingredient->ingredient_name }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                        </button>
                    </td>
                </tr>
            @empty
            @endforelse
        </tbody>
    </table>
    <p id="inventory-empty-state" class="px-4 py-6 text-center text-gray-400 {{ $ingredients->isNotEmpty() ? 'hidden' : '' }}">
        @if (request('search') || request('category'))
            No ingredients match your search or filter.
        @else
            No ingredients yet. Click "Add Ingredient" to create one.
        @endif
    </p>

    <div class="flex items-center justify-between px-4 py-3 border-t">
        <p id="inventory-pagination-summary" class="text-sm text-gray-500">
            Showing {{ $ingredients->firstItem() ?? 0 }} to {{ $ingredients->lastItem() ?? 0 }} of {{ number_format($ingredients->total()) }} entries
        </p>
        @include('admin.inventory._pagination_numbered', ['paginator' => $ingredients])
    </div>
</div>