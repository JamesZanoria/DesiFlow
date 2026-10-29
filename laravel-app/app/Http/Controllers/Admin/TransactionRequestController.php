<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\TransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionRequestController extends Controller
{
    public function index()
    {
        $requests = TransactionRequest::query()
            ->with(['payment.order', 'requester', 'reviewer'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('requested_at')
            ->paginate(30);

        return view('admin.transaction-requests.index', compact('requests'));
    }

    public function review(Request $request, TransactionRequest $transactionRequest)
    {
        $request->merge([
            'decision' => is_string($request->input('decision'))
                ? strtolower(trim($request->input('decision')))
                : $request->input('decision'),
            'decision_reason' => is_string($request->input('decision_reason'))
                ? trim($request->input('decision_reason'))
                : $request->input('decision_reason'),
        ]);

        $validated = $request->validate([
            'decision' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'decision_reason' => ['nullable', 'string', 'max:500', 'required_if:decision,reject'],
        ]);

        DB::transaction(function () use ($request, $transactionRequest, $validated) {
            $lockedRequest = TransactionRequest::query()
                ->whereKey($transactionRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages([
                    'decision' => 'This request has already been reviewed.',
                ]);
            }

            $payment = Payment::query()
                ->whereKey($lockedRequest->payment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($validated['decision'] === 'approve') {
                if ($payment->transaction_status !== 'completed') {
                    throw ValidationException::withMessages([
                        'decision' => 'The transaction is no longer eligible for approval.',
                    ]);
                }

                $payment->update([
                    'transaction_status' => $lockedRequest->request_type === 'void' ? 'voided' : 'refunded',
                ]);

                if ($lockedRequest->request_type === 'void') {
                    $payment->order()->whereNotIn('status', ['picked_up', 'dropped'])->update(['status' => 'voided']);
                }
            }

            $lockedRequest->update([
                'status' => $validated['decision'] === 'approve' ? 'approved' : 'rejected',
                'decision_reason' => $validated['decision_reason'] ?? null,
                'reviewed_by' => $request->user()->user_id,
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->route('admin.transaction-requests.index')
            ->with('status', 'Transaction request reviewed.');
    }
}
