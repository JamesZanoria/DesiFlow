
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

<div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row items-center justify-between gap-4 rounded-t-xl">
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

<div class="p-6 w-full">
    <style>
        .staff-grid {
            display: grid !important;
            gap: 2rem !important;
            width: 100% !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
        }
        @media (min-width: 768px) {
            .staff-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 1024px) {
            .staff-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }
    </style>

    <div class="staff-grid">
        <?php $__empty_1 = true; $__currentLoopData = $staff; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $avatarColor = $avatarPalette[crc32($member->name) % count($avatarPalette)];
            ?>
            <div id="staff-card-<?php echo e($member->user_id); ?>"
                 class="group relative bg-white rounded-xl shadow-[0_30px_60px_-12px_rgba(0,0,0,0.12)] border border-white transition-all duration-500 hover:shadow-amber-500/10 overflow-hidden flex flex-col h-full min-w-0">

                
                <div class="relative h-48 bg-slate-100 overflow-hidden flex items-center justify-center">
                    <div class="absolute top-4 right-4 px-3 py-1 rounded-full bg-white/70 backdrop-blur-md text-[9px] font-black text-slate-800 uppercase tracking-wider shadow-sm border border-white/50 z-20">
                        <?php echo e($roleLabel[$member->role] ?? ucfirst($member->role)); ?>

                    </div>

                    <div class="w-24 h-24 rounded-full border-4 border-white shadow-sm flex items-center justify-center text-white overflow-hidden z-10 <?php echo e($avatarColor); ?>">
                        <span class="text-2xl font-bold"><?php echo e(strtoupper(substr($member->name, 0, 1))); ?></span>
                    </div>

                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/10 to-transparent pointer-events-none"></div>

                    <?php if($member->role !== 'admin'): ?>
                        <div class="absolute top-4 left-4 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-30">
                            <div class="relative">
                                <button type="button"
                                        class="action-menu-btn p-2 rounded-full bg-white shadow-sm text-slate-400 hover:text-slate-600 transition-colors"
                                        onclick="event.stopPropagation(); toggleStaffMenu(this, 'menu-<?php echo e($member->user_id); ?>');">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>
                                </button>
                                <div class="action-menu-dropdown absolute left-0 mt-1 w-32 bg-white border border-slate-200 rounded-lg shadow-lg opacity-0 invisible transition-all duration-200 z-10 overflow-hidden"
                                     id="menu-<?php echo e($member->user_id); ?>">
                                    <a href="<?php echo e(route('admin.staff.edit', $member->user_id)); ?>" class="flex items-center gap-2 px-3 py-2 text-xs text-slate-600 hover:bg-slate-50 transition-colors" onclick="event.stopPropagation();">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                        Edit
                                    </a>
                                    <button type="button"
                                            class="delete-staff-btn w-full flex items-center gap-2 px-3 py-2 text-xs text-red-600 hover:bg-red-50 transition-colors"
                                            onclick="event.stopPropagation();"
                                            data-id="<?php echo e($member->user_id); ?>"
                                            data-name="<?php echo e($member->name); ?>">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                
                <div class="p-6 space-y-4 bg-white flex-1 flex flex-col justify-between">
                    <div class="flex justify-between items-start gap-3">
                        <div class="space-y-1 overflow-hidden">
                            <h3 class="text-xl font-black text-slate-900 truncate leading-tight"><?php echo e($member->name); ?></h3>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                <span class="text-[10px] text-slate-400 font-medium">Verified Account</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-50">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email</span>
                            <span class="text-xs font-medium text-slate-600 truncate ml-4"><?php echo e($member->email); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-gray-400">
                <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H3" /></svg>
                <p class="text-sm"><?php echo e(request('search') || request('role') ? 'No staff match your search or filter.' : 'No staff accounts yet. Click "Add Staff" to create one.'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/staff/_table.blade.php ENDPATH**/ ?>