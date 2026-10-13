
<?php
    $roleBadge = [
        'admin' => 'bg-indigo-100 text-indigo-700',
        'cashier' => 'bg-blue-100 text-blue-700',
        'kitchen' => 'bg-amber-100 text-amber-700',
    ];
    $roleLabel = [
        'admin' => 'Admin / Manager',
        'cashier' => 'Cashier',
        'kitchen' => 'Kitchen Staff',
    ];
    // Deterministic color per name
    $avatarPalette = ['bg-amber-500', 'bg-blue-500', 'bg-purple-500', 'bg-emerald-500', 'bg-rose-500', 'bg-indigo-500'];
?>
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="<?php echo e(route('admin.staff.index')); ?>" class="flex items-center gap-2 w-full md:w-auto">
            <?php echo $__env->make('admin.partials._filter_search', [
                'name' => 'search',
                'placeholder' => 'Search name or email...',
                'value' => request('search'),
                'suggestions' => $allStaffNames ?? [],
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php echo $__env->make('admin.partials._filter_select', [
                'name' => 'role',
                'label' => 'Role',
                'value' => request('role'),
                'options' => [
                    'admin' => 'Admin / Manager',
                    'cashier' => 'Cashier',
                    'kitchen' => 'Kitchen Staff',
                ],
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </form>
    </div>

    <table class="w-full text-sm text-left border-collapse">
        <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold border-b border-slate-200">
            <tr class="h-10">
                <th class="px-6 py-4 font-semibold">Name</th>
                <th class="px-6 py-4 font-semibold">Email</th>
                <th class="px-6 py-4 font-semibold">Role</th>
                <th class="px-6 py-4 font-semibold text-right">Actions</th>
            </tr>
        </thead>
        <tbody id="staff-table-body" class="divide-y divide-slate-100">
            <?php $__empty_1 = true; $__currentLoopData = $staff; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $avatarColor = $avatarPalette[crc32($member->name) % count($avatarPalette)];
                ?>
                <tr id="staff-row-<?php echo e($member->user_id); ?>" class="hover:bg-slate-50 transition-colors duration-150 group">
                    <td class="px-6 py-4 cell-name">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full <?php echo e($avatarColor); ?> text-white flex items-center justify-center text-xs font-semibold flex-shrink-0 shadow-sm">
                                <?php echo e(strtoupper(substr($member->name, 0, 1))); ?>

                            </div>
                            <span class="font-semibold text-gray-900"><?php echo e($member->name); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-500 cell-email"><?php echo e($member->email); ?></td>
                    <td class="px-6 py-4 cell-role">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium <?php echo e($roleBadge[$member->role] ?? 'bg-gray-100 text-gray-700'); ?> border border-transparent">
                            <?php echo e($roleLabel[$member->role] ?? ucfirst($member->role)); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <?php if($member->role !== 'admin'): ?>
                            <div class="flex justify-end gap-1">
                                <a href="<?php echo e(route('admin.staff.edit', $member->user_id)); ?>"
                                    class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                </a>
                                <button type="button"
                                    class="delete-staff-btn p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    title="Delete"
                                    data-id="<?php echo e($member->user_id); ?>"
                                    data-name="<?php echo e($member->name); ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                                </button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr id="staff-empty-row">
                    <td colspan="4" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H3" /></svg>
                            <p class="text-sm"><?php echo e(request('search') || request('role') ? 'No staff match your search or filter.' : 'No staff accounts yet. Click "Add Staff" to create one.'); ?></p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/30">
        <p id="staff-pagination-summary" class="text-xs text-slate-500 font-medium uppercase tracking-wider">
            Showing <?php echo e($staff->firstItem() ?? 0); ?> to <?php echo e($staff->lastItem() ?? 0); ?> of <?php echo e($staff->total()); ?> staff
        </p>
        <div class="flex gap-2">
            <a href="<?php echo e($staff->onFirstPage() ? '#' : $staff->previousPageUrl()); ?>"
                data-ajax-page="prev"
                class="pagination-link p-2 rounded-lg border border-slate-200 bg-white transition-all <?php echo e($staff->onFirstPage() ? 'text-gray-300 border-slate-100 pointer-events-none' : 'text-gray-600 hover:bg-gray-50 hover:border-slate-300'); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </a>
            <a href="<?php echo e($staff->hasMorePages() ? $staff->nextPageUrl() : '#'); ?>"
                data-ajax-page="next"
                class="pagination-link p-2 rounded-lg border border-slate-200 bg-white transition-all <?php echo e($staff->hasMorePages() ? 'text-gray-600 hover:bg-gray-50 hover:border-slate-300' : 'text-gray-300 border-slate-100 pointer-events-none'); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        </div>
    </div>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/staff/_table.blade.php ENDPATH**/ ?>