
<div class="mb-4">
    <div class="flex gap-6 border-b border-gray-200">
        <a href="<?php echo e(route('admin.inventory.index')); ?>"
            class="pb-3 text-sm font-semibold border-b-2 -mb-px transition-colors <?php echo e($activeTab === 'stock' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-800 hover:text-gray-900'); ?>">
            Current Stock
        </a>
        <a href="<?php echo e(route('admin.inventory.ledger')); ?>"
            class="pb-3 text-sm font-semibold border-b-2 -mb-px transition-colors <?php echo e($activeTab === 'ledger' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-800 hover:text-gray-900'); ?>">
            Stock Movement Ledger
        </a>
    </div>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/inventory/_tabs.blade.php ENDPATH**/ ?>