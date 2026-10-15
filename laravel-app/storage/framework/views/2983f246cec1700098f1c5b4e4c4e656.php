
<?php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    // Always show page 1 and the last page, plus one page on either side of
    // the current one; collapse any gap into a single "..." rather than
    // listing every page, which is what makes this usable at 241 pages.
    $pageNumbers = collect();
    for ($p = 1; $p <= $last; $p++) {
        if ($p === 1 || $p === $last || abs($p - $current) <= 1) {
            $pageNumbers->push($p);
        } elseif ($pageNumbers->last() !== '...') {
            $pageNumbers->push('...');
        }
    }
?>
<div class="flex gap-1.5">
    <a href="<?php echo e($paginator->onFirstPage() ? '#' : $paginator->previousPageUrl()); ?>"
        data-ajax-page="prev"
        class="pagination-link p-2 rounded-lg border <?php echo e($paginator->onFirstPage() ? 'text-gray-300 border-gray-200 pointer-events-none' : 'text-gray-600 border-gray-300 hover:bg-gray-50'); ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
    </a>

    <?php $__currentLoopData = $pageNumbers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($page === '...'): ?>
            <span class="w-9 h-9 flex items-center justify-center text-sm text-gray-400">…</span>
        <?php else: ?>
            <a href="<?php echo e($paginator->url($page)); ?>"
                data-ajax-page="<?php echo e($page); ?>"
                class="pagination-link w-9 h-9 flex items-center justify-center rounded-lg border text-sm font-medium <?php echo e($page === $current ? 'bg-amber-500 text-white border-amber-500' : 'text-gray-600 border-gray-300 hover:bg-gray-50'); ?>">
                <?php echo e($page); ?>

            </a>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <a href="<?php echo e($paginator->hasMorePages() ? $paginator->nextPageUrl() : '#'); ?>"
        data-ajax-page="next"
        class="pagination-link p-2 rounded-lg border <?php echo e($paginator->hasMorePages() ? 'text-gray-600 border-gray-300 hover:bg-gray-50' : 'text-gray-300 border-gray-200 pointer-events-none'); ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
    </a>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/inventory/_pagination_numbered.blade.php ENDPATH**/ ?>