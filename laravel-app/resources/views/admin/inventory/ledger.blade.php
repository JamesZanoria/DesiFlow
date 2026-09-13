@extends('layouts.app')

@section('title', 'Stock Movement Ledger')

@section('content')

    @php
        $typeBadgeMeta = [
            'delivery' => ['label' => 'Supplier Delivery', 'badge' => 'bg-green-50 text-green-700 border border-green-200'],
            'opening_balance' => ['label' => 'Opening Balance', 'badge' => 'bg-teal-50 text-teal-700 border border-teal-200'],
            'sale_deduction' => ['label' => 'Sale', 'badge' => 'bg-blue-50 text-blue-700 border border-blue-200'],
            'manual_adjustment' => ['label' => 'Physical Count', 'badge' => 'bg-amber-50 text-amber-700 border border-amber-200'],
            'spoilage' => ['label' => 'Waste / Damage', 'badge' => 'bg-red-50 text-red-700 border border-red-200'],
            'internal_use' => ['label' => 'Internal Use', 'badge' => 'bg-purple-50 text-purple-700 border border-purple-200'],
            'void' => ['label' => 'Voided Entry', 'badge' => 'bg-gray-100 text-gray-600 border border-gray-300'],
        ];

        // Deterministic color per recorded-by name (not tied to role, this
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
         ingredient) vs. 7 days ago, reconstructed from this same ledger,
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

            @include('admin.partials._filter_select', [
                'name' => 'type',
                'label' => 'Type',
                'value' => $selectedType,
                'options' => \App\Http\Controllers\Admin\InventoryController::MOVEMENT_TYPES,
            ])

            
        </div>

        @include('admin.partials._filter_search', [
            'name' => 'search',
            'placeholder' => 'Search recorded by...',
            'value' => request('search'),
            'suggestions' => $searchSuggestions,
        ])
    </form>

    <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
        <div class="relative">
            <div id="ledger-scroll-hint-left" class="hidden md:block pointer-events-none absolute left-[360px] top-0 bottom-0 w-6 z-30 bg-gradient-to-r from-black/10 to-transparent transition-opacity"></div>
            <div id="ledger-scroll-hint-right" class="hidden pointer-events-none absolute right-0 top-0 bottom-0 w-8 z-30 bg-gradient-to-l from-black/10 to-transparent transition-opacity"></div>

            <div id="ledger-scroll-container" class="overflow-x-auto [&::-webkit-scrollbar]:h-2 [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumn]:bg-gray-300 [&::-webkit-scrollbar-thumb]:rounded-full">
                <table class="text-sm text-left" style="min-width: 1000px;">
                    <thead class="text-gray-500 text-xs border-b bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 font-medium sticky left-0 z-10 bg-gray-50" style="width:200px;">Date &amp; Time</th>
                            <th class="px-4 py-3 font-medium sticky z-10 bg-gray-50 shadow-[4px_0_6px_-4px_rgba(0,0,0,0.15)]" style="left:200px; width:220px;">Ingredient</th>
                            <th class="px-4 py-3 font-medium" style="min-width:200px;">Type</th>
                            <th class="px-4 py-3 font-medium" style="min-width:120px;">Qty Changed</th>
                            <th class="px-4 py-3 font-medium" style="min-width:120px;">Delivery Date</th>
                            <th class="px-4 py-3 font-medium" style="min-width:140px;">Expiration Date</th>
                            <th class="px-4 py-3 font-medium" style="min-width:150px;">Recorded By</th>
                            <th class="px-4 py-3 font-medium" style="min-width:200px;">Notes</th>
                            <th class="px-4 py-3 font-medium" style="min-width:110px;">Reference #</th>
                            <th class="px-4 py-3 font-medium text-right" style="min-width:110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ledger-table-body" class="divide-y">
                        @forelse ($movements as $movement)
                            @php
                                $typeMeta = $typeBadgeMeta[$movement->movement_type] ?? ['label' => ucfirst(str_replace('_', ' ', $movement->movement_type)), 'badge' => 'bg-gray-100 text-gray-700 border border-gray-200'];
                                $recordedByName = $movement->recordedBy->name ?? ',';
                                $avatarColor = $avatarPalette[crc32($recordedByName) % count($avatarPalette)];
                            @endphp
                            <tr id="movement-row-{{ $movement->movement_id }}">
                                <td class="px-4 py-3 text-gray-600 cell-date sticky left-0 z-10 bg-white" style="min-width:200px;">{{ $movement->recorded_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800 cell-ingredient sticky z-10 bg-white shadow-[4px_0_6px_-4px_rgba(0,0,0,0.15)]" style="left:200px; width:220px;">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                            @if ($movement->ingredient?->image_url)
                                                <img src="{{ $movement->ingredient->image_url }}" alt="" class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 10.5h.008v.008H18V10.5zm-12 9h12a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018 4.5H6a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 006 19.5z" /></svg>
                                            @endif
                                        </div>
                                        <span class="truncate min-w-0" title="{{ $movement->ingredient->ingredient_name ?? '' }}">
                                            {{ $movement->ingredient->ingredient_name ?? ',' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="cell-type-badge px-2.5 py-1 rounded-full text-xs font-medium {{ $typeMeta['badge'] }}">
                                        {{ $typeMeta['label'] }}
                                    </span>
                                    @if ($movement->voided_at)
                                        <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500 border border-gray-300">Voided</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 cell-qty {{ $movement->qty_changed < 0 ? 'text-red-600' : 'text-green-700' }}">
                                    {{ $movement->qty_changed > 0 ? '+' : '' }}{{ number_format($movement->qty_changed, 2) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $movement->delivery_date ? $movement->delivery_date->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $movement->expiration_date ? $movement->expiration_date->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 cell-recorded-by">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full {{ $avatarColor }} text-white flex items-center justify-center text-[10px] font-semibold flex-shrink-0">
                                            {{ strtoupper(substr($recordedByName, 0, 1)) }}
                                        </div>
                                        <span class="text-gray-600 truncate">{{ $recordedByName }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-500 cell-notes">
                                    @if ($movement->notes || $movement->edit_reason)
                                        <button type="button" class="view-notes-btn text-left hover:underline hover:text-gray-700 block truncate max-w-[12rem]"
                                            data-notes="{{ $movement->notes }}"
                                            data-edit-reason="{{ $movement->edit_reason }}">
                                            {{ \Illuminate\Support\Str::limit($movement->notes ?? 'View edit reason', 30) }}
                                        </button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 cell-reference">{{ $movement->reference_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($movement->movement_type !== 'void' && ! $movement->voided_at)
                                        <button type="button"
                                            class="edit-movement-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                                            title="Edit"
                                            data-id="{{ $movement->movement_id }}"
                                            data-ingredient-id="{{ $movement->ingredient_id }}"
                                            data-ingredient-name="{{ $movement->ingredient->ingredient_name ?? '' }}"
                                            data-ingredient-unit="{{ $movement->ingredient->unit_of_measurement ?? '' }}"
                                            data-type="{{ $movement->movement_type }}"
                                            data-type-label="{{ $typeMeta['label'] }}"
                                            data-qty="{{ $movement->movement_type === 'manual_adjustment' ? $movement->qty_changed : abs($movement->qty_changed) }}"
                                            data-recorded-at="{{ $movement->recorded_at->timezone('Asia/Manila')->toDateString() }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                        </button>
                                        <button type="button"
                                            class="void-movement-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50"
                                            title="Void"
                                            data-id="{{ $movement->movement_id }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-300">Locked</span>
                                    @endif
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
            </div>
        </div>

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

    <div id="notes-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="relative bg-amber-50 border border-amber-200 rounded-sm shadow-2xl w-full max-w-sm rotate-[-0.5deg]"
            style="box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2), 0 4px 6px -4px rgba(0,0,0,0.15);">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 w-6 h-6 rounded-full bg-red-500 border-2 border-red-600 shadow-md"></div>
            <button type="button" id="notes-modal-close" class="absolute top-3 right-3 text-amber-700/50 hover:text-amber-900">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            <div class="px-6 pt-8 pb-6">
                <p class="text-xs font-semibold text-amber-700/70 uppercase tracking-wide mb-2">Note</p>
                <p id="notes-modal-notes" class="text-sm text-amber-950 whitespace-pre-wrap leading-relaxed font-medium"></p>
                <div id="notes-modal-edit-reason-wrap" class="hidden mt-4 pt-4 border-t border-dashed border-amber-300">
                    <p class="text-xs font-semibold text-amber-700/70 uppercase tracking-wide mb-1">Edit Reason</p>
                    <p id="notes-modal-edit-reason" class="text-sm text-amber-950 whitespace-pre-wrap leading-relaxed"></p>
                </div>
            </div>
        </div>
    </div>


    {{-- Record / Edit Movement popup. Record and Edit now diverge enough
         (Edit locks the ingredient and type, and adds its own audit fields)
         that most of the form is wrapped into #movement-create-only-fields /
         #movement-edit-only-fields, toggled as a group in the script below
         rather than field-by-field. Ingredient selection still uses a plain
         native <select> here deliberately (not the _filter_select popover
         used for the page's own filter row), this one drives which
         ingredient a NEW movement gets recorded against inside a modal, a
         different job from filtering the table, and swapping it would be
         change for its own sake rather than fixing anything. --}}
    <div id="movement-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md my-8 max-h-[90vh] overflow-y-auto">
            <div class="bg-indigo-50 rounded-t-xl px-6 py-4 flex items-center justify-between sticky top-0">
                <h2 id="movement-modal-title" class="text-lg font-semibold text-gray-800">Record Stock Movement</h2>
                <button type="button" id="movement-modal-close" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>

            <div class="p-6">
                <div id="movement-modal-errors" class="hidden mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm"></div>

                <form id="movement-modal-form">
                    {{-- Record mode: pick which ingredient this movement is against. --}}
                    <div class="mb-4" id="movement-ingredient-select-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient</label>
                        <div class="relative" data-combo-select="ingredient_select" data-allow-custom="false" data-placeholder="Select an ingredient">
                            <input type="hidden" name="ingredient_select" id="movement-ingredient-select" value="">
                            <button type="button" class="combo-select-trigger w-full flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm text-left hover:border-gray-400 focus:outline-none">
                                <span class="combo-select-value min-w-0 truncate text-gray-400">Select an ingredient</span>
                                <svg class="combo-select-chevron w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            <div class="combo-select-popover hidden absolute left-0 right-0 mt-2 bg-white border rounded-lg shadow-lg z-30 overflow-hidden">
                                <div class="p-2 border-b">
                                    <input type="text" class="combo-select-search w-full border rounded-md px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-amber-400" placeholder="Search ingredients...">
                                </div>
                                <div class="combo-select-list max-h-48 overflow-y-auto py-1">
                                    @foreach ($ingredients as $ingredient)
                                        <button type="button" class="combo-select-option w-full flex items-center justify-between gap-2 px-3 py-2 text-xs text-left hover:bg-gray-50 text-gray-600"
                                            data-value="{{ $ingredient->ingredient_id }}" data-label="{{ $ingredient->ingredient_name }} ({{ $ingredient->unit_of_measurement }})">
                                            <span class="truncate">{{ $ingredient->ingredient_name }} ({{ $ingredient->unit_of_measurement }})</span>
                                            <svg class="combo-select-check hidden w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        </button>
                                    @endforeach
                                </div>
                                <p class="combo-select-empty hidden px-3 py-4 text-center text-xs text-gray-400">No matching ingredients.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Edit mode: the ingredient and reason code are locked in from
                         the original entry (see InventoryController::updateMovement())
                         and just shown for context, not re-selectable. --}}
                    <div class="mb-4 hidden" id="movement-ingredient-display-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient</label>
                        <p id="movement-ingredient-display-text" class="text-sm text-gray-800 bg-gray-50 border rounded-lg px-3 py-2"></p>
                    </div>

                    <div id="movement-create-only-fields">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                            <div class="relative" data-combo-select="movement_type_select" data-allow-custom="false" data-placeholder="Select a reason">
                                <input type="hidden" name="movement_type" id="movement-type-select" value="opening_balance" required>
                                <button type="button" class="combo-select-trigger w-full flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm text-left hover:border-gray-400 focus:outline-none">
                                    <span class="combo-select-value min-w-0 truncate text-gray-400">Select a reason</span>
                                    <svg class="combo-select-chevron w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                </button>
                                <div class="combo-select-popover hidden absolute left-0 right-0 mt-2 bg-white border rounded-lg shadow-lg z-30 overflow-hidden">
                                    <div class="combo-select-list max-h-48 overflow-y-auto py-1">
                                        @foreach (\App\Http\Controllers\Admin\InventoryController::MOVEMENT_TYPES as $key => $label)
                                            <button type="button" class="combo-select-option w-full flex items-center justify-between gap-2 px-3 py-2 text-xs text-left hover:bg-gray-50 text-gray-600"
                                                data-value="{{ $key }}" data-label="{{ $label }}">
                                                <span class="truncate">{{ $label }}</span>
                                                <svg class="combo-select-check hidden w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            </button>
                                        @endforeach
                                    </div>
                                    <p class="combo-select-empty hidden px-3 py-4 text-center text-xs text-gray-400">No matching reasons.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1" id="movement-qty-label">Amount Received</label>
                        <input type="number" step="0.01" min="0.01" name="qty_changed" id="movement-qty-input" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                        <p class="text-xs text-gray-400 mt-1" id="movement-qty-hint">This amount will be added to stock.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Date</label>
                            <input type="date" name="delivery_date" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expiration Date</label>
                            <input type="date" name="expiration_date" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div id="movement-create-only-fields-2">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number / Invoice (optional)</label>
                            <input type="text" name="reference_number" placeholder="e.g. DR# or invoice number" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks / Notes (optional)</label>
                            <input type="text" name="notes" placeholder="e.g. Supplier delivered dented cans" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>

                    {{-- Edit mode only: correcting an existing entry always requires
                         saying why, and can optionally backdate/forward-date it —
                         see InventoryController::updateMovement(). --}}
                    <div class="hidden" id="movement-edit-only-fields">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Edit</label>
                            <input type="text" name="edit_reason" id="movement-edit-reason-input" placeholder="e.g. Typo, meant to type 5 instead of 50" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Transaction (optional)</label>
                            <input type="date" name="recorded_at" id="movement-recorded-at-input" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Only set this if the original date itself was wrong.</p>
                        </div>
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
            const createOnlyFields = document.getElementById('movement-create-only-fields');
            const createOnlyFields2 = document.getElementById('movement-create-only-fields-2');
            const editOnlyFields = document.getElementById('movement-edit-only-fields');
            const editReasonInput = document.getElementById('movement-edit-reason-input');
            const recordedAtInput = document.getElementById('movement-recorded-at-input');
            const typeSelect = document.getElementById('movement-type-select');
            const qtyInput = document.getElementById('movement-qty-input');
            const qtyLabel = document.getElementById('movement-qty-label');
            const qtyHint = document.getElementById('movement-qty-hint');
            const baseMovementUrl = '{{ url('admin/inventory/movement') }}';
            const recordUrlBase = '{{ url('admin/inventory') }}';
            const container = document.getElementById('ledger-scroll-container');
            const leftHint = document.getElementById('ledger-scroll-hint-left');
            const rightHint = document.getElementById('ledger-scroll-hint-right');
            const saveBtn = form.querySelector('button[type="submit"]');

            const QTY_COPY = {
                delivery: { label: 'Amount Received', hint: 'This amount will be added to stock.' },
                opening_balance: { label: 'Starting Quantity', hint: 'This becomes the ingredient\u2019s starting stock.' },
                spoilage: { label: 'Amount Wasted', hint: 'This amount will be deducted from stock.' },
                internal_use: { label: 'Amount Used', hint: 'This amount will be deducted from stock.' },
                manual_adjustment: { label: 'Quantity Adjustment', hint: 'Use a positive number to add, negative to subtract.' },
            };

            if (!container) return;

            function updateScrollHints() {
                const maxScroll = container.scrollWidth - container.clientWidth;
                const atStart = container.scrollLeft <= 4;
                const atEnd = container.scrollLeft >= maxScroll - 4;
                const isScrollable = maxScroll > 4;

                leftHint.classList.toggle('opacity-0', atStart || !isScrollable);
                rightHint.classList.toggle('hidden', !isScrollable);
                rightHint.classList.toggle('opacity-0', atEnd);
            }

            container.addEventListener('scroll', updateScrollHints);
            window.addEventListener('resize', updateScrollHints);
            updateScrollHints();

            // Adjusts the quantity field's label, hint, and allowed sign based on the
            // reason code, this is what stops an admin from having to remember
            // "waste needs a minus sign." Every reason except Physical Count
            // Adjustment takes a plain positive magnitude; the server decides the
            // actual direction from the reason (see normalizeQtySign()).
            function syncQtyFieldToType(type) {
                const copy = QTY_COPY[type] ?? QTY_COPY.manual_adjustment;
                qtyLabel.textContent = copy.label;
                qtyHint.textContent = copy.hint;
                if (type === 'manual_adjustment') {
                    qtyInput.removeAttribute('min');
                } else {
                    qtyInput.min = '0.01';
                }
            }

            typeSelect.addEventListener('change', () => syncQtyFieldToType(typeSelect.value));

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

                createOnlyFields.classList.remove('hidden');
                createOnlyFields2.classList.remove('hidden');
                editOnlyFields.classList.add('hidden');
                editReasonInput.required = false;

                const typeContainer = document.getElementById('movement-type-select').closest('[data-combo-select]');
                setComboSelectValue(typeContainer, 'opening_balance', 'Opening Balance');
                typeSelect.required = true;
                syncQtyFieldToType('opening_balance');

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function openEditModal(data) {
                initialSnapshot = null;
                form.reset();
                errorBox.classList.add('hidden');
                errorBox.innerHTML = '';
                title.textContent = 'Edit Stock Movement';
                form.dataset.mode = 'edit';
                form.dataset.movementId = data.id;
                form.dataset.movementType = data.type;

                ingredientSelectField.classList.add('hidden');
                ingredientSelect.required = false;
                ingredientDisplayField.classList.remove('hidden');
                ingredientDisplayText.textContent = `${data.ingredientName} (${data.ingredientUnit}) \u2014 ${data.typeLabel}`;

                // Reason code is locked in from the original entry — not part of
                // this form in edit mode (see InventoryController::updateMovement()).
                createOnlyFields.classList.add('hidden');
                createOnlyFields2.classList.add('hidden');
                typeSelect.required = false;

                editOnlyFields.classList.remove('hidden');
                editReasonInput.required = true;
                recordedAtInput.value = data.recordedAt ?? '';

                initialSnapshot = snapshotForm();
                checkDirty();

                qtyInput.value = data.qty;
                syncQtyFieldToType(data.type);

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            let initialSnapshot = null;

            function snapshotForm() {
                return JSON.stringify({ qty: qtyInput.value, editReason: editReasonInput.value, recordedAt: recordedAtInput.value });
            }

            function checkDirty() {
                if (form.dataset.mode !== 'edit' || !initialSnapshot) {
                    saveBtn.disabled = false;
                    saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    return;
                }
                const dirty = snapshotForm() !== initialSnapshot;
                saveBtn.disabled = !dirty;
                saveBtn.classList.toggle('opacity-50', !dirty);
                saveBtn.classList.toggle('cursor-not-allowed', !dirty);
            }

            [qtyInput, editReasonInput, recordedAtInput].forEach((el) => el.addEventListener('input', checkDirty));

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
                        onSuccess: () => {
                            // A corrected quantity/date also shifts the stat cards
                            // (Net Stock especially), so resync from the server
                            // rather than hand-patching just this one row.
                            closeModal();
                            window.location.reload();
                        },
                    });
                }
            });

            // "Delete" never erases a row — it voids it, see
            // InventoryController::destroyMovement(). A full reload picks up
            // both the now-locked original row and the new reversing entry,
            // plus refreshed stat cards, the same reasoning as create() above.
            function voidMovement(btn) {
                submitAjaxAction({
                    url: `${baseMovementUrl}/${btn.dataset.id}`,
                    method: 'DELETE',
                    confirmMessage: "Void this stock movement? This records a reversing entry rather than erasing it, and cannot be undone.",
                    toastKind: 'delete',
                    onSuccess: () => {
                        window.location.reload();
                    },
                });
            }

            document.querySelectorAll('.edit-movement-btn').forEach((btn) => {
                btn.addEventListener('click', () => openEditModal({
                    id: btn.dataset.id,
                    ingredientName: btn.dataset.ingredientName,
                    ingredientUnit: btn.dataset.ingredientUnit,
                    type: btn.dataset.type,
                    typeLabel: btn.dataset.typeLabel,
                    qty: btn.dataset.qty,
                    recordedAt: btn.dataset.recordedAt,
                }));
            });
            document.querySelectorAll('.void-movement-btn').forEach((btn) => {
                btn.addEventListener('click', () => voidMovement(btn));
            });

            const notesModal = document.getElementById('notes-modal');
            const notesModalNotes = document.getElementById('notes-modal-notes');
            const notesModalEditReasonWrap = document.getElementById('notes-modal-edit-reason-wrap');
            const notesModalEditReason = document.getElementById('notes-modal-edit-reason');

            document.querySelectorAll('.view-notes-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    // textContent only — notes/edit reasons are free text typed by staff,
                    // never trust them as HTML.
                    notesModalNotes.textContent = btn.dataset.notes || '(no notes entered)';
                    if (btn.dataset.editReason) {
                        notesModalEditReason.textContent = btn.dataset.editReason;
                        notesModalEditReasonWrap.classList.remove('hidden');
                    } else {
                        notesModalEditReasonWrap.classList.add('hidden');
                    }
                    notesModal.classList.remove('hidden');
                });
            });
            document.getElementById('notes-modal-close').addEventListener('click', () => notesModal.classList.add('hidden'));
            notesModal.addEventListener('click', (e) => { if (e.target === notesModal) notesModal.classList.add('hidden'); });

            document.getElementById('record-movement-btn').addEventListener('click', openCreateModal);
            document.getElementById('movement-modal-close').addEventListener('click', closeModal);
            document.getElementById('movement-modal-cancel').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
        })();
    </script>

@endsection