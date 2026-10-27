@extends('layouts.module')

@section('title', 'Receipt')
@section('module-name', 'Cashier')
@section('home-url', route('cashier.index'))
@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 flex flex-wrap justify-between gap-3 print:hidden">
            <a href="{{ route('cashier.menu') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Start next order</a>
            <button type="button" onclick="window.print()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Print receipt</button>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm print:border-0 print:shadow-none">
            <header class="border-b border-dashed border-gray-300 pb-5 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-sm font-black text-white shadow-md shadow-amber-900/15" aria-label="Desi Zaika logo">DZ</div>
                <h1 class="mt-2 text-xl font-bold text-slate-900">Desi Zaika</h1>
                <p class="text-sm text-slate-500">Digital receipt</p>
                <p class="mt-4 text-sm"><span class="text-gray-500">Order</span> <strong>{{ $order->order_number }}</strong></p>
                <p class="mt-1 text-sm text-gray-500">{{ $order->payment->created_at?->format('M j, Y g:i:s A') }}</p>
            </header>

            <section class="py-5">
                <h2 class="sr-only">Purchased items</h2>
                <div class="space-y-4">
                    @foreach ($order->orderItems as $item)
                        <div>
                            <div class="flex justify-between gap-3 text-sm">
                                <span>{{ $item->quantity }} × {{ $item->item_name_snapshot ?: $item->menuItem?->item_name ?: 'Item' }} @if($item->unit_price !== null)<span class="text-gray-500">₱{{ number_format($item->unit_price, 2) }} each</span>@endif</span>
                                <span class="font-medium">₱{{ number_format($item->total, 2) }}</span>
                            </div>
                            @foreach ($item->addOns as $addOn)
                                <p class="mt-1 pl-4 text-xs text-gray-500">+ {{ $addOn->add_on_name_snapshot }} × {{ $item->quantity }} · ₱{{ number_format($addOn->total, 2) }}</p>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="space-y-2 border-t border-dashed border-gray-300 pt-4 text-sm">
                <p class="flex justify-between text-base font-bold"><span>Total paid</span><span>₱{{ number_format($order->total_amount, 2) }}</span></p>
                <p class="flex justify-between"><span>Payment method</span><span class="font-medium uppercase">{{ $order->payment->payment_method }}</span></p>
                @if ($order->payment->payment_method === 'cash')
                    <p class="flex justify-between"><span>Cash received</span><span>₱{{ number_format($order->payment->amount_tethered, 2) }}</span></p>
                    <p class="flex justify-between"><span>Change</span><span>₱{{ number_format($order->payment->change_due, 2) }}</span></p>
                @else
                    <p class="flex justify-between gap-4"><span>GCash reference</span><span class="break-all text-right">{{ $order->payment->gcash_ref_num }}</span></p>
                    <p class="text-xs text-gray-500">Reference recorded as manually supplied; not verified with GCash.</p>
                @endif
                <p class="pt-3 text-center text-xs text-gray-500">Thank you for dining with us.</p>
            </section>
        </article>
    </div>

    <style>
        @media print {
            body { background: white !important; }
            body * { visibility: hidden; }
            article, article * { visibility: visible; }
            article { position: absolute; inset: 0; width: 100%; margin: 0; padding: 12mm; }
        }
    </style>
@endsection
