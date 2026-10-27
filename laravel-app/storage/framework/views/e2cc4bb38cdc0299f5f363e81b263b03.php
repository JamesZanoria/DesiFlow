
<?php $__currentLoopData = $suggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $suggestion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <button type="button" class="search-suggestion w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 truncate" data-value="<?php echo e($suggestion); ?>"><?php echo e($suggestion); ?></button>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<p class="search-suggest-empty hidden px-3 py-4 text-center text-xs text-gray-400">No matches.</p><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/partials/_search_suggestions.blade.php ENDPATH**/ ?>