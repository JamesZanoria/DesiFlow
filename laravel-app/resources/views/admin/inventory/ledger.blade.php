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

        // Deterministic color per recorded-by name
        $avatarPalette = ['bg-amber-500', 'bg-blue-500', 'bg-purple-500', 'bg-emerald-500', 'bg-rose-500', 'bg-indigo-500'];
    @endphp

    <div class="flex items-start justify-between mb-4 gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Inventory Management</h1>
            <p class="text-sm text-gray-500 mt-1">Auditable history of every stock-in and waste.</p>
        </div>

        <a href="{{ route('admin.inventory.movement.create') }}"
            class="flex items-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 whitespace-nowrap transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Record Movement
        </a>
    </div>

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

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="relative">
            <div id="ledger-scroll-hint-left" class="hidden md:block pointer-events-none absolute left-[400px] top-0 bottom-0 w-6 z-30 bg-gradient-to-r from-black/10 to-transparent transition-opacity"></div>
            <div id="ledger-scroll-hint-right" class="hidden pointer-events-none absolute right-0 top-0 bottom-0 w-8 z-30 bg-gradient-to-l from-black/10 to-transparent transition-opacity"></div>

            <div id="ledger-scroll-container" class="overflow-x-auto [&::-webkit-scrollbar]:h-2 [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 [&::-webkit-scrollbar-thumb]:rounded-full">
                <table class="text-sm text-left border-collapse" style="width: 1570px; table-layout: fixed;">
                    <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider font-semibold border-b border-gray-200">
                        <tr class="h-10">
                            <th class="px-6 py-4 font-semibold sticky left-0 z-10 bg-gray-50 whitespace-nowrap" style="width:200px;">Date &amp; Time</th>
                            <th class="px-6 py-4 font-semibold sticky z-10 bg-gray-50 shadow-[4px_0_6px_-4px_rgba(0,0,0,0.15)] whitespace-nowrap" style="left:200px; width:220px;">Ingredient</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap" style="width:200px;">Type</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap" style="width:120px;">Qty Changed</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap" style="width:120px;">Delivery Date</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap" style="width:140px;">Expiration Date</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap" style="width:150px;">Recorded By</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap" style="width:200px;">Notes</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap" style="width:110px;">Reference #</th>
                            <th class="px-6 py-4 font-semibold text-right whitespace-nowrap" style="width:110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ledger-table-body" class="divide-y divide-gray-100">
                        @forelse ($movements as $movement)
                            @php
                                $typeMeta = $typeBadgeMeta[$movement->movement_type] ?? ['label' => ucfirst(str_replace('_', ' ', $movement->movement_type)), 'badge' => 'bg-gray-100 text-gray-700 border border-gray-200'];
                                $recordedByName = $movement->recordedBy->name ?? ',';
                                $avatarColor = $avatarPalette[crc32($recordedByName) % count($avatarPalette)];
                            @endphp
                            <tr id="movement-row-{{ $movement->movement_id }}" class="hover:bg-gray-50 transition-colors duration-150 group">
                                <td class="px-6 py-4 text-gray-500 cell-date sticky left-0 z-10 bg-white truncate" style="width:200px;">{{ $movement->recorded_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-900 cell-ingredient sticky z-10 bg-white shadow-[4px_0_6px_-4px_rgba(0,0,0,0.15)] truncate" style="left:200px; width:220px;">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center shadow-sm">
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
                                <td class="px-6 py-4" style="width:200px;">
                                    <div class="flex items-center gap-1.5 overflow-hidden">
                                        <span class="cell-type-badge px-2.5 py-1 rounded-full text-xs font-medium truncate {{ $movement->voided_at ? 'bg-gray-100 text-gray-500 border border-gray-200' : $typeMeta['badge'] }}">
                                            {{ $typeMeta['label'] }}
                                        </span>
                                        @if ($movement->voided_at)
                                            <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-200 text-gray-600 border border-gray-300 uppercase tracking-tighter">Voided</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 cell-qty {{ $movement->qty_changed < 0 ? 'text-red-600 font-semibold' : 'text-green-700 font-semibold' }} truncate" style="width:120px;">
                                    {{ $movement->qty_changed > 0 ? '+' : '' }}{{ number_format($movement->qty_changed, 2) }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 whitespace-nowrap" style="width:120px;">
                                    {{ $movement->delivery_date ? $movement->delivery_date->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 truncate" style="width:140px;">
                                    {{ $movement->expiration_date ? $movement->expiration_date->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-6 py-4 cell-recorded-by truncate" style="width:150px;">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full {{ $avatarColor }} text-white flex items-center justify-center text-[10px] font-semibold flex-shrink-0 shadow-sm">
                                            {{ strtoupper(substr($recordedByName, 0, 1)) }}
                                        </div>
                                        <span class="text-gray-600 truncate">{{ $recordedByName }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-500 cell-notes truncate" style="width:200px;">
                                    @if ($movement->notes || $movement->edit_reason)
                                        <button type="button" class="view-notes-btn text-left hover:underline hover:text-gray-700 block truncate max-w-[18rem]"
                                            title="{{ $movement->notes ?? 'View edit reason' }}"
                                            data-notes="{{ $movement->notes }}"
                                            data-edit-reason="{{ $movement->edit_reason }}">
                                            {{ $movement->notes ?? 'View edit reason' }}
                                        </button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500 cell-reference truncate" title="{{ $movement->reference_number ?? '' }}" style="width:110px;">{{ $movement->reference_number ?? '—' }}</td>
                                <td class="px-6 py-4 text-right truncate" style="width:110px;">
                                    @if ($movement->movement_type !== 'void' && ! $movement->voided_at)
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('admin.inventory.movement.edit', $movement->movement_id) }}"
                                                class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                            </a>
                                            <button type="button"
                                                class="void-movement-btn p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                                title="Void"
                                                data-id="{{ $movement->movement_id }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-300">Locked</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="ledger-empty-row">
                                <td colspan="10" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V//L12 11l-7-7" /></svg>
                                        <p class="text-sm">{{ (request('search') || $selectedIngredientId || $selectedType) ? 'No stock movements match your search or filters.' : 'No stock movements recorded yet.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50/30">
            <p id="ledger-pagination-summary" class="text-xs text-gray-500 font-medium uppercase tracking-wider">
                Showing {{ $movements->firstItem() ?? 0 }} to {{ $movements->lastItem() ?? 0 }} of {{ $movements->total() }} entries
            </p>
            <div class="flex gap-2">
                <a href="{{ $movements->onFirstPage() ? '#' : $movements->previousPageUrl() }}"
                    class="p-2 rounded-lg border border-gray-200 bg-white transition-all {{ $movements->onFirstPage() ? 'text-gray-300 border-gray-100 pointer-events-none' : 'text-gray-600 hover:bg-gray-50 hover:border-gray-300' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                </a>
                <a href="{{ $movements->hasMorePages() ? $movements->nextPageUrl() : '#' }}"
                    class="p-2 rounded-lg border border-gray-200 bg-white transition-all {{ $movements->hasMorePages() ? 'text-gray-600 hover:bg-gray-50 hover:border-gray-300' : 'text-gray-300 border-gray-100 pointer-events-none' }}">
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

    <script>
        (function () {
            const container = document.getElementById('ledger-scroll-container');
            const leftHint = document.getElementById('ledger-scroll-hint-left');
            const rightHint = document.getElementById('ledger-scroll-hint-right');

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

            function voidMovement(btn) {
                submitAjaxAction({
                    url: `{{ url('admin/inventory/movement') }}/${btn.dataset.id}`,
                    method: 'DELETE',
                    confirmMessage: {
                        title: 'Void Stock Movement?',
                        message: 'Are you sure you want to void this stock movement? The original record will be retained as voided.',
                        actionLabel: 'Void',
                        type: 'warning'
                    },
                    toastKind: 'delete',
                    onSuccess: () => {
                        window.location.reload();
                    },
                });
            }

            document.querySelectorAll('.void-movement-btn').forEach((btn) => {
                btn.addEventListener('click', () => voidMovement(btn));
            });

            const notesModal = document.getElementById('notes-modal');
            const notesModalNotes = document.getElementById('notes-modal-notes');
            const notesModalEditReasonWrap = document.getElementById('notes-modal-edit-reason-wrap');
            const notesModalEditReason = document.getElementById('notes-modal-edit-reason');

            document.querySelectorAll('.view-notes-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
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
            notesModal.addEventListener('click', (e) => { if (e.target === notesModal) { notesModal.classList.add('hidden'); } });
        })();
    </script>

@endsection
