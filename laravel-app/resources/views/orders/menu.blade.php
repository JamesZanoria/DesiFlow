@extends('layouts.ordering')

@section('title', 'Order Online')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Order from our menu</h1>
            <p class="mt-1 text-sm text-gray-500">Add your favourites to the cart and get a pickup code.</p>
        </div>
        <label class="w-full sm:w-72">
            <span class="sr-only">Search menu</span>
            <input id="menu-search" type="search" placeholder="Search menu..."
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
        </label>
    </div>

    <div id="order-message" class="hidden mb-4 rounded-lg border px-4 py-3 text-sm" role="status"></div>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-label="Menu items">
            <div id="category-filters" class="mb-4 flex flex-wrap gap-2">
                <button type="button" data-category-filter="all" class="rounded-full bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white">All items</button>
                @foreach ($categories as $category)
                    <button type="button" data-category-filter="{{ $category }}" class="rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">{{ $category }}</button>
                @endforeach
            </div>

            @if ($menuItems->isEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
                    No menu items are available right now. Please check again later.
                </div>
            @else
                <div id="menu-grid" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($menuItems as $item)
                        <article data-menu-card data-name="{{ strtolower($item->item_name) }}" data-category="{{ $item->category }}"
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            @if ($item->image_url)
                                <img src="{{ route('customer.menu-image', ['filename' => $item->image_url]) }}"
                                    alt="" class="h-36 w-full bg-gray-100 object-cover" loading="lazy">
                            @else
                                <div class="flex h-36 items-center justify-center bg-amber-50 text-sm font-medium text-amber-800" aria-hidden="true">Menu item</div>
                            @endif
                            <div class="p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $item->category }}</p>
                                <h2 class="mt-1 font-semibold">{{ $item->item_name }}</h2>
                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <span class="font-bold">₱{{ number_format($item->price, 2) }}</span>
                                    <button type="button" data-add-item="{{ $item->menu_item_id }}"
                                        class="rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">
                                        Add to order
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p id="menu-empty" class="hidden rounded-lg bg-white p-6 text-center text-sm text-gray-500">No menu items match your search.</p>
            @endif
        </section>

        <aside class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm xl:sticky xl:top-4" aria-label="Your order">
            <h2 class="text-lg font-bold">Your order</h2>
            <div id="cart-items" class="mt-3 max-h-72 space-y-3 overflow-y-auto border-b border-gray-200 pb-4"></div>
            <p id="cart-empty" class="py-5 text-center text-sm text-gray-500">Your cart is empty.</p>
            <div class="flex justify-between py-4 font-bold">
                <span>Total</span>
                <span id="cart-total">₱0.00</span>
            </div>

            <form id="place-order-form" class="space-y-3">
                <label class="block text-sm font-medium text-gray-700">
                    Order type
                    <select id="order-type" name="order_type" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        <option value="takeout">Takeout / Pickup</option>
                        <option value="dine-in">Dine-in</option>
                    </select>
                </label>
                <label id="table-number-field" class="hidden block text-sm font-medium text-gray-700">
                    Table number
                    <input type="number" id="table-number" name="table_number" min="1" max="999"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                </label>
                <button id="place-order-button" type="submit" disabled
                    class="w-full rounded-lg bg-amber-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:bg-gray-300">
                    Place order
                </button>
                <p class="text-center text-xs text-gray-500">Payment is handled by staff when your order is confirmed.</p>
            </form>
        </aside>
    </div>

    <section id="order-tracking" class="hidden fixed inset-0 z-50 items-center justify-center bg-gray-950/60 p-4" aria-modal="true" role="dialog" aria-labelledby="tracking-title">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="tracking-title" class="text-xl font-bold">Order placed</h2>
                    <p class="mt-1 text-sm text-gray-500">Keep this code to track your order.</p>
                </div>
                <button type="button" id="tracking-close" class="rounded p-1 text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Close order details">&times;</button>
            </div>
            <div class="my-5 rounded-xl bg-gray-950 px-4 py-5 text-center text-white">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Pickup code</p>
                <p id="tracking-code" class="mt-1 font-mono text-4xl font-bold tracking-[0.2em] text-green-400"></p>
            </div>
            <p class="text-sm text-gray-600">Order <span id="tracking-number" class="font-medium"></span></p>
            <p class="mt-1 text-sm text-gray-600">Status: <span id="tracking-status" class="font-semibold text-amber-700">Waiting for confirmation</span></p>
            <div id="tracking-items" class="mt-4 space-y-1 border-t border-gray-200 pt-3 text-sm text-gray-600"></div>
            <button type="button" id="tracking-done" class="mt-5 w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">Continue browsing</button>
        </div>
    </section>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const cartContainer = document.getElementById('cart-items');
            const cartEmpty = document.getElementById('cart-empty');
            const cartTotal = document.getElementById('cart-total');
            const submitButton = document.getElementById('place-order-button');
            const statusMessage = document.getElementById('order-message');
            const trackingDialog = document.getElementById('order-tracking');
            const trackingItems = document.getElementById('tracking-items');
            let cart = {};
            let currentOrder = null;
            let trackingInterval = null;
            let activeCategory = 'all';

            function showMessage(message, kind = 'error') {
                statusMessage.textContent = message;
                statusMessage.className = `mb-4 rounded-lg border px-4 py-3 text-sm ${kind === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'}`;
                statusMessage.classList.remove('hidden');
            }

            async function request(url, options = {}) {
                const response = await fetch(url, {
                    ...options,
                    headers: { 'Accept': 'application/json', ...(options.headers || {}) },
                });
                const data = await response.json();
                if (!response.ok) {
                    const message = data.message || Object.values(data.errors || {}).flat()[0] || 'The request could not be completed.';
                    throw new Error(message);
                }
                return data;
            }

            function money(value) {
                return `₱${Number(value).toFixed(2)}`;
            }

            function button(label, title, onClick) {
                const el = document.createElement('button');
                el.type = 'button';
                el.className = 'h-8 w-8 rounded border border-gray-300 text-gray-700 hover:bg-gray-100';
                el.textContent = label;
                el.setAttribute('aria-label', title);
                el.addEventListener('click', onClick);
                return el;
            }

            function renderCart() {
                cartContainer.replaceChildren();
                let total = 0;
                for (const [id, item] of Object.entries(cart)) {
                    const row = document.createElement('div');
                    row.className = 'flex items-start justify-between gap-3';
                    const details = document.createElement('div');
                    details.className = 'min-w-0';
                    const name = document.createElement('p');
                    name.className = 'truncate text-sm font-medium';
                    name.textContent = item.name;
                    const price = document.createElement('p');
                    price.className = 'mt-0.5 text-xs text-gray-500';
                    price.textContent = `${item.quantity} × ${money(item.price)} = ${money(item.quantity * item.price)}`;
                    details.append(name, price);

                    const actions = document.createElement('div');
                    actions.className = 'flex shrink-0 items-center gap-1';
                    actions.append(
                        button('−', `Remove one ${item.name}`, () => changeQuantity(id, 'minus')),
                        button('+', `Add one ${item.name}`, () => changeQuantity(id, 'plus')),
                    );
                    row.append(details, actions);
                    cartContainer.append(row);
                    total += Number(item.price) * Number(item.quantity);
                }
                cartEmpty.classList.toggle('hidden', Object.keys(cart).length > 0);
                cartTotal.textContent = money(total);
                submitButton.disabled = Object.keys(cart).length === 0;
            }

            async function refreshCart() {
                const data = await request('{{ route('cart.get') }}');
                cart = data.cart || {};
                renderCart();
            }

            async function changeQuantity(id, action) {
                try {
                    const data = await request('{{ route('cart.update') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ product_id: id, action }),
                    });
                    cart = data.cart || {};
                    renderCart();
                } catch (error) {
                    showMessage(error.message);
                }
            }

            document.querySelectorAll('[data-add-item]').forEach((buttonEl) => {
                buttonEl.addEventListener('click', async () => {
                    try {
                        const data = await request('{{ route('cart.add') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ product_id: buttonEl.dataset.addItem }),
                        });
                        cart = data.cart || {};
                        renderCart();
                        statusMessage.classList.add('hidden');
                    } catch (error) {
                        showMessage(error.message);
                    }
                });
            });

            document.getElementById('menu-search').addEventListener('input', (event) => {
                const query = event.target.value.trim().toLowerCase();
                let shown = 0;
                document.querySelectorAll('[data-menu-card]').forEach((card) => {
                    const visible = card.dataset.name.includes(query)
                        && (activeCategory === 'all' || card.dataset.category === activeCategory);
                    card.classList.toggle('hidden', !visible);
                    if (visible) shown++;
                });
                document.getElementById('menu-empty')?.classList.toggle('hidden', shown > 0);
            });

            document.querySelectorAll('[data-category-filter]').forEach((categoryButton) => {
                categoryButton.addEventListener('click', () => {
                    activeCategory = categoryButton.dataset.categoryFilter;
                    document.querySelectorAll('[data-category-filter]').forEach((buttonEl) => {
                        buttonEl.className = buttonEl === categoryButton
                            ? 'rounded-full bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white'
                            : 'rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100';
                    });
                    document.getElementById('menu-search').dispatchEvent(new Event('input'));
                });
            });

            document.getElementById('order-type').addEventListener('change', (event) => {
                const dineIn = event.target.value === 'dine-in';
                document.getElementById('table-number-field').classList.toggle('hidden', !dineIn);
                document.getElementById('table-number').required = dineIn;
            });

            document.getElementById('place-order-form').addEventListener('submit', async (event) => {
                event.preventDefault();
                if (Object.keys(cart).length === 0) return;
                submitButton.disabled = true;
                submitButton.textContent = 'Placing order...';
                try {
                    const data = await request('{{ route('orders.submit') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({
                            order_type: document.getElementById('order-type').value,
                            table_number: document.getElementById('table-number').value || null,
                        }),
                    });
                    cart = {};
                    renderCart();
                    statusMessage.classList.add('hidden');
                    openTracking({
                        order_number: data.order_number,
                        order_id: data.order_id,
                        code: data.code,
                        status: data.status,
                    });
                    startTracking();
                } catch (error) {
                    showMessage(error.message);
                } finally {
                    submitButton.textContent = 'Place order';
                    renderCart();
                }
            });

            function openTracking(order) {
                currentOrder = order;
                saveTrackedOrder();
                document.getElementById('tracking-code').textContent = order.code;
                document.getElementById('tracking-number').textContent = order.order_number;
                trackingDialog.classList.remove('hidden');
                trackingDialog.classList.add('flex');
                loadOrderDetails();
            }

            function saveTrackedOrder() {
                try {
                    if (currentOrder) {
                        localStorage.setItem('desiflow_last_order', JSON.stringify(currentOrder));
                    } else {
                        localStorage.removeItem('desiflow_last_order');
                    }
                } catch {
                    showMessage('Order tracking storage is unavailable in this browser. Keep this page open and save your pickup code.');
                }
            }

            async function loadOrderDetails() {
                if (!currentOrder) return;
                try {
                    const data = await request('{{ route('orders.details') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({
                            order_number: currentOrder.order_number,
                            code: currentOrder.code,
                        }),
                    });
                    trackingItems.replaceChildren();
                    data.order.items.forEach((item) => {
                        const line = document.createElement('p');
                        line.textContent = `${item.quantity} × ${item.name}`;
                        trackingItems.append(line);
                    });
                } catch (error) {
                    showMessage(error.message);
                }
            }

            async function refreshOrderStatus() {
                if (!currentOrder) return;
                try {
                    const url = new URL('{{ url('/api/orders') }}/' + encodeURIComponent(currentOrder.order_number) + '/status');
                    url.searchParams.set('code', currentOrder.code);
                    const data = await request(url.toString());
                    currentOrder.status = data.order.status;
                    document.getElementById('tracking-status').textContent = {
                        unapproved: 'Waiting for confirmation',
                        pending: 'Accepted — waiting for the kitchen',
                        preparing: 'Being prepared',
                        ready: 'Ready',
                        picked_up: 'Picked up',
                        dropped: 'Completed',
                        rejected: 'Order declined',
                    }[data.order.status] || data.order.status;
                    saveTrackedOrder();
                    if (['picked_up', 'dropped', 'rejected'].includes(data.order.status)) {
                        currentOrder = null;
                        saveTrackedOrder();
                    }
                } catch (error) {
                    showMessage(error.message);
                }
            }

            function startTracking() {
                if (trackingInterval) clearInterval(trackingInterval);
                refreshOrderStatus();
                trackingInterval = setInterval(() => {
                    if (!currentOrder) {
                        clearInterval(trackingInterval);
                        trackingInterval = null;
                        return;
                    }
                    refreshOrderStatus();
                }, 4000);
            }

            function closeTracking() {
                trackingDialog.classList.add('hidden');
                trackingDialog.classList.remove('flex');
            }

            document.getElementById('tracking-close').addEventListener('click', closeTracking);
            document.getElementById('tracking-done').addEventListener('click', closeTracking);
            document.getElementById('order-type').dispatchEvent(new Event('change'));

            refreshCart().catch((error) => showMessage(error.message));
            try {
                const savedOrder = JSON.parse(localStorage.getItem('desiflow_last_order') || 'null');
                if (savedOrder?.order_number && /^[0-9]{6}$/.test(savedOrder.code || '')) {
                    openTracking(savedOrder);
                    startTracking();
                }
            } catch {
                showMessage('The saved order could not be restored. Enter your order number and pickup code at the restaurant display.');
            }
        })();
    </script>
@endsection
