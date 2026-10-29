@extends('layouts.app')

@section('title', 'Transaction Requests')
@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-slate-900">Void and refund requests</h1>
            <p class="mt-1 text-sm text-slate-500">Review requests without deleting or rewriting the original payment record.</p>
        </div>

        <div class="space-y-4">
            @forelse ($requests as $requestItem)
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ $requestItem->payment?->order?->order_number }} · <span class="capitalize">{{ $requestItem->request_type }}</span></p>
                            <p class="mt-1 text-sm text-slate-500">₱{{ number_format($requestItem->payment?->order?->total_amount ?? 0, 2) }} · Requested by {{ $requestItem->requester?->name }} on {{ $requestItem->requested_at?->format('M j, Y g:i A') }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize">{{ $requestItem->status }}</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-700">{{ $requestItem->reason }}</p>
                    @if ($requestItem->status === 'pending')
                        <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-2">
                            <form method="POST" action="{{ route('admin.transaction-requests.review', $requestItem) }}" class="flex flex-col gap-2 sm:flex-row">
                                @csrf
                                <input type="hidden" name="decision" value="approve">
                                <input name="decision_reason" maxlength="500" placeholder="Optional approval note" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <button class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.transaction-requests.review', $requestItem) }}" class="flex flex-col gap-2 sm:flex-row">
                                @csrf
                                <input type="hidden" name="decision" value="reject">
                                <input name="decision_reason" required maxlength="500" placeholder="Reason for rejection" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <button class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Reject</button>
                            </form>
                        </div>
                    @else
                        <p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-500">Reviewed by {{ $requestItem->reviewer?->name }} on {{ $requestItem->reviewed_at?->format('M j, Y g:i A') }}@if($requestItem->decision_reason) · {{ $requestItem->decision_reason }}@endif</p>
                    @endif
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">There are no void or refund requests.</p>
            @endforelse
        </div>

        <div class="mt-5">{{ $requests->links() }}</div>
    </div>
@endsection
