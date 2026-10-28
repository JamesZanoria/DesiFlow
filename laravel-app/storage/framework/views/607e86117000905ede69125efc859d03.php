<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome to the Dashboard</h1>
        <p class="text-gray-500 max-w-md mx-auto">
            This page is currently under development by the team.
            For now, you can use the <a href="<?php echo e(route('admin.assistant.index')); ?>" class="font-semibold text-amber-600 hover:underline">Smart Business Assistant</a> to get quick insights!
        </p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/dashboard/index.blade.php ENDPATH**/ ?>