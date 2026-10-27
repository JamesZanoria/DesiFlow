@extends('layouts.module')

@section('title', 'Transaction History')
@section('module-name', 'Cashier')
@section('home-url', route('cashier.index'))
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-amber-600">Cash control</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Transaction history</h1>
            <p class="mt-1 text-sm text-slate-500">Payments recorded by your cashier account.</p>
        </div>
        <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm">Your transactions</span>
    </div>

    <form method="GET" action="{{ route('cashier.transactions.index') }}" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
        <label class="text-xs font-bold uppercase tracking-wide text-slate-600">From
            <input type="date" name="date_from" value="{{ $validated['date_from'] ?? '' }}" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal normal-case tracking-normal focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
        </label>
        <label class="text-xs font-bold uppercase tracking-wide text-slate-600">To
            <input type="date" name="date_to" value="{{ $validated['date_to'] ?? '' }}" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal normal-case tracking-normal focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100">
        </label>
        <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Order number
            <input type="search" name="search" value="{{ $validated['search'] ?? '' }}" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal normal-case tracking-normal placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100" placeholder="Search order">
        </label>
        <div class="flex items-end gap-2"><button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">Apply filters</button><a href="{{ route('cashier.transactions.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Clear</a></div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($payments->isEmpty())
            <div class="px-6 py-14 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h18v18H3zM7.5 8.25h9m-9 3.75h9m-9 3.75h5.25" /></svg></span>
                <p class="mt-3 text-sm font-semibold text-slate-700">No transactions found</p>
                <p class="mt-1 text-sm text-slate-500">Try changing the date range or order number.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr><th class="px-5 py-3">Order number</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Total</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($payments as $payment)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $payment->order?->order_number }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $payment->created_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-5 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold uppercase text-slate-700">{{ $payment->payment_method }}</span></td>
                                <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-bold capitalize', 'bg-emerald-50 text-emerald-700' => $payment->transaction_status === 'completed', 'bg-amber-50 text-amber-700' => $payment->transaction_status === 'pending', 'bg-red-50 text-red-700' => in_array($payment->transaction_status, ['voided', 'refunded'], true)])>{{ $payment->transaction_status }}</span></td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-900">₱{{ number_format($payment->order?->total_amount ?? 0, 2) }}</td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('cashier.transactions.show', $payment) }}" class="rounded-lg px-3 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-50 hover:text-amber-800">Details <span aria-hidden="true">→</span></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection
