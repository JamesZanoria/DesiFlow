
<?php
    $statusMeta = [
        'negative_stock' => ['label' => 'Negative Stock', 'badge' => 'bg-purple-100 text-purple-700'],
        'active' => ['label' => 'Active', 'badge' => 'bg-green-100 text-green-700'],
        'low_stock' => ['label' => 'Low Stock', 'badge' => 'bg-amber-100 text-amber-700'],
        'out_of_stock' => ['label' => 'Out of Stock', 'badge' => 'bg-red-100 text-red-700'],
        'expiring_soon' => ['label' => 'Expiring Soon', 'badge' => 'bg-orange-100 text-orange-700'],
        'expired' => ['label' => 'Expired', 'badge' => 'bg-rose-200 text-rose-800'],
    ];
?>
<div class="bg-white border border-gray-200 rounded-xl shadow-sm">
    <div class="w-full">
        <table class="w-full text-sm text-left border-collapse table-fixed">
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
                <?php $__empty_1 = true; $__currentLoopData = $ingredients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ingredient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $status = $ingredient->stockStatus(); ?>
                    <tr id="ingredient-row-<?php echo e($ingredient->ingredient_id); ?>" class="hover:bg-gray-50 transition-colors duration-150 group">
                        <td class="px-6 py-4 cell-name">
                            <div class="flex items-center gap-3">
                                <span class="font-semibold text-gray-900 cell-name-text truncate"><?php echo e($ingredient->ingredient_name); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-500 cell-unit"><?php echo e($ingredient->unit_of_measurement); ?></td>
                        <td class="px-6 py-4 cell-stock <?php echo e($status !== 'active' ? 'text-red-600 font-semibold' : 'text-gray-900'); ?>"><?php echo e(number_format($ingredient->current_stock ?? 0, 2)); ?></td>
                        <td class="px-6 py-4 text-gray-900 font-medium cell-cost">₱<?php echo e(number_format($ingredient->unit_cost, 2)); ?></td>
                        <td class="px-6 py-4 text-gray-500 cell-threshold"><?php echo e(number_format($ingredient->restock_threshold, 2)); ?></td>
                        <td class="px-6 py-4 cell-status">
                            <div class="flex items-center gap-2">
                                <?php
                                    $status = $ingredient->stockStatus();
                                    $isExpiryStatus = ($status === 'expired' || $status === 'expiring_soon');
                                    $dotColor = $status === 'expired' ? 'bg-rose-600' : 'bg-orange-500';
                                    $borderColor = $status === 'expired' ? 'border-rose-300' : 'border-orange-200';
                                ?>

                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php echo e($statusMeta[$status]['badge']); ?> <?php echo e($isExpiryStatus ? $borderColor : 'border-transparent'); ?> border">
                                        <?php if($isExpiryStatus): ?>
                                            <span class="w-1.5 h-1.5 rounded-full <?php echo e($dotColor); ?>"></span>
                                        <?php endif; ?>
                                        <?php echo e($statusMeta[$status]['label']); ?>

                                    </span>

                                    <?php if($status === 'low_stock'): ?>
                                        <span class="text-[11px] font-medium text-gray-600">
                                            <?php echo e(number_format($ingredient->current_stock ?? 0, 2)); ?> <?php echo e($ingredient->unit_of_measurement); ?> remaining
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if($isExpiryStatus): ?>
                                    <button type="button"
                                        class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                                        onclick="showWasteDetails(event, <?php echo e(json_encode([
                                            'name' => $ingredient->ingredient_name,
                                            'unit' => $ingredient->unit_of_measurement,
                                            'expired' => number_format($ingredient->expiredQuantity(), 2),
                                            'expiring' => number_format($ingredient->expiringSoonQuantity(), 2)
                                        ])); ?>)"
                                        title="View waste details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end items-center gap-1">
                                <?php if($status === 'expired'): ?>
                                    
                                    <div class="relative inline-block text-left">
                                        <button type="button"
                                            class="action-menu-btn p-2 rounded-full bg-white shadow-sm text-gray-400 hover:text-slate-600 transition-colors"
                                            data-menu-id="menu-<?php echo e($ingredient->ingredient_id); ?>">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 10a2 2 0 110-4 2 2 0 010 4zM10 10a2 2 0 110-4 2 2 0 010 4zM15 10a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>
                                        <div class="action-menu-dropdown absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-xl shadow-lg opacity-0 invisible transition-all duration-200 z-50 overflow-hidden"
                                             id="menu-<?php echo e($ingredient->ingredient_id); ?>">
                                            <div class="p-1 space-y-1">
                                                <button type="button"
                                                    class="mark-waste-btn w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-rose-50 hover:text-rose-700 transition-colors"
                                                    title="Mark expired stock as waste"
                                                    data-id="<?php echo e($ingredient->ingredient_id); ?>"
                                                    data-name="<?php echo e($ingredient->ingredient_name); ?>"
                                                    data-current-stock="<?php echo e($ingredient->current_stock ?? 0); ?>"
                                                    data-unit="<?php echo e($ingredient->unit_of_measurement); ?>">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    Mark as Waste
                                                </button>
                                                <button type="button"
                                                    class="edit-ingredient-btn w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                                                    title="Edit"
                                                    data-id="<?php echo e($ingredient->ingredient_id); ?>"
                                                    onclick="window.location.href='<?php echo e(route('admin.inventory.edit', $ingredient->ingredient_id)); ?>'">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                                    Edit Ingredient
                                                </button>
                                                <button type="button"
                                                    class="delete-ingredient-btn w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-red-50 hover:text-red-700 transition-colors"
                                                    title="Delete" data-id="<?php echo e($ingredient->ingredient_id); ?>" data-name="<?php echo e($ingredient->ingredient_name); ?>">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                                                    Delete Ingredient
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    
                                    <button type="button"
                                        class="edit-ingredient-btn p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Edit"
                                        onclick="window.location.href='<?php echo e(route('admin.inventory.edit', $ingredient->ingredient_id)); ?>'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                    </button>
                                    <button type="button"
                                        class="delete-ingredient-btn p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                        title="Delete" data-id="<?php echo e($ingredient->ingredient_id); ?>" data-name="<?php echo e($ingredient->ingredient_name); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr id="inventory-empty-row">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4-8-4" /></svg>
                                <p class="text-sm"><?php echo e((request('search') || request('category')) ? 'No ingredients match your search or filter.' : 'No ingredients yet. Click "Add Ingredient" to create one.'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p id="inventory-empty-state" class="px-4 py-6 text-center text-gray-400 <?php echo e($ingredients->isNotEmpty() ? 'hidden' : ''); ?>">
        
    </p>
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50/30">
        <p id="inventory-pagination-summary" class="text-xs text-gray-500 font-medium uppercase tracking-wider">
            Showing <?php echo e($ingredients->firstItem() ?? 0); ?> to <?php echo e($ingredients->lastItem() ?? 0); ?> of <?php echo e(number_format($ingredients->total())); ?> entries
        </p>
        <?php echo $__env->make('admin.inventory._pagination_numbered', ['paginator' => $ingredients], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>
<?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/inventory/_table.blade.php ENDPATH**/ ?>