
<?php
    $showSearch = count($options) > 7;
?>
<?php if($showSearch): ?>
    <div class="p-2 border-b">
        <input type="text" class="filter-select-search w-full border rounded-md px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-amber-400" placeholder="Search <?php echo e(strtolower($label)); ?>...">
    </div>
<?php endif; ?>

<div class="filter-select-list max-h-64 overflow-y-auto py-1">
    <button type="button" class="filter-select-option w-full flex items-center justify-between gap-2 px-3 py-2 text-xs text-left hover:bg-gray-50 <?php echo e(!$value ? 'font-semibold text-gray-800' : 'text-gray-600'); ?>" data-option-value="" data-option-label="all">
        <span>All</span>
        <?php if(!$value): ?>
            <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
        <?php endif; ?>
    </button>
    <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optValue => $optLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="button" class="filter-select-option w-full flex items-center justify-between gap-2 px-3 py-2 text-xs text-left hover:bg-gray-50 <?php echo e((string) $value === (string) $optValue ? 'font-semibold text-gray-800' : 'text-gray-600'); ?>" data-option-value="<?php echo e($optValue); ?>" data-option-label="<?php echo e(strtolower($optLabel)); ?>">
            <span class="truncate"><?php echo e($optLabel); ?></span>
            <?php if((string) $value === (string) $optValue): ?>
                <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
            <?php endif; ?>
        </button>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <p class="filter-select-empty hidden px-3 py-4 text-center text-xs text-gray-400">No matches.</p>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/partials/_filter_select_popover.blade.php ENDPATH**/ ?>