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
        'negative_stock' => ['label' => 'Negative Stock', 'badge' => 'bg-purple-100 text-purple-700'],
        'active' => ['label' => 'Active', 'badge' => 'bg-green-100 text-green-700'],
        'low_stock' => ['label' => 'Low Stock', 'badge' => 'bg-amber-100 text-amber-700'],
        'out_of_stock' => ['label' => 'Out of Stock', 'badge' => 'bg-red-100 text-red-700'],
        'expiring_soon' => ['label' => 'Expiring Soon', 'badge' => 'bg-orange-100 text-orange-700'],
        'expired' => ['label' => 'Expired', 'badge' => 'bg-rose-200 text-rose-800'],
    ];
@endphp
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider font-semibold border-b border-gray-200">
                <tr class="h-10">
                    <th class="px-6 py-4 font-semibold">Ingredient</th>
                    <th class="px-6 py-4 font-semibold">Unit</th>
                    <th class="px-6 py-4 font-semibold">Current Stock</th>
                    <th class="px-6 py-4 font-semibold">Unit Cost</th>
                    <th class="px-6 py-4 font-semibold">Threshold</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="inventory-table-body" class="divide-y divide-gray-100">
                @forelse ($ingredients as $ingredient)
                    @php $status = $ingredient->stockStatus(); @endphp
                    <tr id="ingredient-row-{{ $ingredient->ingredient_id }}" class="hover:bg-gray-50 transition-colors duration-150 group">
                        <td class="px-6 py-4 cell-name">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center shadow-sm">
                                    @if ($ingredient->image_url)
                                        <img src="{{ $ingredient->image_url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 10.5h.008v.008H18V10.5zm-12 9h12a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018 4.5H6a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 006 19.5z" /></svg>
                                    @endif
                                </div>
                                <span class="font-semibold text-gray-900 cell-name-text truncate">{{ $ingredient->ingredient_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-500 cell-unit">{{ $ingredient->unit_of_measurement }}</td>
                        <td class="px-6 py-4 cell-stock {{ $status !== 'active' ? 'text-red-600 font-semibold' : 'text-gray-900' }}">{{ number_format($ingredient->current_stock ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-gray-900 font-medium cell-cost">₱{{ number_format($ingredient->unit_cost, 2) }}</td>
                        <td class="px-6 py-4 text-gray-500 cell-threshold">{{ number_format($ingredient->restock_threshold, 2) }}</td>
                        <td class="px-6 py-4 cell-status">
                            <div class="flex items-center gap-2">
                                @php
                                    $status = $ingredient->stockStatus();
                                    $isExpiryStatus = ($status === 'expired' || $status === 'expiring_soon');
                                    $dotColor = $status === 'expired' ? 'bg-rose-600' : 'bg-orange-500';
                                    $borderColor = $status === 'expired' ? 'border-rose-300' : 'border-orange-200';
                                @endphp

                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $statusMeta[$status]['badge'] }} {{ $isExpiryStatus ? $borderColor : 'border-transparent' }} border">
                                        @if($isExpiryStatus)
                                            <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                        @endif
                                        {{ $statusMeta[$status]['label'] }}
                                    </span>

                                    @if($status === 'low_stock')
                                        <span class="text-[11px] font-medium text-gray-600">
                                            {{ number_format($ingredient->current_stock ?? 0, 2) }} {{ $ingredient->unit_of_measurement }} remaining
                                        </span>
                                    @endif
                                </div>

                                @if($isExpiryStatus)
                                    <button type="button"
                                        class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                                        onclick="showWasteDetails(event, {{ json_encode([
                                            'name' => $ingredient->ingredient_name,
                                            'unit' => $ingredient->unit_of_measurement,
                                            'expired' => number_format($ingredient->expiredQuantity(), 2),
                                            'expiring' => number_format($ingredient->expiringSoonQuantity(), 2)
                                        ]) }})"
                                        title="View waste details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                @if ($status === 'expired')
                                    <button type="button"
                                        class="mark-waste-btn p-2 rounded-lg text-gray-400 hover:text-rose-700 hover:bg-rose-50 transition-colors"
                                        title="Mark expired stock as waste"
                                        data-id="{{ $ingredient->ingredient_id }}"
                                        data-name="{{ $ingredient->ingredient_name }}"
                                        data-current-stock="{{ $ingredient->current_stock ?? 0 }}"
                                        data-unit="{{ $ingredient->unit_of_measurement }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </button>
                                @endif
                                <button type="button"
                                    class="edit-ingredient-btn p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                    title="Edit"
                                    data-id="{{ $ingredient->ingredient_id }}"
                                    data-name="{{ $ingredient->ingredient_name }}"
                                    data-unit="{{ $ingredient->unit_of_measurement }}"
                                    data-cost="{{ $ingredient->unit_cost }}"
                                    data-threshold="{{ $ingredient->restock_threshold }}"
                                    data-current-stock="{{ $ingredient->current_stock ?? 0 }}"
                                    data-linked-item-ids="{{ $ingredient->recipeIngredients()->where('is_active', true)->pluck('menu_item_id')->implode(',') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                </button>
                                <button type="button"
                                    class="delete-ingredient-btn p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    title="Delete" data-id="{{ $ingredient->ingredient_id }}" data-name="{{ $ingredient->ingredient_name }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="inventory-empty-row">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4-8-4" /></svg>
                                <p class="text-sm">{{ (request('search') || request('category')) ? 'No ingredients match your search or filter.' : 'No ingredients yet. Click "Add Ingredient" to create one.' }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p id="inventory-empty-state" class="px-4 py-6 text-center text-gray-400 {{ $ingredients->isNotEmpty() ? 'hidden' : '' }}">
        {{-- Handled inside the table tbody for better layout consistency --}}
    </p>
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50/30">
        <p id="inventory-pagination-summary" class="text-xs text-gray-500 font-medium uppercase tracking-wider">
            Showing {{ $ingredients->firstItem() ?? 0 }} to {{ $ingredients->lastItem() ?? 0 }} of {{ number_format($ingredients->total()) }} entries
        </p>
        @include('admin.inventory._pagination_numbered', ['paginator' => $ingredients])
    </div>
</div>