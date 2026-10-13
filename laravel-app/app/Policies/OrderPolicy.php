<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Enforces (Transaction History) and (Void/Refund
 * requests) at the ownership level: a cashier may only view or act on
 * orders/transactions THEY personally processed. Admins bypass this
 * entirely, matching their "Full System Access" privilege in.
 
 * This is deliberately a Policy, not an ad-hoc `if` check inside a
 * controller, centralizing it here means every future controller method
 * that touches a single Order (show, void, refund, receipt reprint, etc.)
 * enforces the same rule automatically via $this->authorize(), rather than
 * each developer having to remember to re-implement the same ownership
 * check by hand and risk missing one.
 
 * WIRING (once the Cashier/POS module exists):
 *   1. Register in App\Providers\AuthServiceProvider:
 *        protected $policies = [
 *            Order::class => OrderPolicy::class,
 *        ];
 *   2. In any controller method that returns a single order's data:
 *        public function show(Order $order)
 *        {
 *            $this->authorize('view', $order);
 *            // ... proceed only if authorization passed; Laravel throws a
 *            // 403 automatically otherwise, before any data is returned.
 *        }
 *   3. Same pattern for void/refund submission:
 *        public function requestVoid(Request $request, Order $order)
 *        {
 *            $this->authorize('requestVoidRefund', $order);
 *            ...
 *        }
 */
class OrderPolicy
{
    // Viewing a single order's details (Order Quick-View Modal, Transaction
    // History detail breakdown, receipt reprint).
    public function view(User $user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'cashier'
            && $order->cashier_id === $user->user_id;
    }

    // Listing transactions (Transaction History page), used with
    // Gate::allows or a query-scoping helper rather than per-row, since the
    // list itself should already be scoped by cashier_id at the query level
    // (see OrderPolicy::scopeVisibleTo below) for performance. This method
    // exists for the case where a cashier reaches a single order directly
    // by URL/ID rather than through the scoped list.
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'cashier'], true);
    }

    // Submitting a void/refund request, a cashier may only
    // request this for an order they personally processed. Approving the
    // request is a SEPARATE, admin-only action and deliberately
    // has no policy method here, that lives in AdminApprovalPolicy or
    // equivalent, since "who can request" and "who can approve" are
    // different questions with different answers.
    public function requestVoidRefund(User $user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'cashier'
            && $order->cashier_id === $user->user_id;
    }

    /**
     * Query-level scoping helper for the Transaction History LIST itself
     * (as opposed to a single-order lookup). Use this in the controller
     * instead of fetching everything and filtering in PHP:
     
     *   $orders = OrderPolicy::visibleTo(Order::query(), $request->user())
     *       ->orderByDesc('created_at')
     *       ->paginate(25);
     
     * Scoping at the query level (WHERE cashier_id = ?) rather than
     * fetching all rows and checking each one in a loop is both faster and
     * safer, there's no way to accidentally leak a row that never got
     * filtered out, because it's never pulled from the database at all.
     */
    public static function visibleTo($query, User $user)
    {
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->where('cashier_id', $user->user_id);
    }
}