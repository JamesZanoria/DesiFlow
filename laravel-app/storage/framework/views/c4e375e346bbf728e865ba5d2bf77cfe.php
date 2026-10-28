<?php $__env->startSection('title', 'Inventory Management'); ?>

<?php $__env->startSection('content'); ?>

    <div class="flex items-start justify-between mb-4 gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Inventory Management</h1>
            <p class="text-sm text-gray-500 mt-1">Track stock levels, monitor thresholds, and manage ingredients.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="inventory-export-btn"
                class="flex items-center gap-1.5 bg-white border px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Export
            </button>
            <a href="<?php echo e(route('admin.inventory.create')); ?>"
                class="flex items-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Ingredient
            </a>
        </div>
    </div>

    <div id="inventory-stats-container">
        <?php echo $__env->make('admin.inventory._current_stock_stats', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    <?php echo $__env->make('admin.inventory._tabs', ['activeTab' => 'stock'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <form method="GET" action="<?php echo e(route('admin.inventory.index')); ?>" class="flex items-center justify-between gap-2 mb-4 flex-wrap">
        <div class="flex items-center gap-2">
            <?php echo $__env->make('admin.partials._filter_select', [
                'name' => 'status',
                'label' => 'Status',
                'value' => request('status'),
                'options' => [
                    'active' => 'Active',
                    'low_stock' => 'Low Stock',
                    'out_of_stock' => 'Out of Stock',
                    'expiring_soon' => 'Expiring Soon',
                    'expired' => 'Expired',
                ],
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php echo $__env->make('admin.partials._filter_select', [
                'name' => 'sort',
                'label' => 'Sort',
                'value' => request('sort'),
                'options' => ['critical' => 'Critical First'],
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>

        <?php echo $__env->make('admin.partials._filter_search', [
            'name' => 'search',
            'placeholder' => 'Search items...',
            'value' => request('search'),
            'suggestions' => $allIngredientNames,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>

    <div id="inventory-table-container">
        <?php echo $__env->make('admin.inventory._table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    
    <div id="waste-popover" class="hidden fixed z-[60] bg-white border shadow-xl rounded-lg p-4 w-72 max-w-[calc(100vw-32px)] max-h-[80vh] overflow-y-auto pointer-events-auto">
        <div class="flex items-center justify-between mb-3 pb-2 border-b sticky top-0 bg-white z-10">
            <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wide">Waste Details</h4>
            <button type="button" id="waste-popover-close" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div id="waste-popover-content" class="space-y-3">
            
        </div>
        <div class="mt-4 pt-2 border-t sticky bottom-0 bg-white">
            <p class="text-[10px] text-gray-400 italic">Quantities based on Naive FIFO stock movement.</p>
        </div>
    </div>

    <script>
        (function () {
            const storeUrl = '<?php echo e(route('admin.inventory.store')); ?>';
            const baseUrl = '<?php echo e(url('admin/inventory')); ?>';
            const indexUrl = '<?php echo e(route('admin.inventory.index')); ?>';
            const todayDateStr = '<?php echo e(now()->toDateString()); ?>';

            function numberFormat(value) {
                return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            async function refreshTable(url) {
                const targetUrl = url || window.location.href;
                try {
                    const response = await fetch(targetUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const data = await response.json();
                    const container = document.getElementById('inventory-table-container');
                    if (container) container.innerHTML = data.html;

                    if (data.statsHtml) {
                        const statsContainer = document.getElementById('inventory-stats-container');
                        if (statsContainer) statsContainer.innerHTML = data.statsHtml;
                    }
                    if (data.categoryOptionsHtml) {
                        const categoryPopover = document.querySelector('[data-filter-select][data-name="category"] .filter-select-popover');
                        if (categoryPopover) categoryPopover.innerHTML = data.categoryOptionsHtml;
                    }
                    if (data.ingredientSuggestionsHtml) {
                        const suggestList = document.querySelector('[data-search-suggest] .search-suggest-list');
                        if (suggestList) suggestList.innerHTML = data.ingredientSuggestionsHtml;
                    }

                    history.replaceState(null, '', targetUrl);
                } catch (err) {
                    console.error('Failed to refresh inventory table', err);
                    showToast('Could not refresh the table. Please reload the page.', 'error');
                }
            }

            (function() {
                const container = document.getElementById('inventory-table-container');
                if (!container) return;

                container.addEventListener('click', (e) => {
                    const paginationLink = e.target.closest('.pagination-link');
                    if (paginationLink) {
                        if (paginationLink.classList.contains('pointer-events-none')) return;
                        e.preventDefault();
                        refreshTable(paginationLink.href);
                        return;
                    }

                    const wasteBtn = e.target.closest('.mark-waste-btn');
                    if (wasteBtn) {
                        markAsWaste(wasteBtn);
                        return;
                    }

                    const editBtn = e.target.closest('.edit-ingredient-btn');
                    if (editBtn) {
                        // Now handled by window.location.href in _table.blade.php
                        return;
                    }

                    const deleteBtn = e.target.closest('.delete-ingredient-btn');
                    if (deleteBtn) {
                        deleteIngredient(deleteBtn);
                        return;
                    }
                });
            })();

            const AUTO_REFRESH_INTERVAL_MS = 5000;
            let refreshInFlight = false;

            setInterval(() => {
                if (refreshInFlight) return;
                if (document.hidden) return;

                refreshInFlight = true;
                refreshTable().finally(() => {
                    refreshInFlight = false;
                });
            }, AUTO_REFRESH_INTERVAL_MS);

            function markAsWaste(btn) {
                submitAjaxAction({
                    url: `${baseUrl}/${btn.dataset.id}/waste-expired`,
                    method: 'PUT',
                    confirmMessage: {
                        title: 'Mark Expired Stock as Waste?',
                        message: `Are you sure you want to mark the expired stock of "${btn.dataset.name}" as waste? The system will automatically calculate and remove only the quantity that has passed its shelf life.`,
                        actionLabel: 'Mark as Waste',
                        type: 'warning'
                    },
                    toastKind: 'delete',
                    onSuccess: () => {
                        refreshTable(window.location.href);
                    },
                });
            }

            function deleteIngredient(btn) {
                submitAjaxAction({
                    url: `${baseUrl}/${btn.dataset.id}`,
                    method: 'DELETE',
                    confirmMessage: {
                        title: 'Delete Ingredient?',
                        message: `Are you sure you want to delete "${btn.dataset.name}"? This action cannot be undone.`,
                        actionLabel: 'Delete',
                        type: 'danger'
                    },
                    toastKind: 'delete',
                    onSuccess: () => refreshTable(window.location.href),
                });
            }

            window.showWasteDetails = function(event, data) {
                event.stopPropagation();
                const popover = document.getElementById('waste-popover');
                const content = document.getElementById('waste-popover-content');

                content.innerHTML = `
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">Ingredient:</span>
                            <span class="text-xs font-semibold text-gray-800">${data.name}</span>
                        </div>
                        <div class="p-3 bg-rose-50 rounded-lg border border-rose-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] font-medium text-rose-600">Already Expired:</span>
                                <span class="text-xs font-bold text-rose-700">${data.expired} ${data.unit}</span>
                            </div>
                        </div>
                        <div class="p-3 bg-orange-50 rounded-lg border border-orange-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] font-medium text-orange-600">Expiring Soon:</span>
                                <span class="text-xs font-bold text-orange-700">${data.expiring} ${data.unit}</span>
                            </div>
                        </div>
                    </div>
                `;

                popover.classList.remove('hidden');

                const trigger = event.target.closest('button');
                const triggerRect = trigger.getBoundingClientRect();
                const popoverRect = popover.getBoundingClientRect();
                const popoverHeight = popoverRect.height;
                const popoverWidth = popoverRect.width;

                let top = triggerRect.bottom + 8;
                if (top + popoverHeight > window.innerHeight) {
                    top = triggerRect.top - popoverHeight - 8;
                }
                popover.style.top = `${Math.max(8, top)}px`;

                let left = triggerRect.left;
                if (left + popoverWidth > window.innerWidth) {
                    left = window.innerWidth - popoverWidth - 16;
                }
                if (left < 16) {
                    left = 16;
                }
                popover.style.left = `${left}px`;
            };

            document.getElementById('waste-popover-close')?.addEventListener('click', () => {
                document.getElementById('waste-popover').classList.add('hidden');
            });

            document.addEventListener('click', (e) => {
                const popover = document.getElementById('waste-popover');
                if (popover && !popover.contains(e.target) && !e.target.closest('[onclick="showWasteDetails(...)"]')) {
                    popover.classList.add('hidden');
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.getElementById('waste-popover')?.classList.add('hidden');
                }
            });

            function sanitizeCsvCell(text) {
                return /^[=+\-@\t\r]/.test(text) ? `'${text}` : text;
            }

            document.getElementById('inventory-export-btn').addEventListener('click', () => {
                const currentParams = window.location.search;
                window.location.href = `<?php echo e(route('admin.inventory.export')); ?>${currentParams}`;
            });
        })();
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/inventory/index.blade.php ENDPATH**/ ?>