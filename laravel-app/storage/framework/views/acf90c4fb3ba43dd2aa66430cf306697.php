
<?php
    $currentLabel = $value !== null && $value !== '' ? ($options[$value] ?? $value) : 'All';
?>
<div class="relative inline-block" data-filter-select data-name="<?php echo e($name); ?>">
    <input type="hidden" name="<?php echo e($name); ?>" value="<?php echo e($value); ?>">

    <button type="button"
        class="filter-select-trigger relative inline-flex items-center gap-1.5 border rounded-full bg-white pl-3 <?php echo e($value ? 'pr-8' : 'pr-3'); ?> py-1.5 text-sm max-w-full <?php echo e($value ? 'border-gray-400' : 'border-gray-300'); ?> hover:border-gray-400"
        aria-haspopup="listbox" aria-expanded="false">
        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
        
        <span class="min-w-0 truncate">
            <span class="text-gray-500"><?php echo e($label); ?>:</span>
            <span class="text-gray-800 font-medium"><?php echo e($currentLabel); ?></span>
        </span>
        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>

    <?php if($value): ?>
        
        <button type="button"
            class="filter-chip-clear absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            data-clear-field="<?php echo e($name); ?>"
            title="Clear <?php echo e(strtolower($label)); ?> filter">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    <?php endif; ?>

    <div class="filter-select-popover hidden absolute left-0 mt-2 w-56 bg-white border rounded-lg shadow-lg z-20 overflow-hidden">
        <?php echo $__env->make('admin.partials._filter_select_popover', ['name' => $name, 'label' => $label, 'value' => $value, 'options' => $options], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/partials/_filter_select.blade.php ENDPATH**/ ?>