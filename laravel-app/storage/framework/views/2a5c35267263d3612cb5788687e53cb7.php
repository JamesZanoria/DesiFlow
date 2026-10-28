
<div class="mb-6 flex justify-center">
    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 shadow-inner">
        <a href="<?php echo e(route('admin.inventory.index')); ?>"
            class="px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-200 <?php echo e($activeTab === 'stock' ? 'bg-white text-amber-700 shadow-sm ring-1 ring-amber-200' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'); ?>">
            Current Stock
        </a>
        <a href="<?php echo e(route('admin.inventory.ledger')); ?>"
            class="px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-200 <?php echo e($activeTab === 'ledger' ? 'bg-white text-amber-700 shadow-sm ring-1 ring-amber-200' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'); ?>">
            Stock Movement Ledger
        </a>
    </div>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/inventory/_tabs.blade.php ENDPATH**/ ?>