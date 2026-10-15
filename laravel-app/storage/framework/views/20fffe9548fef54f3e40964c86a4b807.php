
<?php
    $meta = \App\Http\Controllers\Admin\OrderMonitorController::STATUS_META;
?>
<div id="orders-board-grid" class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $orders): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="bg-white border rounded-xl shadow-sm flex flex-col min-h-[28rem]">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full <?php echo e($meta[$status]['dot']); ?>"></span>
                    <h3 class="text-sm font-semibold text-gray-800"><?php echo e($meta[$status]['label']); ?></h3>
                </div>
                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><?php echo e($orders->count()); ?></span>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="order-card bg-white border-l-4 <?php echo e($meta[$status]['border']); ?> border-y border-r rounded-lg shadow-sm p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-bold text-gray-800">Order #<?php echo e($order->order_id); ?></p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <?php if($order->order_type === 'takeout'): ?>
                                        Takeout
                                    <?php elseif($order->table_number): ?>
                                        Table <?php echo e($order->table_number); ?>

                                    <?php else: ?>
                                        Dine-in
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="order-elapsed text-xs font-medium text-gray-400 whitespace-nowrap"
                                data-created-at="<?php echo e($order->created_at->toIso8601String()); ?>"></span>
                        </div>

                        <div class="mt-2 text-xs text-gray-600 space-y-0.5">
                            <?php $__currentLoopData = $order->orderItems->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <p class="truncate"><?php echo e($item->quantity); ?>&times; <?php echo e($item->menuItem->item_name ?? 'Item'); ?></p>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if($order->orderItems->count() > 3): ?>
                                <p class="text-gray-400">+<?php echo e($order->orderItems->count() - 3); ?> more item<?php echo e($order->orderItems->count() - 3 === 1 ? '' : 's'); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-xs text-gray-400">
                                <?php if($order->cashier): ?>
                                    by <?php echo e($order->cashier->name); ?>

                                <?php endif; ?>
                            </p>
                            <button type="button"
                                class="view-order-btn text-xs font-medium text-amber-600 hover:underline"
                                data-id="<?php echo e($order->order_id); ?>">
                                View
                            </button>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-center text-xs text-gray-400 py-8">No orders in this stage.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/orders/_board.blade.php ENDPATH**/ ?>