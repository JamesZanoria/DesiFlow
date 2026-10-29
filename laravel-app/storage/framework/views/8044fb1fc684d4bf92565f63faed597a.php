<?php $__env->startSection('title', 'Real-Time Order Monitoring'); ?>

<?php $__env->startSection('content'); ?>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden">
        
        <header class="px-8 py-6 bg-white border-b border-slate-200 flex items-center justify-between shrink-0">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Real-Time Order Monitoring</h1>
                <p class="text-sm text-slate-500 mt-1">Live synchronization of all active restaurant orders.</p>
            </div>

            
            <div id="status-filter-pills" class="flex items-center gap-2 p-1 bg-slate-100 rounded-full border border-slate-200">
                <a href="<?php echo e(route('admin.orders.index')); ?>"
                    data-status=""
                    class="filter-pill px-4 py-1.5 rounded-full text-xs font-semibold transition-all <?php echo e(!$statusFilter ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'); ?>">
                    All Orders
                </a>
                <?php $__currentLoopData = \App\Http\Controllers\Admin\OrderMonitorController::STATUS_META; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('admin.orders.index', ['status' => $status])); ?>"
                        data-status="<?php echo e($status); ?>"
                        class="filter-pill px-4 py-1.5 rounded-full text-xs font-semibold transition-all flex items-center gap-2 <?php echo e($statusFilter === $status ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700'); ?>">
                        <span class="w-2 h-2 rounded-full <?php echo e($meta['dot']); ?>"></span>
                        <?php echo e($meta['label']); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </header>

        
        <main id="orders-board-container" class="flex-1 overflow-x-auto overflow-y-hidden p-8">
            <?php echo $__env->make('admin.orders._board', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </main>

        
        <div id="order-quickview-drawer" class="fixed inset-y-0 right-0 w-full max-w-md bg-slate-900 shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
            <div class="bg-slate-800 px-6 py-4 border-b border-slate-700 flex items-center justify-between shrink-0">
                <h2 class="text-lg font-bold text-white tracking-tight">Order Details</h2>
                <button type="button" id="order-quickview-close" class="p-2 rounded-full text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div id="order-quickview-body" class="flex-1 overflow-y-auto p-6 text-slate-300">
                <p class="text-center text-sm text-slate-500 py-8">Loading…</p>
            </div>
        </div>


        <script>
            (function () {
                const boardContainer = document.getElementById('orders-board-container');
                const drawer = document.getElementById('order-quickview-drawer');
                const modalBody = document.getElementById('order-quickview-body');
                const baseUrl = '<?php echo e(url('admin/orders')); ?>';

                function elapsedLabel(isoString) {
                    const diffMinutes = Math.floor((Date.now() - new Date(isoString).getTime()) / 60000);
                    if (diffMinutes < 1) return 'Just now';
                    if (diffMinutes < 60) return `${diffMinutes}m ago`;
                    const hours = Math.floor(diffMinutes / 60);
                    const mins = diffMinutes % 60;
                    return `${hours}h ${mins}m ago`;
                }

                function tickElapsedLabels() {
                    boardContainer.querySelectorAll('.order-elapsed').forEach((el) => {
                        el.textContent = elapsedLabel(el.dataset.createdAt);
                    });
                }

                async function openQuickView(orderId) {
                    drawer.classList.remove('translate-x-full');
                    document.body.classList.add('overflow-hidden');
                    modalBody.innerHTML = '<p class="text-center text-sm text-slate-500 py-8">Loading…</p>';

                    try {
                        // Use the route that matches the controller's expectaton: /admin/orders/{order}
                        const response = await fetch(`${baseUrl}/${orderId}`, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) throw new Error('Request failed');
                        const data = await response.json();
                        modalBody.innerHTML = data.html;
                    } catch (err) {
                        console.error('Failed to load order details', err);
                        modalBody.innerHTML = '<p class="text-center text-sm text-red-500 py-8">Could not load this order. Please try again.</p>';
                    }
                }

                function closeQuickView() {
                    drawer.classList.add('translate-x-full');
                    document.body.classList.remove('overflow-hidden');
                }

                document.getElementById('order-quickview-close').addEventListener('click', closeQuickView);
                drawer.addEventListener('click', (e) => { if (e.target === drawer) closeQuickView(); });

                function attachCardListeners(scope) {
                    scope.querySelectorAll('.view-order-btn').forEach((btn) => {
                        btn.addEventListener('click', () => openQuickView(btn.dataset.id));
                    });
                }

                let currentStatus = '<?php echo e($statusFilter); ?>';

                async function refreshBoard() {
                    const url = currentStatus
                        ? `<?php echo e(route('admin.orders.index')); ?>?status=${encodeURIComponent(currentStatus)}`
                        : `<?php echo e(route('admin.orders.index')); ?>`;

                    try {
                        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) return;
                        const data = await response.json();

                        // Simple DOM morphing approach:
                        // We replace the content but keep the scroll position of the main container
                        const currentScroll = boardContainer.scrollLeft;
                        boardContainer.innerHTML = data.html;
                        boardContainer.scrollLeft = currentScroll;

                        attachCardListeners(boardContainer);
                        tickElapsedLabels();
                    } catch (err) {
                        console.error('Failed to refresh order board', err);
                    }
                }

                document.getElementById('status-filter-pills').addEventListener('click', (e) => {
                    const pill = e.target.closest('.filter-pill');
                    if (!pill) return;
                    e.preventDefault();

                    currentStatus = pill.dataset.status;
                    document.querySelectorAll('.filter-pill').forEach((p) => {
                        p.classList.remove('bg-white', 'text-slate-900', 'shadow-sm', 'ring-1', 'ring-slate-200');
                        p.classList.add('text-slate-500');
                    });
                    pill.classList.add('bg-white', 'text-slate-900', 'shadow-sm', 'ring-1', 'ring-slate-200');
                    pill.classList.remove('text-slate-500');

                    history.replaceState(null, '', pill.href);
                    refreshBoard();
                });

                attachCardListeners(boardContainer);
                tickElapsedLabels();

                setInterval(refreshBoard, 5000);
                setInterval(tickElapsedLabels, 30000);
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') refreshBoard();
                });
            })();
        </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/orders/index.blade.php ENDPATH**/ ?>