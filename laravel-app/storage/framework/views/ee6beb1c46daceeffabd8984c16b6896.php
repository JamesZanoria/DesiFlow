
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Items</p>
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
        </div>
        <p class="text-2xl font-bold text-gray-800"><?php echo e(number_format($stats['totalItems'])); ?></p>
    </div>

    <div class="bg-white border rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Low Stock</p>
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
        </div>
        <p class="text-2xl font-bold <?php echo e($stats['lowStock'] > 0 ? 'text-amber-600' : 'text-gray-800'); ?>"><?php echo e(number_format($stats['lowStock'])); ?></p>
        <?php if($stats['lowStock'] > 0): ?>
            <p class="text-xs text-amber-600 mt-0.5">Action recommended</p>
        <?php endif; ?>
    </div>

    <div class="bg-white border rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Out of Stock</p>
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <p class="text-2xl font-bold <?php echo e($stats['outOfStock'] > 0 ? 'text-red-600' : 'text-gray-800'); ?>"><?php echo e(number_format($stats['outOfStock'])); ?></p>
        <?php if($stats['outOfStock'] > 0): ?>
            <p class="text-xs text-red-600 mt-0.5">Action required today</p>
        <?php endif; ?>
    </div>

    <div class="bg-white border rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Value</p>
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
        </div>
        <p class="text-2xl font-bold text-gray-800">₱<?php echo e(number_format($stats['totalValue'], 2)); ?></p>
        <p class="text-xs text-gray-400 mt-0.5">Est. current stock</p>
    </div>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/inventory/_current_stock_stats.blade.php ENDPATH**/ ?>