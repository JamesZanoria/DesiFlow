@extends('layouts.module')

@section('title', 'Payment')
@section('module-name', 'Cashier')
@section('home-url', route('cashier.index'))
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-amber-600">Checkout</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Process payment</h1>
            <p class="mt-1 text-sm text-slate-500">Order {{ $order->order_number }} <span class="mx-1 text-slate-300">·</span> {{ $order->order_type === 'takeout' ? 'Takeout' : 'Dine-in · table ' . $order->table_number }}</p>
        </div>
        <a href="{{ route('cashier.menu') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-50">← Return to menu</a>
    </div>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4"><h2 class="font-bold text-slate-900">Order items</h2><p class="mt-0.5 text-xs text-slate-500">Review the items before completing payment.</p></div>
            <ul class="divide-y divide-slate-100 px-5">
                @foreach ($order->orderItems as $item)
                    <li class="py-4">
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="font-medium text-slate-800">{{ $item->quantity }} × {{ $item->item_name_snapshot ?: $item->menuItem?->item_name ?: 'Item' }} <span class="font-normal text-slate-500">(₱{{ number_format($item->unit_price ?? 0, 2) }} each)</span></span>
                            <span class="shrink-0 font-bold text-slate-900">₱{{ number_format($item->total, 2) }}</span>
                        </div>
                        @foreach ($item->addOns as $addOn)
                            <p class="mt-1 pl-4 text-xs text-slate-500">+ {{ $addOn->add_on_name_snapshot }} · ₱{{ number_format($addOn->unit_price, 2) }} each</p>
                        @endforeach
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4"><h2 class="font-bold text-slate-900">Payment</h2><p class="mt-0.5 text-xs text-slate-500">Choose a method to complete this order.</p></div>
            <div class="p-5">
            <p class="rounded-xl bg-slate-900 px-4 py-4 text-sm text-slate-300">Total due <strong class="float-right text-xl font-extrabold text-white">₱{{ number_format($order->total_amount, 2) }}</strong></p>
            <form method="POST" action="{{ route('cashier.orders.payment.store', $order) }}" class="mt-4 space-y-4" id="payment-form">
                @csrf
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-gray-700">Payment method</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50">
                            <input type="radio" name="payment_method" value="cash" @checked(old('payment_method', 'cash') === 'cash') class="text-amber-600 focus:ring-amber-500">
                            Cash
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50">
                            <input type="radio" name="payment_method" value="gcash" @checked(old('payment_method') === 'gcash') class="text-amber-600 focus:ring-amber-500">
                            GCash
                        </label>
                    </div>
                </fieldset>

                <div id="cash-fields" class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700">
                        Cash received
                        <input id="cash-received" type="text" name="cash_received" inputmode="decimal" value="{{ old('cash_received') }}" placeholder="0.00"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                    </label>
                    <p class="flex justify-between rounded-xl bg-emerald-50 px-3 py-3 text-sm text-emerald-800"><span>Change due</span><strong id="change-preview">₱0.00</strong></p>
                </div>

                <div id="gcash-fields" class="hidden">
                    <label class="block text-sm font-medium text-gray-700">
                        GCash reference number
                        <input type="text" name="gcash_ref_num" value="{{ old('gcash_ref_num') }}" minlength="6" maxlength="50" placeholder="Enter manually supplied reference"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                    </label>
                    <p class="mt-2 text-xs text-gray-500">Reference format: 6–50 letters, numbers, or hyphens. This information is recorded as supplied and is not verified with GCash.</p>
                </div>

                <button id="complete-payment" class="w-full rounded-xl bg-amber-500 px-4 py-3.5 text-sm font-bold text-white shadow-md shadow-amber-900/10 transition hover:bg-amber-600 focus:outline-none focus:ring-4 focus:ring-amber-100">Complete payment</button>
            </form>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const form = document.getElementById('payment-form');
            const cashFields = document.getElementById('cash-fields');
            const gcashFields = document.getElementById('gcash-fields');
            const cashReceived = document.getElementById('cash-received');
            const changePreview = document.getElementById('change-preview');
            const totalCents = Math.round(Number(@json((float) $order->total_amount)) * 100);
            const updateMethod = () => {
                const method = form.querySelector('input[name="payment_method"]:checked')?.value || 'cash';
                cashFields.classList.toggle('hidden', method !== 'cash');
                gcashFields.classList.toggle('hidden', method !== 'gcash');
                cashReceived.required = method === 'cash';
                gcashFields.querySelector('input').required = method === 'gcash';
            };
            const updateChange = () => {
                const value = Number(cashReceived.value);
                const receivedCents = Number.isFinite(value) ? Math.round(value * 100) : 0;
                changePreview.textContent = `₱${(Math.max(0, receivedCents - totalCents) / 100).toFixed(2)}`;
            };
            form.querySelectorAll('input[name="payment_method"]').forEach((input) => input.addEventListener('change', updateMethod));
            cashReceived.addEventListener('input', updateChange);
            form.addEventListener('submit', () => {
                const button = document.getElementById('complete-payment');
                button.disabled = true;
                button.textContent = 'Processing payment...';
            });
            updateMethod();
            updateChange();
        })();
    </script>
@endsection
