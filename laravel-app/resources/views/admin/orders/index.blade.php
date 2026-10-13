@extends('layouts.app')

@section('title', 'Real-Time Order Monitoring')

@section('content')

    <div class="flex items-start justify-between mb-4 gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Real-Time Order Monitoring</h1>
            <p class="text-sm text-gray-500 mt-1">Live view of every order currently in progress across the restaurant.</p>
        </div>

        {{-- Status filter (REQ-4.3-3), pills instead of a dropdown so the current
             filter and the total-active-orders count stay visible at a glance. --}}
        <div id="status-filter-pills" class="flex items-center gap-2">
            <a href="{{ route('admin.orders.index') }}"
                data-status=""
                class="filter-pill px-3 py-1.5 rounded-full text-xs font-medium border {{ !$statusFilter ? 'bg-gray-800 text-white border-gray-800' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                All
            </a>
            @foreach (\App\Http\Controllers\Admin\OrderMonitorController::STATUS_META as $status => $meta)
                <a href="{{ route('admin.orders.index', ['status' => $status]) }}"
                    data-status="{{ $status }}"
                    class="filter-pill px-3 py-1.5 rounded-full text-xs font-medium border flex items-center gap-1.5 {{ $statusFilter === $status ? 'bg-gray-800 text-white border-gray-800' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div id="orders-board-container">
        @include('admin.orders._board')
    </div>

    {{-- Quick-View popup --}}
    <div id="order-quickview-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md my-8 max-h-[90vh] overflow-y-auto">
            <div class="bg-indigo-50 rounded-t-xl px-6 py-4 flex items-center justify-between sticky top-0">
                <h2 class="text-lg font-semibold text-gray-800">Order Details</h2>
                <button type="button" id="order-quickview-close" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <div id="order-quickview-body" class="p-6">
                <p class="text-center text-sm text-gray-400 py-8">Loading…</p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const boardContainer = document.getElementById('orders-board-container');
            const modal = document.getElementById('order-quickview-modal');
            const modalBody = document.getElementById('order-quickview-body');
            const baseUrl = '{{ url('admin/orders') }}';

            // ---------------------------------------------------------------
            // Live elapsed-time labels ("3 min ago"), computed client-side
            // from each card's data-created-at so the numbers keep ticking up
            // between polls without needing a server round trip just for that.
            // ---------------------------------------------------------------
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

            // ---------------------------------------------------------------
            // Quick-View modal
            // ---------------------------------------------------------------
            async function openQuickView(orderId) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                modalBody.innerHTML = '<p class="text-center text-sm text-gray-400 py-8">Loading…</p>';

                try {
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
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            document.getElementById('order-quickview-close').addEventListener('click', closeQuickView);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeQuickView(); });

            function attachCardListeners(scope) {
                scope.querySelectorAll('.view-order-btn').forEach((btn) => {
                    btn.addEventListener('click', () => openQuickView(btn.dataset.id));
                });
            }

            // ---------------------------------------------------------------
            // Board polling, stand-in for real-time WebSocket push (REQ-4.3-2)
            // until Laravel Reverb is wired into this module. Refreshes the
            // whole board fragment from the server on an interval, and again
            // whenever the tab regains focus (catches up instantly instead of
            // waiting out the rest of the interval after being backgrounded).
            // ---------------------------------------------------------------
            let currentStatus = '{{ $statusFilter }}';

            async function refreshBoard() {
                const url = currentStatus
                    ? `{{ route('admin.orders.index') }}?status=${encodeURIComponent(currentStatus)}`
                    : `{{ route('admin.orders.index') }}`;

                try {
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const data = await response.json();
                    boardContainer.innerHTML = data.html;
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
                    p.classList.remove('bg-gray-800', 'text-white', 'border-gray-800');
                    p.classList.add('text-gray-600', 'border-gray-300');
                });
                pill.classList.add('bg-gray-800', 'text-white', 'border-gray-800');
                pill.classList.remove('text-gray-600', 'border-gray-300');

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

@endsection