@extends('layouts.app')

@section('title', 'Stock Movement Ledger')

@section('content')

    @php
        $typeBadgeMeta = [
            'delivery' => ['label' => 'Delivery', 'badge' => 'bg-green-50 text-green-700 border border-green-200'],
            'sale_deduction' => ['label' => 'Sale', 'badge' => 'bg-blue-50 text-blue-700 border border-blue-200'],
            'manual_adjustment' => ['label' => 'Manual Adj', 'badge' => 'bg-amber-50 text-amber-700 border border-amber-200'],
            'spoilage' => ['label' => 'Waste', 'badge' => 'bg-red-50 text-red-700 border border-red-200'],
        ];

        // Deterministic color per recorded-by name (not tied to role — this
        // system only has admin/cashier/kitchen roles, none of which map to
        // the mockup's implied "Manager" distinction) so the same person
        // always gets the same avatar color across the table.
        $avatarPalette = ['bg-amber-500', 'bg-blue-500', 'bg-purple-500', 'bg-emerald-500', 'bg-rose-500', 'bg-indigo-500'];
    @endphp

    <div class="flex items-start justify-between mb-4 gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Inventory Management</h1>
            <p class="text-sm text-gray-500 mt-1">Auditable history of every stock-in and waste.</p>
        </div>

        <button type="button" id="record-movement-btn"
            class="flex items-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Record Movement
        </button>
    </div>

    {{-- Stat cards. Net Stock is defined concretely as the % change in total
         inventory value (current_stock × unit_cost, summed across every
         ingredient) vs. 7 days ago, reconstructed from this same ledger —
         see InventoryController::ledgerStats() for the full rationale. --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border rounded-xl shadow-sm p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Movements</p>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['totalMovementsToday']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Today</p>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Restocks</p>
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['restocksCompleted']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Completed</p>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Waste Recorded</p>
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['wasteItemCount']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Items</p>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Net Stock</p>
                <svg class="w-4 h-4 {{ $stats['netStockChangePercent'] >= 0 ? 'text-green-600' : 'text-red-500' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    @if ($stats['netStockChangePercent'] >= 0)
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.306-4.306a11.95 11.95 0 015.814 5.518l2.74 1.22m0 0l-5.94 2.28m5.94-2.28l-2.28-5.941" />
                    @endif
                </svg>
            </div>
            <p class="text-2xl font-bold {{ $stats['netStockChangePercent'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $stats['netStockChangePercent'] >= 0 ? '+' : '' }}{{ number_format($stats['netStockChangePercent'], 1) }}%</p>
            <p class="text-xs text-gray-400 mt-0.5">vs. 7 days ago</p>
        </div>
    </div>

    @include('admin.inventory._tabs', ['activeTab' => 'ledger'])

    <form method="GET" action="{{ route('admin.inventory.ledger') }}" class="flex items-center justify-between gap-2 mb-4 flex-wrap">
        <div class="flex items-center gap-2 flex-wrap">
            @include('admin.partials._filter_select', [
                'name' => 'ingredient_id',
                'label' => 'Ingredient',
                'value' => $selectedIngredientId,
                'options' => $ingredients->mapWithKeys(fn ($i) => [$i->ingredient_id => $i->ingredient_name]),
            ])

            {{-- Movement-type pills. Plain navigation links (not AJAX) —
                 consistent with this page's existing architecture, where
                 Record/Edit Movement already uses a full reload / direct DOM
                 patch rather than the fragment-swap pattern Menu/Inventory's
                 Current Stock tab use. --}}
            <div class="flex items-center gap-1.5">
                <a href="{{ request()->fullUrlWithQuery(['type' => null]) }}"
                    class="px-3 py-1.5 rounded-full text-xs font-medium border {{ !$selectedType ? 'bg-gray-800 text-white border-gray-800' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                    All
                </a>
                @foreach (\App\Http\Controllers\Admin\InventoryController::MOVEMENT_TYPES as $typeKey => $typeLabel)
                    <a href="{{ request()->fullUrlWithQuery(['type' => $typeKey]) }}"
                        class="px-3 py-1.5 rounded-full text-xs font-medium border {{ $selectedType === $typeKey ? 'bg-gray-800 text-white border-gray-800' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                        {{ $typeLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        @include('admin.partials._filter_search', [
            'name' => 'search',
            'placeholder' => 'Search ingredient or recorded by...',
            'value' => request('search'),
        ])
    </form>

    <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="text-gray-500 text-xs border-b bg-gray-50">
                <tr>
                    <th class="px-4 py-3 font-medium">Date &amp; Time</th>
                    <th class="px-4 py-3 font-medium">Ingredient</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Qty Changed</th>
                    <th class="px-4 py-3 font-medium">Recorded By</th>
                    <th class="px-4 py-3 font-medium">Notes</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="ledger-table-body" class="divide-y">
                @forelse ($movements as $movement)
                    @php
                        $typeMeta = $typeBadgeMeta[$movement->movement_type] ?? ['label' => ucfirst(str_replace('_', ' ', $movement->movement_type)), 'badge' => 'bg-gray-100 text-gray-700 border border-gray-200'];
                        $recordedByName = $movement->recordedBy->name ?? '—';
                        $avatarColor = $avatarPalette[crc32($recordedByName) % count($avatarPalette)];
                    @endphp
                    <tr id="movement-row-{{ $movement->movement_id }}">
                        <td class="px-4 py-3 text-gray-600 cell-date">{{ $movement->recorded_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800 cell-ingredient">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                    @if ($movement->ingredient?->image_url)
                                        <img src="{{ $movement->ingredient->image_url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 10.5h.008v.008H18V10.5zm-12 9h12a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018 4.5H6a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 006 19.5z" /></svg>
                                    @endif
                                </div>
                                <a href="{{ route('admin.inventory.history', ['ingredient' => $movement->ingredient_id]) }}" class="hover:underline">
                                    {{ $movement->ingredient->ingredient_name ?? '—' }}
                                </a>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="cell-type-badge px-2.5 py-1 rounded-full text-xs font-medium {{ $typeMeta['badge'] }}">
                                {{ $typeMeta['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 cell-qty {{ $movement->qty_changed < 0 ? 'text-red-600' : 'text-green-700' }}">
                            {{ $movement->qty_changed > 0 ? '+' : '' }}{{ number_format($movement->qty_changed, 2) }}
                        </td>
                        <td class="px-4 py-3 cell-recorded-by">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full {{ $avatarColor }} text-white flex items-center justify-center text-[10px] font-semibold flex-shrink-0">
                                    {{ strtoupper(substr($recordedByName, 0, 1)) }}
                                </div>
                                <span class="text-gray-600 truncate">{{ $recordedByName }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 cell-notes truncate max-w-[12rem]">{{ $movement->waste_reason ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button"
                                class="edit-movement-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                                title="Edit"
                                data-id="{{ $movement->movement_id }}"
                                data-ingredient-id="{{ $movement->ingredient_id }}"
                                data-ingredient-name="{{ $movement->ingredient->ingredient_name ?? '' }}"
                                data-ingredient-unit="{{ $movement->ingredient->unit_of_measurement ?? '' }}"
                                data-type="{{ $movement->movement_type }}"
                                data-qty="{{ abs($movement->qty_changed) }}"
                                data-notes="{{ $movement->waste_reason }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                            </button>
                            <button type="button"
                                class="delete-movement-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50"
                                title="Delete"
                                data-id="{{ $movement->movement_id }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr id="ledger-empty-row">
                        <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                            @if (request('search') || $selectedIngredientId || $selectedType)
                                No stock movements match your search or filters.
                            @else
                                No stock movements recorded yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="flex items-center justify-between px-4 py-3 border-t">
            <p id="ledger-pagination-summary" class="text-sm text-gray-500">
                Showing {{ $movements->firstItem() ?? 0 }} to {{ $movements->lastItem() ?? 0 }} of {{ $movements->total() }} entries
            </p>
            <div class="flex gap-2">
                <a href="{{ $movements->onFirstPage() ? '#' : $movements->previousPageUrl() }}"
                    class="p-2 rounded-lg border {{ $movements->onFirstPage() ? 'text-gray-300 border-gray-200 pointer-events-none' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                </a>
                <a href="{{ $movements->hasMorePages() ? $movements->nextPageUrl() : '#' }}"
                    class="p-2 rounded-lg border {{ $movements->hasMorePages() ? 'text-gray-600 border-gray-300 hover:bg-gray-50' : 'text-gray-300 border-gray-200 pointer-events-none' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </a>
            </div>
        </div>
    </div>

    {{-- Record / Edit Movement popup — unchanged from before, including all
         of its JS below. Ingredient selection still uses a plain native
         <select> here deliberately (not the _filter_select popover used for
         the page's own filter row) — this one drives which ingredient a NEW
         movement gets recorded against inside a modal, a different job from
         filtering the table, and swapping it would be change for its own
         sake rather than fixing anything. --}}
    <div id="movement-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md my-8 max-h-[90vh] overflow-y-auto">
            <div class="bg-indigo-50 rounded-t-xl px-6 py-4 flex items-center justify-between sticky top-0">
                <h2 id="movement-modal-title" class="text-lg font-semibold text-gray-800">Record Stock Movement</h2>
                <button type="button" id="movement-modal-close" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>

            <div class="p-6">
                <div id="movement-modal-errors" class="hidden mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm"></div>

                <form id="movement-modal-form">
                    <div class="mb-4" id="movement-ingredient-select-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient</label>
                        <select name="ingredient_select" id="movement-ingredient-select" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="" disabled selected>Select an ingredient</option>
                            @foreach ($ingredients as $ingredient)
                                <option value="{{ $ingredient->ingredient_id }}">{{ $ingredient->ingredient_name }} ({{ $ingredient->unit_of_measurement }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4 hidden" id="movement-ingredient-display-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient</label>
                        <p id="movement-ingredient-display-text" class="text-sm text-gray-800 bg-gray-50 border rounded-lg px-3 py-2"></p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select name="movement_type" id="movement-type-select" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="delivery">Delivery (Stock-In)</option>
                            <option value="spoilage">Spoilage (Waste)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1" id="movement-qty-label">Amount Received</label>
                        <input type="number" step="0.01" min="0.01" name="qty_changed" id="movement-qty-input" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                        <p class="text-xs text-gray-400 mt-1" id="movement-qty-hint">This amount will be added to stock.</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes (optional)</label>
                        <input type="text" name="waste_reason" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" id="movement-modal-cancel" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</button>
                        <button type="submit" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600">Save Movement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('movement-modal');
            const form = document.getElementById('movement-modal-form');
            const title = document.getElementById('movement-modal-title');
            const errorBox = document.getElementById('movement-modal-errors');
            const ingredientSelectField = document.getElementById('movement-ingredient-select-field');
            const ingredientSelect = document.getElementById('movement-ingredient-select');
            const ingredientDisplayField = document.getElementById('movement-ingredient-display-field');
            const ingredientDisplayText = document.getElementById('movement-ingredient-display-text');
            const typeSelect = document.getElementById('movement-type-select');
            const qtyInput = document.getElementById('movement-qty-input');
            const qtyLabel = document.getElementById('movement-qty-label');
            const qtyHint = document.getElementById('movement-qty-hint');
            const baseMovementUrl = '{{ url('admin/inventory/movement') }}';
            const recordUrlBase = '{{ url('admin/inventory') }}';

            // Adjusts the quantity field's label, hint, and allowed sign based on the
            // selected type — this is what stops an admin from having to remember
            // "spoilage needs a minus sign." Delivery/Spoilage always take a plain
            // positive magnitude; the server decides the actual direction from the type.
            function syncQtyFieldToType() {
                const type = typeSelect.value;
                if (type === 'delivery') {
                    qtyLabel.textContent = 'Amount Received';
                    qtyHint.textContent = 'This amount will be added to stock.';
                    qtyInput.min = '0.01';
                } else if (type === 'spoilage') {
                    qtyLabel.textContent = 'Amount Wasted';
                    qtyHint.textContent = 'This amount will be deducted from stock.';
                    qtyInput.min = '0.01';
                } else {
                    qtyLabel.textContent = 'Quantity Adjustment';
                    qtyHint.textContent = 'Use a positive number to add, negative to subtract.';
                    qtyInput.removeAttribute('min');
                }
            }

            typeSelect.addEventListener('change', syncQtyFieldToType);

            function openCreateModal() {
                form.reset();
                errorBox.classList.add('hidden');
                errorBox.innerHTML = '';
                title.textContent = 'Record Stock Movement';
                form.dataset.mode = 'create';
                form.dataset.movementId = '';
                ingredientSelectField.classList.remove('hidden');
                ingredientDisplayField.classList.add('hidden');
                ingredientSelect.required = true;
                typeSelect.value = 'delivery';
                syncQtyFieldToType();
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function openEditModal(data) {
                form.reset();
                errorBox.classList.add('hidden');
                errorBox.innerHTML = '';
                title.textContent = 'Edit Stock Movement';
                form.dataset.mode = 'edit';
                form.dataset.movementId = data.id;

                ingredientSelectField.classList.add('hidden');
                ingredientSelect.required = false;
                ingredientDisplayField.classList.remove('hidden');
                ingredientDisplayText.textContent = `${data.ingredientName} (${data.ingredientUnit})`;

                typeSelect.value = data.type;
                qtyInput.value = data.qty;
                form.waste_reason.value = data.notes ?? '';
                syncQtyFieldToType();

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            const badgeColors = {
                delivery: 'bg-green-50 text-green-700 border border-green-200',
                sale_deduction: 'bg-blue-50 text-blue-700 border border-blue-200',
                spoilage: 'bg-red-50 text-red-700 border border-red-200',
                manual_adjustment: 'bg-amber-50 text-amber-700 border border-amber-200',
            };

            const typeLabels = {
                delivery: 'Delivery',
                sale_deduction: 'Sale',
                spoilage: 'Waste',
                manual_adjustment: 'Manual Adj',
            };

            function typeLabel(type) {
                return typeLabels[type] ?? (type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' '));
            }

            function applyMovementToRow(row, movement) {
                const qty = parseFloat(movement.qty_changed);
                const badge = row.querySelector('.cell-type-badge');
                badge.className = `cell-type-badge px-2.5 py-1 rounded-full text-xs font-medium ${badgeColors[movement.movement_type] ?? 'bg-gray-100 text-gray-700 border border-gray-200'}`;
                badge.textContent = typeLabel(movement.movement_type);

                const qtyCell = row.querySelector('.cell-qty');
                qtyCell.className = `px-4 py-3 cell-qty ${qty < 0 ? 'text-red-600' : 'text-green-700'}`;
                qtyCell.textContent = `${qty > 0 ? '+' : ''}${qty.toFixed(2)}`;

                row.querySelector('.cell-notes').textContent = movement.waste_reason ?? '—';

                const editBtn = row.querySelector('.edit-movement-btn');
                editBtn.dataset.type = movement.movement_type;
                editBtn.dataset.qty = Math.abs(qty);
                editBtn.dataset.notes = movement.waste_reason ?? '';
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const mode = form.dataset.mode;

                if (mode === 'create') {
                    const ingredientId = ingredientSelect.value;
                    if (!ingredientId) return;

                    await submitAjaxForm({
                        form,
                        url: `${recordUrlBase}/${ingredientId}/movement`,
                        method: 'POST',
                        errorBox,
                        onSuccess: () => {
                            closeModal();
                            // Full reload here (as before): this page spans every
                            // ingredient and can be filtered, so a fresh load is the
                            // simplest way to get the new row (and refreshed stat
                            // cards) into correct position without duplicating the
                            // server's sort/filter/aggregation logic client-side.
                            window.location.reload();
                        },
                    });
                } else {
                    const movementId = form.dataset.movementId;

                    await submitAjaxForm({
                        form,
                        url: `${baseMovementUrl}/${movementId}`,
                        method: 'PUT',
                        errorBox,
                        toastKind: 'edit',
                        onSuccess: (data) => {
                            const row = document.getElementById(`movement-row-${movementId}`);
                            if (row) applyMovementToRow(row, data.movement);
                            closeModal();
                        },
                    });
                }
            });

            function deleteMovement(btn) {
                submitAjaxAction({
                    url: `${baseMovementUrl}/${btn.dataset.id}`,
                    method: 'DELETE',
                    confirmMessage: "Delete this stock movement entry? This will change the ingredient's current stock and cannot be undone.",
                    toastKind: 'delete',
                    onSuccess: () => {
                        document.getElementById(`movement-row-${btn.dataset.id}`)?.remove();
                        const tbody = document.getElementById('ledger-table-body');
                        if (tbody.children.length === 0) {
                            const tr = document.createElement('tr');
                            tr.id = 'ledger-empty-row';
                            tr.innerHTML = '<td colspan="7" class="px-4 py-6 text-center text-gray-400">No stock movements recorded yet.</td>';
                            tbody.appendChild(tr);
                        }
                    },
                });
            }

            document.querySelectorAll('.edit-movement-btn').forEach((btn) => {
                btn.addEventListener('click', () => openEditModal({
                    id: btn.dataset.id,
                    ingredientName: btn.dataset.ingredientName,
                    ingredientUnit: btn.dataset.ingredientUnit,
                    type: btn.dataset.type,
                    qty: btn.dataset.qty,
                    notes: btn.dataset.notes,
                }));
            });
            document.querySelectorAll('.delete-movement-btn').forEach((btn) => {
                btn.addEventListener('click', () => deleteMovement(btn));
            });

            document.getElementById('record-movement-btn').addEventListener('click', openCreateModal);
            document.getElementById('movement-modal-close').addEventListener('click', closeModal);
            document.getElementById('movement-modal-cancel').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
        })();
    </script>

@endsection