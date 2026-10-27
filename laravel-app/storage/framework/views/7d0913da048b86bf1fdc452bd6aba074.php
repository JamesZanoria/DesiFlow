
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="p-3 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
        <form method="GET" action="<?php echo e(route('admin.menu.index')); ?>" class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
            <?php echo $__env->make('admin.partials._filter_search', [
                'name' => 'search',
                'placeholder' => 'Search menu items...',
                'value' => request('search'),
                'suggestions' => $allItemNames,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php echo $__env->make('admin.partials._filter_select', [
                'name' => 'category',
                'label' => 'Category',
                'value' => request('category'),
                'options' => $categories->mapWithKeys(fn ($c) => [$c => $c]),
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </form>
    </div>

    
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider font-semibold border-b border-gray-200">
                <tr class="h-10">
                    <th class="px-6 py-4 font-semibold">Name</th>
                    <th class="px-6 py-4 font-semibold">Category</th>
                    <th class="px-6 py-4 font-semibold">Price</th>
                    <th class="px-6 py-4 font-semibold">Inventory</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="menu-table-body" class="divide-y divide-gray-100">
                <?php $__empty_1 = true; $__currentLoopData = $menuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr id="menu-row-<?php echo e($item->menu_item_id); ?>" class="hover:bg-gray-50 transition-colors duration-150 group">
                        <td class="px-6 py-4 cell-name-cell">
                            <div class="flex items-center gap-3">
                                <?php if($item->image_url): ?>
                                    <img src="<?php echo e($item->image_url); ?>" alt="" class="w-10 h-10 rounded-lg object-cover bg-gray-100 cell-thumb shadow-sm">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center cell-thumb shadow-sm">
                                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 20.25H4.5A2.25 2.25 0 012.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v9.75a2.25 2.25 0 01-2.25 2.25zM9 9.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                                    </div>
                                <?php endif; ?>
                                <span class="font-semibold text-gray-900 cell-item-name"><?php echo e($item->item_name); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-500 cell-category"><?php echo e($item->category); ?></td>
                        <td class="px-6 py-4 text-gray-900 font-medium cell-price">₱<?php echo e(number_format($item->price, 2)); ?></td>
                        <td class="px-6 py-4">
                            <?php $invStatus = $item->inventoryAvailable(); ?>
                            <?php if($invStatus === 'available'): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Available
                                </span>
                            <?php elseif($invStatus === 'warning'): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Low Stock
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Unavailable
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 cell-status">
                            <?php $details = $item->getAvailabilityDetails(); ?>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php echo e($details['is_available'] ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'); ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo e($details['is_available'] ? 'bg-green-500' : 'bg-red-500'); ?>"></span>
                                    <?php echo e($details['is_available'] ? 'Active' : 'Unavailable'); ?>

                                </span>
                                <?php if(!$details['is_available']): ?>
                                    <button type="button"
                                        class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                                        onclick="showAvailabilityDetails(event, <?php echo e($item->menu_item_id); ?>, <?php echo e(json_encode($details)); ?>)"
                                        title="Why unavailable?">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                <button type="button"
                                    class="edit-menu-item-btn p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                    title="Edit"
                                    data-id="<?php echo e($item->menu_item_id); ?>"
                                    data-item-name="<?php echo e($item->item_name); ?>"
                                    data-category="<?php echo e($item->category); ?>"
                                    data-price="<?php echo e($item->price); ?>"
                                    data-description="<?php echo e($item->description); ?>"
                                    data-image-url="<?php echo e($item->image_url); ?>"
                                    data-is-available="<?php echo e($item->is_available ? '1' : '0'); ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                </button>
                                <button type="button"
                                    class="delete-menu-item-btn p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    title="Delete"
                                    data-id="<?php echo e($item->menu_item_id); ?>"
                                    data-item-name="<?php echo e($item->item_name); ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr class="hidden md:table-row">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H3" /></svg>
                                <p class="text-sm"><?php echo e(request('search') || request('category') ? 'No menu items match your search or filter.' : 'No menu items yet. Click "Add New Item" to create one.'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    
    <div class="md:hidden space-y-3 p-4">
        <?php $__empty_1 = true; $__currentLoopData = $menuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex items-center gap-3">
                        <?php if($item->image_url): ?>
                            <img src="<?php echo e($item->image_url); ?>" alt="" class="w-12 h-12 rounded-lg object-cover bg-gray-100 shadow-sm">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center shadow-sm">
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 20.25H4.5A2.25 2.25 0 012.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v9.75a2.25 2.25 0 01-2.25 2.25zM9 9.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-bold text-slate-900"><?php echo e($item->item_name); ?></p>
                            <p class="text-xs text-slate-500"><?php echo e($item->category); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-slate-900">₱<?php echo e(number_format($item->price, 2)); ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <div class="flex gap-2">
                        <?php $invStatus = $item->inventoryAvailable(); ?>
                        <?php if($invStatus === 'available'): ?>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700 border border-green-200">
                                <span class="w-1 h-1 rounded-full bg-green-500"></span>
                                Available
                            </span>
                        <?php elseif($invStatus === 'warning'): ?>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700 border border-amber-200">
                                <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                Low Stock
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700 border border-red-200">
                                <span class="w-1 h-1 rounded-full bg-red-500"></span>
                                Unavailable
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-1">
                        <button type="button"
                            class="edit-menu-item-btn p-2 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors"
                            title="Edit"
                            data-id="<?php echo e($item->menu_item_id); ?>"
                            data-item-name="<?php echo e($item->item_name); ?>"
                            data-category="<?php echo e($item->category); ?>"
                            data-price="<?php echo e($item->price); ?>"
                            data-description="<?php echo e($item->description); ?>"
                            data-image-url="<?php echo e($item->image_url); ?>"
                            data-is-available="<?php echo e($item->is_available ? '1' : '0'); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                        </button>
                        <button type="button"
                            class="delete-menu-item-btn p-2 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 transition-colors"
                            title="Delete"
                            data-id="<?php echo e($item->menu_item_id); ?>"
                            data-item-name="<?php echo e($item->item_name); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-center py-12 text-gray-400">
                <p class="text-sm"><?php echo e(request('search') || request('category') ? 'No menu items match your search or filter.' : 'No menu items yet.'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50/30 gap-3 mt-4">
        <p id="menu-pagination-summary" class="text-xs text-gray-500 font-medium uppercase tracking-wider">
            Showing <?php echo e($menuItems->firstItem() ?? 0); ?> to <?php echo e($menuItems->lastItem() ?? 0); ?> of <?php echo e($menuItems->total()); ?> items
        </p>
        <div class="flex gap-2">
            <a href="<?php echo e($menuItems->onFirstPage() ? '#' : $menuItems->previousPageUrl()); ?>"
                data-ajax-page="prev"
                class="pagination-link p-2 rounded-lg border border-gray-200 bg-white transition-all <?php echo e($menuItems->onFirstPage() ? 'text-gray-300 border-gray-100 pointer-events-none' : 'text-gray-600 hover:bg-gray-50 hover:border-gray-300'); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </a>
            <a href="<?php echo e($menuItems->hasMorePages() ? $menuItems->nextPageUrl() : '#'); ?>"
                data-ajax-page="next"
                class="pagination-link p-2 rounded-lg border border-gray-200 bg-white transition-all <?php echo e($menuItems->hasMorePages() ? 'text-gray-600 hover:bg-gray-50 hover:border-gray-300' : 'text-gray-300 border-gray-100 pointer-events-none'); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        </div>
    </div>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/menu/_table.blade.php ENDPATH**/ ?>