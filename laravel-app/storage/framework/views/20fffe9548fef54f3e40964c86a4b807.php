
<?php
    $meta = \App\Http\Controllers\Admin\OrderMonitorController::STATUS_META;
?>
<div id="orders-board-grid" class="flex gap-6 h-full min-w-max">
    <?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $orders): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="w-80 flex flex-col h-full">
            
            <div class="flex items-center justify-between mb-4 px-1">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full <?php echo e($meta[$status]['dot']); ?>"></span>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider"><?php echo e($meta[$status]['label']); ?></h3>
                </div>
                <span class="text-[10px] font-bold bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full"><?php echo e($orders->count()); ?></span>
            </div>

            
            <div class="flex-1 overflow-y-auto pr-2 space-y-4 pb-8 scrollbar-hide">
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="order-card group bg-white border-l-4 <?php echo e($meta[$status]['border']); ?> border-y border-r border-slate-200 rounded-xl shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-0.5">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <p class="text-sm font-bold text-slate-900">Order #<?php echo e($order->order_id); ?></p>
                                <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                                    <?php if($order->order_type === 'takeout'): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-medium">Takeout</span>
                                    <?php elseif($order->table_number): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-medium">Table <?php echo e($order->table_number); ?></span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-50 text-slate-700 font-medium">Dine-in</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="order-elapsed text-[10px] font-medium text-slate-400 whitespace-nowrap"
                                data-created-at="<?php echo e($order->created_at->toIso8601String()); ?>"></span>
                        </div>

                        <div class="space-y-1.5 mb-4">
                            <?php $__currentLoopData = $order->orderItems->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex justify-between items-center text-xs text-slate-600">
                                    <span class="truncate"><?php echo e($item->menuItem->item_name ?? 'Item'); ?></span>
                                    <span class="font-semibold text-slate-900 ml-2"><?php echo e($item->quantity); ?>x</span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if($order->orderItems->count() > 4): ?>
                                <p class="text-[10px] text-slate-400 italic">+<?php echo e($order->orderItems->count() - 4); ?> more items</p>
                            <?php endif; ?>
                        </div>

                        <div class="pt-3 border-t border-slate-50 flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <p class="text-[10px] text-slate-400">By <span class="text-slate-600 font-medium"><?php echo e($order->cashier->name ?? 'Staff'); ?></span></p>
                            </div>
                            <button type="button"
                                class="view-order-btn text-xs font-bold text-amber-600 hover:text-amber-700 transition-colors flex items-center gap-1"
                                data-id="<?php echo e($order->order_id); ?>">
                                View Details
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center">
                        <p class="text-xs text-slate-400 italic">No orders in this stage.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/orders/_board.blade.php ENDPATH**/ ?>