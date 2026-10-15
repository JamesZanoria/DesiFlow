<?php $__env->startSection('title', 'Menu Management'); ?>

<?php $__env->startSection('content'); ?>
    <div class="overflow-hidden flex flex-col p-2 space-y-2">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-0 gap-4 flex-none">
            <div class="w-full md:w-auto">
                <h1 class="text-2xl font-bold text-gray-800">Menu Management</h1>
                <p class="text-sm text-gray-500 mt-1 mb-3">Manage your restaurant's items, categories, and availability.</p>
            </div>
            <a href="<?php echo e(route('admin.menu.create')); ?>"
                class="w-full md:w-auto flex items-center justify-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 whitespace-nowrap transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add New Item
            </a>
        </div>

        
        <?php
            $successMsg = session('success') ?? session('menu_success');
            if ($successMsg) {
                session()->forget('menu_success');
            }
        ?>

        <?php if($successMsg): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (window.showToast) {
                        window.showToast("<?php echo e($successMsg); ?>", 'success');
                    }
                });
            </script>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-3 animate-in fade-in slide-in-from-top-2 duration-300">
                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?>

    <div id="menu-table-container" class="flex-1 overflow-hidden rounded-xl border border-slate-200">
        <?php echo $__env->make('admin.menu._table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>


    
    <div id="availability-popover" class="hidden fixed z-[60] bg-white border shadow-xl rounded-lg p-4 w-72 max-w-[300px] max-h-[80vh] overflow-y-auto pointer-events-auto">
        <div class="flex items-center justify-between mb-3 pb-2 border-b sticky top-0 bg-white z-10">
            <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wide">Why unavailable?</h4>
            <button type="button" id="availability-popover-close" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div id="availability-popover-content" class="space-y-3">
            
        </div>
        <div class="mt-4 pt-2 border-t sticky bottom-0 bg-white">
            <p class="text-[10px] text-gray-400 italic">Reasons: Expired, out of stock, or insufficient usable stock.</p>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>

    <script>
        (function () {
            // --- Table Logic (Preserved) ---
            function tableContainer() { return document.getElementById('menu-table-container'); }
            async function refreshTable(url) {
                const targetUrl = url || window.location.href;
                try {
                    const response = await fetch(targetUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const data = await response.json();
                    const container = tableContainer();
                    container.innerHTML = data.html;
                    attachRowListeners(container);
                    attachPaginationListeners(container);
                } catch (err) { console.error(err); }
            }
            function attachPaginationListeners(scope) {
                scope.querySelectorAll('.pagination-link').forEach((link) => {
                    if (link.classList.contains('pointer-events-none')) return;
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const url = link.getAttribute('href');
                        refreshTable(url);
                    });
                });
            }
            function attachRowListeners(scope) {
                scope.querySelectorAll('.edit-menu-item-btn').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        window.location.href = `<?php echo e(url('admin/menu')); ?>/${btn.dataset.id}/edit`;
                    });
                });
                scope.querySelectorAll('.delete-menu-item-btn').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        submitAjaxAction({
                            url: `<?php echo e(url('admin/menu')); ?>/${btn.dataset.id}`,
                            method: 'DELETE',
                            confirmMessage: {
                                title: 'Delete Menu Item?',
                                message: `Are you sure you want to delete <b>${btn.dataset.itemName}</b>?`,
                                actionLabel: 'Delete',
                                type: 'danger'
                            },
                            onSuccess: () => refreshTable(window.location.href),
                        });
                    });
                });
            }

            window.showAvailabilityDetails = function(event, itemId, details) {
                event.stopPropagation();
                const popover = document.getElementById('availability-popover');
                const content = document.getElementById('availability-popover-content');
                let html = '';
                if (details.failing_ingredients && details.failing_ingredients.length > 0) {
                    details.failing_ingredients.forEach(ing => {
                        html += `<div class="flex flex-col gap-1"><span class="text-xs font-semibold text-gray-800">• ${ing.name}</span><span class="text-[11px] text-gray-500 ml-2">Required: ${ing.required} ${ing.unit || ''}</span></div>`;
                    });
                } else {
                    html = `<p class="text-xs text-gray-600">${details.reason || 'Unknown issue.'}</p>`;
                }
                content.innerHTML = html;
                popover.classList.remove('hidden');
                const trigger = event.target.closest('button');
                const triggerRect = trigger.getBoundingClientRect();
                const popoverRect = popover.getBoundingClientRect();
                const margin = 16;

                let top = triggerRect.bottom + 8;
                let left = triggerRect.left;

                if (top + popoverRect.height > window.innerHeight - margin) {
                    top = triggerRect.top - popoverRect.height - 8;
                }

                if (left + popoverRect.width > window.innerWidth - margin) {
                    left = window.innerWidth - popoverRect.width - margin;
                }

                if (left < margin) {
                    left = margin;
                }

                popover.style.top = `${top}px`;
                popover.style.left = `${left}px`;
            };

            document.getElementById('availability-popover-close')?.addEventListener('click', () => {
                document.getElementById('availability-popover').classList.add('hidden');
            });

            document.addEventListener('click', (e) => {
                const popover = document.getElementById('availability-popover');
                if (!popover.contains(e.target) && !e.target.closest('[onclick="showAvailabilityDetails(...)"]')) {
                    popover.classList.add('hidden');
                }
            });
            attachRowListeners(tableContainer());
            attachPaginationListeners(tableContainer());
        })();
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/menu/index.blade.php ENDPATH**/ ?>