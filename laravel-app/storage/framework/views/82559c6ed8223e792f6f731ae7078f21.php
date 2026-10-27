
<?php
    $suggestions = $suggestions ?? collect();
?>
<div class="relative" <?php if($suggestions->isNotEmpty()): ?> data-search-suggest <?php endif; ?>>
    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>

    <input type="text" name="<?php echo e($name); ?>" value="<?php echo e($value); ?>"
        placeholder="<?php echo e($placeholder); ?>"
        autocomplete="off"
        class="<?php echo e($suggestions->isNotEmpty() ? 'search-suggest-input' : ''); ?> border rounded-lg pl-9 <?php echo e($value ? 'pr-8' : 'pr-3'); ?> py-2 text-sm w-56">

    <?php if($value): ?>
        <button type="button"
            class="filter-chip-clear absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            data-clear-field="<?php echo e($name); ?>"
            title="Clear search">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    <?php endif; ?>

    <?php if($suggestions->isNotEmpty()): ?>
        <div class="search-suggest-list hidden absolute left-0 mt-1 w-full bg-white border rounded-lg shadow-lg z-20 max-h-64 overflow-y-auto py-1">
            <?php echo $__env->make('admin.partials._search_suggestions', ['suggestions' => $suggestions], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    <?php endif; ?>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/partials/_filter_search.blade.php ENDPATH**/ ?>