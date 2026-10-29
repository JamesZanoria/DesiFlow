<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\TransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CashierPaymentController extends Controller
{
    public function create(Request $request, Order $order)
    {
        $this->ensureCashierOwnsOrder($request, $order);

        if ($order->payment()->exists()) {
            return redirect()->route('cashier.receipts.show', $order);
        }

        abort_unless(in_array($order->status, ['awaiting_payment', 'pending', 'preparing', 'ready'], true), 404);

        $order->load(['orderItems.addOns', 'orderItems.menuItem']);

        return view('cashier.payment', compact('order'));
    }

    public function store(Request $request, Order $order)
    {
        $this->ensureCashierOwnsOrder($request, $order);

        $request->merge([
            'payment_method' => is_string($request->input('payment_method'))
                ? strtolower(trim($request->input('payment_method')))
                : $request->input('payment_method'),
            'gcash_ref_num' => is_string($request->input('gcash_ref_num'))
                ? trim($request->input('gcash_ref_num'))
                : $request->input('gcash_ref_num'),
        ]);

        $validated = $request->validate([
            'payment_method' => ['required', 'string', Rule::in(['cash', 'gcash'])],
            'cash_received' => [
                'required_if:payment_method,cash',
                'nullable',
                'regex:/^\d{1,8}(?:\.\d{1,2})?$/',
            ],
            'gcash_ref_num' => [
                'required_if:payment_method,gcash',
                'nullable',
                'string',
                'regex:/^[A-Za-z0-9-]{6,50}$/',
            ],
        ]);

        $payment = DB::transaction(function () use ($request, $order, $validated) {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureCashierOwnsOrder($request, $lockedOrder);

            if ($lockedOrder->payment()->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'This order already has a recorded payment.',
                ]);
            }

            if (! in_array($lockedOrder->status, ['awaiting_payment', 'pending', 'preparing', 'ready'], true)) {
                throw ValidationException::withMessages([
                    'payment' => 'This order is not eligible for payment.',
                ]);
            }

            $totalCents = $this->moneyToCents($lockedOrder->total_amount);
            $cashReceivedCents = null;
            $changeCents = 0;

            if ($validated['payment_method'] === 'cash') {
                $cashReceivedCents = $this->moneyToCents($validated['cash_received']);
                if ($cashReceivedCents < $totalCents) {
                    throw ValidationException::withMessages([
                        'cash_received' => 'The amount received must be at least the order total.',
                    ]);
                }
                $changeCents = $cashReceivedCents - $totalCents;
            } else {
                $cashReceivedCents = $totalCents;
            }

            $payment = Payment::create([
                'order_id' => $lockedOrder->order_id,
                'payment_method' => $validated['payment_method'],
                'gcash_ref_num' => $validated['payment_method'] === 'gcash' ? $validated['gcash_ref_num'] : null,
                'amount_tethered' => $cashReceivedCents / 100,
                'change_due' => $changeCents / 100,
                'transaction_status' => 'completed',
                'created_at' => now(),
            ]);

            if ($lockedOrder->status === 'awaiting_payment') {
                $lockedOrder->update(['status' => 'pending']);
            }

            return $payment;
        });

        return redirect()->route('cashier.receipts.show', $payment->order_id)
            ->with('status', 'Payment recorded. The digital receipt is ready.');
    }

    public function receipt(Request $request, Order $order)
    {
        $this->ensureCashierOwnsOrder($request, $order);

        $order->load(['orderItems.addOns', 'orderItems.menuItem', 'payment']);
        abort_unless($order->payment, 404);

        return view('cashier.receipt', compact('order'));
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:40'],
        ]);

        $payments = Payment::query()
            ->with('order')
            ->whereHas('order', fn ($query) => $query->where('cashier_id', $request->user()->user_id))
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when(($validated['search'] ?? '') !== '', fn ($query) => $query->whereHas('order', fn ($orderQuery) => $orderQuery
                ->where('cashier_id', $request->user()->user_id)
                ->where('order_number', 'like', '%' . $validated['search'] . '%')))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('cashier.history', compact('payments', 'validated'));
    }

    public function showTransaction(Request $request, Payment $payment)
    {
        $this->ensureCashierOwnsPayment($request, $payment);
        $payment->load(['order.orderItems.addOns', 'order.orderItems.menuItem', 'transactionRequests.requester', 'transactionRequests.reviewer']);

        return view('cashier.transaction', compact('payment'));
    }

    public function requestTransactionAction(Request $request, Payment $payment)
    {
        $this->ensureCashierOwnsPayment($request, $payment);

        $request->merge([
            'request_type' => is_string($request->input('request_type'))
                ? strtolower(trim($request->input('request_type')))
                : $request->input('request_type'),
            'reason' => is_string($request->input('reason'))
                ? trim($request->input('reason'))
                : $request->input('reason'),
        ]);

        $validated = $request->validate([
            'request_type' => ['required', 'string', Rule::in(['void', 'refund'])],
            'reason' => ['required', 'string', 'filled', 'min:5', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $payment, $validated) {
            $lockedPayment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureCashierOwnsPayment($request, $lockedPayment);

            if ($lockedPayment->transaction_status !== 'completed') {
                throw ValidationException::withMessages([
                    'request_type' => 'Only completed transactions can be submitted for void or refund review.',
                ]);
            }

            if ($lockedPayment->transactionRequests()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'request_type' => 'This transaction already has a pending review request.',
                ]);
            }

            TransactionRequest::create([
                'payment_id' => $lockedPayment->payment_id,
                'requested_by' => $request->user()->user_id,
                'request_type' => $validated['request_type'],
                'reason' => $validated['reason'],
                'status' => 'pending',
                'requested_at' => now(),
            ]);
        });

        return redirect()->route('cashier.transactions.show', $payment)
            ->with('status', 'Your request was sent to an administrator for review.');
    }

    private function ensureCashierOwnsOrder(Request $request, Order $order): void
    {
        abort_unless((int) $order->cashier_id === (int) $request->user()->user_id, 404);
    }

    private function ensureCashierOwnsPayment(Request $request, Payment $payment): void
    {
        $payment->loadMissing('order');
        abort_unless(
            $payment->order && (int) $payment->order->cashier_id === (int) $request->user()->user_id,
            404
        );
    }

    private function moneyToCents(string|int|float $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', number_format((float) $amount, 2, '.', '')), 2, '00');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
