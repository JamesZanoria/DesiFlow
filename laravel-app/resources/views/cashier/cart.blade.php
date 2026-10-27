@extends('layouts.module')

@section('title', 'Order Summary')
@section('module-name', 'Cashier')
@section('home-url', route('cashier.index'))
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-amber-600">Current sale</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Order summary</h1>
            <p class="mt-1 text-sm text-slate-500">Review items and quantities before payment.</p>
        </div>
        <a href="{{ route('cashier.menu') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-50">← Return to menu</a>
    </div>

    @if ($cartError)
        <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $cartError }}</div>
    @endif

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @if (count($cart) === 0)
                <p class="py-8 text-center text-sm text-gray-500">Your cart is empty.</p>
                <div class="text-center"><a href="{{ route('cashier.menu') }}" class="font-semibold text-amber-700 hover:text-amber-900">Choose menu items</a></div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($cart as $item)
                        <article class="flex flex-wrap items-start justify-between gap-4 py-4 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <h2 class="font-semibold">{{ $item['name'] }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $item['quantity'] }} × ₱{{ number_format($item['price'], 2) }}</p>
                                @foreach ($item['addOns'] as $addOn)
                                    <p class="mt-1 pl-3 text-sm text-gray-500">+ {{ $addOn['name'] }} (₱{{ number_format($addOn['price'], 2) }} each)</p>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-semibold">₱{{ number_format($item['lineTotal'], 2) }}</span>
                                <div class="flex items-center gap-1">
                                    @foreach (['minus' => '−', 'plus' => '+'] as $action => $label)
                                        <form method="POST" action="{{ route('cashier.cart.update') }}">
                                            @csrf
                                            <input type="hidden" name="cart_key" value="{{ $item['key'] }}">
                                            <input type="hidden" name="action" value="{{ $action }}">
                                            <button class="h-8 w-8 rounded border border-gray-300 text-gray-700 hover:bg-gray-100" aria-label="{{ $action === 'plus' ? 'Add one' : 'Remove one' }} {{ $item['name'] }}">{{ $label }}</button>
                                        </form>
                                    @endforeach
                                    <form method="POST" action="{{ route('cashier.cart.update') }}">
                                        @csrf
                                        <input type="hidden" name="cart_key" value="{{ $item['key'] }}">
                                        <input type="hidden" name="action" value="remove">
                                        <button class="ml-1 rounded px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Order details</h2>
            <p class="mt-4 flex justify-between border-b border-gray-100 pb-4 text-sm"><span>Items</span><span>{{ $cartItemCount }}</span></p>
            <p class="flex justify-between py-4 text-lg font-bold"><span>Total</span><span>₱{{ number_format($cartTotal, 2) }}</span></p>
            @if (count($cart) > 0)
                <form method="POST" action="{{ route('cashier.orders.store') }}" class="space-y-3">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700">
                        Order type
                        <select id="order-type" name="order_type" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                            <option value="takeout" @selected(old('order_type', 'takeout') === 'takeout')>Takeout</option>
                            <option value="dine-in" @selected(old('order_type') === 'dine-in')>Dine-in</option>
                        </select>
                    </label>
                    <label id="table-number-field" class="block text-sm font-medium text-gray-700">
                        Table number
                        <input type="number" id="table-number" name="table_number" value="{{ old('table_number') }}" min="1" max="999"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                    </label>
                    <button class="w-full rounded-xl bg-amber-500 px-4 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600 focus:outline-none focus:ring-4 focus:ring-amber-100">Proceed to payment</button>
                </form>
            @endif
        </aside>
    </div>

    <script>
        (() => {
            const orderType = document.getElementById('order-type');
            const tableField = document.getElementById('table-number-field');
            const tableNumber = document.getElementById('table-number');
            if (!orderType || !tableField || !tableNumber) return;
            const update = () => {
                const dineIn = orderType.value === 'dine-in';
                tableField.classList.toggle('hidden', !dineIn);
                tableNumber.required = dineIn;
            };
            orderType.addEventListener('change', update);
            update();
        })();
    </script>
@endsection
