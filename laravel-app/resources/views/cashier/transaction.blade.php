@extends('layouts.module')

@section('title', 'Transaction Details')
@section('module-name', 'Cashier')
@section('home-url', route('cashier.index'))
@section('content')
    @php($order = $payment->order)
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div><p class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-amber-600">Cash control</p><h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Transaction details</h1><p class="mt-1 text-sm text-slate-500">{{ $order->order_number }} · {{ $payment->created_at?->format('M j, Y g:i A') }}</p></div>
        <a href="{{ route('cashier.transactions.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-50">← Back to history</a>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex justify-between gap-4 border-b border-slate-100 bg-slate-50/80 px-5 py-4"><h2 class="font-bold text-slate-900">Order items</h2><a class="text-sm font-bold text-amber-700 hover:text-amber-800" href="{{ route('cashier.receipts.show', $order) }}">View receipt →</a></div>
            <ul class="divide-y divide-slate-100 px-5">
                @foreach ($order->orderItems as $item)
                    <li class="py-3">
                        <p class="flex justify-between gap-3 text-sm"><span>{{ $item->quantity }} × {{ $item->item_name_snapshot ?: $item->menuItem?->item_name ?: 'Item' }}</span><span>₱{{ number_format($item->total, 2) }}</span></p>
                        @foreach ($item->addOns as $addOn)<p class="mt-1 pl-4 text-xs text-gray-500">+ {{ $addOn->add_on_name_snapshot }} · ₱{{ number_format($addOn->unit_price, 2) }} each</p>@endforeach
                    </li>
                @endforeach
            </ul>
            <div class="mx-5 mb-5 mt-4 space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                <p class="flex justify-between"><span class="text-slate-500">Total</span><strong class="text-lg text-slate-900">₱{{ number_format($order->total_amount, 2) }}</strong></p>
                <p class="flex justify-between"><span class="text-slate-500">Method</span><strong class="uppercase text-slate-800">{{ $payment->payment_method }}</strong></p>
                <p class="flex justify-between"><span class="text-slate-500">Transaction status</span><strong class="capitalize text-slate-800">{{ $payment->transaction_status }}</strong></p>
                @if ($payment->payment_method === 'cash')
                    <p class="text-slate-600">Cash received: ₱{{ number_format($payment->amount_tethered, 2) }} · Change: ₱{{ number_format($payment->change_due, 2) }}</p>
                @else
                    <p class="text-slate-600">GCash reference: {{ $payment->gcash_ref_num }} <span class="text-xs text-slate-500">(manually supplied; not verified)</span></p>
                @endif
            </div>
        </section>

        <section class="space-y-5">
            @if ($payment->transaction_status === 'completed')
                <form method="POST" action="{{ route('cashier.transactions.requests.store', $payment) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    @csrf
                    <h2 class="font-semibold">Request void or refund</h2>
                    <p class="mt-1 text-sm text-gray-500">An administrator must review the request. The transaction remains unchanged until approved.</p>
                    <label class="mt-4 block text-sm font-medium text-gray-700">Request type
                        <select name="request_type" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
                            <option value="void">Void</option>
                            <option value="refund">Refund</option>
                        </select>
                    </label>
                    <label class="mt-3 block text-sm font-medium text-gray-700">Reason
                        <textarea name="reason" required minlength="5" maxlength="500" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100" placeholder="Explain why this action is needed"></textarea>
                    </label>
                    <button class="mt-3 rounded-lg border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50">Submit for admin review</button>
                </form>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-900">Financial action audit trail</h2>
                @forelse ($payment->transactionRequests as $transactionRequest)
                    <article class="mt-3 border-t border-gray-100 pt-3 text-sm">
                        <p><strong class="capitalize">{{ $transactionRequest->request_type }}</strong> request · <span class="capitalize">{{ $transactionRequest->status }}</span></p>
                        <p class="mt-1 text-gray-600">Requested by {{ $transactionRequest->requester?->name }} on {{ $transactionRequest->requested_at?->format('M j, Y g:i A') }}</p>
                        <p class="mt-1 text-gray-600">{{ $transactionRequest->reason }}</p>
                        @if ($transactionRequest->reviewed_at)
                            <p class="mt-1 text-gray-600">Reviewed by {{ $transactionRequest->reviewer?->name }} on {{ $transactionRequest->reviewed_at->format('M j, Y g:i A') }}@if($transactionRequest->decision_reason) · {{ $transactionRequest->decision_reason }}@endif</p>
                        @endif
                    </article>
                @empty
                    <p class="mt-2 text-sm text-gray-500">No void or refund requests.</p>
                @endforelse
            </section>
        </section>
    </div>
@endsection
