@extends('layouts.module')

@section('title', 'New Order')
@section('module-name', 'Cashier')
@section('home-url', route('cashier.index'))
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-amber-600">Point of sale</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Create an order</h1>
            <p class="mt-1 text-sm text-slate-500">Browse the menu, customize items, and build your order.</p>
        </div>
        <label class="relative w-full sm:w-80">
            <span class="sr-only">Search menu</span>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m16 16 4 4" /></svg>
            <input id="menu-search" type="search" value="{{ $search }}" placeholder="Search menu..."
                class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
        </label>
    </div>

    <div id="cashier-order-message" class="mb-4 hidden rounded-xl border px-4 py-3 text-sm" role="status"></div>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-label="Menu items">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-sm font-bold text-slate-800">Explore the menu</h2>
                <span class="text-xs font-medium text-slate-400">{{ $menuItems->count() }} available</span>
            </div>
            <div id="category-filters" class="mb-5 flex flex-wrap gap-2">
                <button type="button" data-category-filter="all" class="rounded-full bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-sm transition">All items</button>
                @foreach ($categories as $category)
                    <button type="button" data-category-filter="{{ $category }}" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-800">{{ $category }}</button>
                @endforeach
            </div>

            @if ($menuItems->isEmpty())
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
                    No menu items are available right now. Please check again later.
                </div>
            @else
                <div id="menu-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($menuItems as $item)
                        <article data-menu-card data-name="{{ strtolower($item->item_name) }}" data-category="{{ $item->category }}"
                            class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-lg">
                            @if ($item->image_url)
                                <img src="{{ route('files.show', ['type' => 'menu-items', 'filename' => $item->image_url]) }}"
                                    alt="" class="h-44 w-full bg-slate-100 object-cover transition duration-300 group-hover:scale-[1.03]" loading="lazy">
                            @else
                                <div class="flex h-44 items-center justify-center bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100 text-sm font-semibold text-amber-800" aria-hidden="true">
                                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/80 text-xl font-black text-amber-600 shadow-sm">DZ</span>
                                </div>
                            @endif
                            <div class="relative bg-white p-4">
                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700">{{ $item->category }}</span>
                                <h2 class="mt-2 font-bold text-slate-900">{{ $item->item_name }}</h2>
                                @if ($item->description)
                                    <p class="mt-1 line-clamp-2 min-h-10 text-sm leading-5 text-slate-500">{{ $item->description }}</p>
                                @else
                                    <p class="mt-1 min-h-10 text-sm leading-5 text-slate-400">Made fresh to order.</p>
                                @endif
                                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                    <span class="text-lg font-extrabold tracking-tight text-slate-900">₱{{ number_format($item->price, 2) }}</span>
                                    <button type="button" data-open-item
                                        data-item-id="{{ $item->menu_item_id }}"
                                        data-item-name="{{ $item->item_name }}"
                                        data-item-category="{{ $item->category }}"
                                        data-item-description="{{ $item->description }}"
                                        data-item-price="{{ $item->price }}"
                                        data-item-image="{{ $item->image_url ? route('files.show', ['type' => 'menu-items', 'filename' => $item->image_url]) : '' }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-amber-600 focus:outline-none focus:ring-4 focus:ring-amber-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" /></svg>
                                        Add
                                    </button>
                                </div>
                                <div class="hidden" data-addon-options>
                                    @foreach ($item->addOns as $addOn)
                                        <span data-addon-option data-id="{{ $addOn->add_on_id }}" data-name="{{ $addOn->add_on_name }}" data-price="{{ $addOn->price }}"></span>
                                    @endforeach
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p id="menu-empty" class="hidden rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">No menu items match your search.</p>
            @endif
        </section>

        <aside class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-900/5 xl:sticky xl:top-24" aria-label="Current cashier order">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-600">Order summary</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Current order</h2>
                </div>
                <span id="cart-count" class="flex h-9 min-w-9 items-center justify-center rounded-xl bg-slate-900 px-2 text-sm font-bold text-white">{{ $cartItemCount }}</span>
            </div>
            <div class="p-5">
                <p id="cart-error" class="mb-3 hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert"></p>
                <div id="cart-items" class="max-h-72 space-y-4 overflow-y-auto divide-y divide-slate-100"></div>
                <div id="cart-empty" class="py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.4 12.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 8H6" /><circle cx="10" cy="20" r="1" /><circle cx="18" cy="20" r="1" /></svg></span>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Your order is empty</p>
                    <p class="mt-1 text-xs text-slate-500">Add menu items to get started.</p>
                </div>
                <div class="mt-1 flex justify-between border-t border-slate-200 py-4 text-sm">
                    <span class="font-medium text-slate-500">Subtotal</span>
                    <span id="cart-total" class="text-xl font-extrabold text-slate-900">₱{{ number_format($cartTotal, 2) }}</span>
                </div>

            <form id="place-order-form" method="POST" action="{{ route('cashier.orders.store') }}" class="space-y-3">
                @csrf
                <label class="block text-sm font-medium text-gray-700">
                    Order type
                    <select id="order-type" name="order_type" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                        <option value="takeout" @selected(old('order_type', 'takeout') === 'takeout')>Takeout</option>
                        <option value="dine-in" @selected(old('order_type') === 'dine-in')>Dine-in</option>
                    </select>
                </label>
                <label id="table-number-field" class="hidden block text-sm font-medium text-gray-700">
                    Table number
                    <input type="number" id="table-number" name="table_number" value="{{ old('table_number') }}" min="1" max="999"
                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                </label>
                <button id="place-order-button" type="submit" @disabled(count($cart) === 0)
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-3.5 text-sm font-bold text-white shadow-md shadow-amber-900/10 transition hover:-translate-y-0.5 hover:bg-amber-600 focus:outline-none focus:ring-4 focus:ring-amber-100 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                    Proceed to payment <span aria-hidden="true">→</span>
                </button>
            </form>
            </div>
        </aside>
    </div>

    <section id="item-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="item-modal-title">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="relative">
                <img id="item-modal-image" alt="" class="hidden h-52 w-full bg-gray-100 object-cover">
                <div id="item-modal-image-empty" class="flex h-36 items-center justify-center bg-amber-50 text-sm font-medium text-amber-800">Menu item</div>
                <button type="button" data-close-item class="absolute right-3 top-3 rounded-full bg-white/90 px-2 py-1 text-xl leading-none text-gray-600 hover:text-gray-900" aria-label="Close item details">&times;</button>
            </div>
            <form id="add-item-form" method="POST" action="{{ route('cashier.cart.add') }}" class="p-5">
                @csrf
                <input type="hidden" name="product_id" id="item-product-id">
                <input type="hidden" name="quantity" id="item-quantity" value="1">
                <p id="item-modal-category" class="text-xs font-semibold uppercase tracking-wide text-gray-500"></p>
                <div class="mt-1 flex items-start justify-between gap-4">
                    <h2 id="item-modal-title" class="text-xl font-bold"></h2>
                    <span id="item-modal-price" class="shrink-0 font-bold"></span>
                </div>
                <p id="item-modal-description" class="mt-2 text-sm text-gray-600"></p>
                <fieldset class="mt-4">
                    <legend class="mb-2 text-sm font-semibold">Available add-ons</legend>
                    <div id="item-modal-addons" class="space-y-2"></div>
                    <p id="no-addons-message" class="hidden text-sm text-gray-500">No add-ons for this item.</p>
                </fieldset>
                <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3" aria-label="Quantity">
                        <button type="button" id="item-quantity-minus" class="h-9 w-9 rounded-lg border border-gray-300 text-lg hover:bg-gray-50" aria-label="Decrease quantity">−</button>
                        <span id="item-quantity-display" class="min-w-6 text-center font-semibold">1</span>
                        <button type="button" id="item-quantity-plus" class="h-9 w-9 rounded-lg border border-gray-300 text-lg hover:bg-gray-50" aria-label="Increase quantity">+</button>
                    </div>
                    <button id="add-item-button" class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">Add to order</button>
                </div>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const modal = document.getElementById('item-modal');
            const addOnList = document.getElementById('item-modal-addons');
            const quantityInput = document.getElementById('item-quantity');
            const quantityDisplay = document.getElementById('item-quantity-display');
            const cartContainer = document.getElementById('cart-items');
            const cartEmpty = document.getElementById('cart-empty');
            const cartTotal = document.getElementById('cart-total');
            const cartCount = document.getElementById('cart-count');
            const placeOrderButton = document.getElementById('place-order-button');
            const cartError = document.getElementById('cart-error');
            const orderMessage = document.getElementById('cashier-order-message');
            let cart = @json($cart);
            let activeCategory = 'all';

            const money = (value) => `₱${Number(value).toFixed(2)}`;
            const showError = (message) => {
                orderMessage.textContent = message;
                orderMessage.className = 'mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800';
            };

            async function request(url, options = {}) {
                const response = await fetch(url, {
                    ...options,
                    headers: { 'Accept': 'application/json', ...(options.headers || {}) },
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'The request could not be completed.');
                }
                return data;
            }

            function quantityButton(label, accessibleName, onClick) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'h-8 w-8 rounded border border-gray-300 text-gray-700 hover:bg-gray-100';
                button.textContent = label;
                button.setAttribute('aria-label', accessibleName);
                button.addEventListener('click', onClick);
                return button;
            }

            function renderCart() {
                cartContainer.replaceChildren();
                const lines = Object.entries(cart);

                lines.forEach(([key, item]) => {
                    const row = document.createElement('div');
                    row.className = 'flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0';
                    const details = document.createElement('div');
                    details.className = 'min-w-0';
                    const name = document.createElement('p');
                    name.className = 'truncate text-sm font-medium';
                    name.textContent = item.name;
                    const price = document.createElement('p');
                    price.className = 'mt-0.5 text-xs text-slate-500';
                    price.textContent = `${item.quantity} × ${money(item.price)} = ${money(item.lineTotal)}`;
                    details.append(name, price);
                    item.addOns.forEach((addOn) => {
                        const addOnLine = document.createElement('p');
                        addOnLine.className = 'mt-1 text-xs text-slate-500';
                        addOnLine.textContent = `+ ${addOn.name} (${money(addOn.price)} each)`;
                        details.append(addOnLine);
                    });

                    const actions = document.createElement('div');
                    actions.className = 'flex shrink-0 items-center gap-1';
                    actions.append(
                        quantityButton('−', `Remove one ${item.name}`, () => updateCart(key, 'minus')),
                        quantityButton('+', `Add one ${item.name}`, () => updateCart(key, 'plus')),
                    );
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'ml-1 rounded px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50';
                    remove.textContent = 'Remove';
                    remove.addEventListener('click', () => updateCart(key, 'remove'));
                    actions.append(remove);
                    row.append(details, actions);
                    cartContainer.append(row);
                });

                cartEmpty.classList.toggle('hidden', lines.length > 0);
                cartCount.textContent = String(Object.values(cart).reduce((sum, item) => sum + Number(item.quantity), 0));
                cartTotal.textContent = money(Object.values(cart).reduce((sum, item) => sum + Number(item.lineTotal), 0));
                placeOrderButton.disabled = lines.length === 0;
            }

            async function updateCart(key, action) {
                cartError.classList.add('hidden');
                try {
                    const data = await request('{{ route('cashier.cart.update') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ cart_key: key, action }),
                    });
                    cart = data.cart || {};
                    cartError.textContent = data.cartError || '';
                    cartError.classList.toggle('hidden', !data.cartError);
                    renderCart();
                } catch (error) {
                    showError(error.message);
                }
            }

            const setQuantity = (quantity) => {
                const value = Math.max(1, Math.min(99, quantity));
                quantityInput.value = String(value);
                quantityDisplay.textContent = String(value);
            };

            document.querySelectorAll('[data-open-item]').forEach((button) => button.addEventListener('click', () => {
                const card = button.closest('[data-menu-card]');
                document.getElementById('item-product-id').value = button.dataset.itemId;
                document.getElementById('item-modal-title').textContent = button.dataset.itemName;
                document.getElementById('item-modal-category').textContent = button.dataset.itemCategory;
                document.getElementById('item-modal-description').textContent = button.dataset.itemDescription || '';
                document.getElementById('item-modal-price').textContent = money(button.dataset.itemPrice);
                const image = document.getElementById('item-modal-image');
                const emptyImage = document.getElementById('item-modal-image-empty');
                if (button.dataset.itemImage) {
                    image.src = button.dataset.itemImage;
                    image.classList.remove('hidden');
                    emptyImage.classList.add('hidden');
                } else {
                    image.removeAttribute('src');
                    image.classList.add('hidden');
                    emptyImage.classList.remove('hidden');
                }

                addOnList.replaceChildren();
                const options = card.querySelectorAll('[data-addon-option]');
                document.getElementById('no-addons-message').classList.toggle('hidden', options.length > 0);
                options.forEach((option) => {
                    const label = document.createElement('label');
                    label.className = 'flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm';
                    const name = document.createElement('span');
                    name.textContent = option.dataset.name;
                    const amount = document.createElement('span');
                    amount.className = 'flex items-center gap-2 text-gray-600';
                    amount.textContent = `+ ${money(option.dataset.price)}`;
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'addon_ids[]';
                    checkbox.value = option.dataset.id;
                    checkbox.className = 'h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500';
                    amount.prepend(checkbox);
                    label.append(name, amount);
                    addOnList.append(label);
                });

                setQuantity(1);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }));

            document.getElementById('item-quantity-minus').addEventListener('click', () => setQuantity(Number(quantityInput.value) - 1));
            document.getElementById('item-quantity-plus').addEventListener('click', () => setQuantity(Number(quantityInput.value) + 1));
            document.querySelectorAll('[data-close-item]').forEach((button) => button.addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });

            document.getElementById('add-item-form').addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = document.getElementById('add-item-button');
                button.disabled = true;
                button.textContent = 'Adding...';
                try {
                    const formData = new FormData(event.currentTarget);
                    const data = await request(event.currentTarget.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf },
                        body: formData,
                    });
                    cart = data.cart || {};
                    cartError.textContent = data.cartError || '';
                    cartError.classList.toggle('hidden', !data.cartError);
                    renderCart();
                    orderMessage.textContent = 'Item added to the current order.';
                    orderMessage.className = 'mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800';
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                } catch (error) {
                    showError(error.message);
                } finally {
                    button.disabled = false;
                    button.textContent = 'Add to order';
                }
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

            document.querySelectorAll('[data-category-filter]').forEach((button) => button.addEventListener('click', () => {
                activeCategory = button.dataset.categoryFilter;
                document.querySelectorAll('[data-category-filter]').forEach((categoryButton) => {
                    categoryButton.className = categoryButton === button
                    ? 'rounded-full bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-sm transition'
                    : 'rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-800';
                });
                document.getElementById('menu-search').dispatchEvent(new Event('input'));
            }));

            document.getElementById('order-type').addEventListener('change', (event) => {
                const dineIn = event.target.value === 'dine-in';
                document.getElementById('table-number-field').classList.toggle('hidden', !dineIn);
                document.getElementById('table-number').required = dineIn;
            });

            document.getElementById('order-type').dispatchEvent(new Event('change'));
            renderCart();
        })();
    </script>
@endsection
