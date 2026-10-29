<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route (or route group) to users holding one of the given
 * roles, e.g. Route::middleware('role:admin'). Written generically rather
 * than as an admin-only check, defines Cashier and Kitchen
 * Staff as separate roles with their own restricted modules; when those
 * modules are built, they gate the same way via role:cashier / role:kitchen
 * without needing a new middleware class.
 *
 * Registered as 'auth' route middleware runs first (see web.php), so
 * Auth::user() is guaranteed non-null here, this only needs to check role.
 */
class EnsureUserHasRole{
    public function handle(Request $request, Closure $next, string ...$roles): Response{
        $user = Auth::user();
        $activePortal = session('active_portal');

        // 1. Basic Role Validation
        // Admin is a Super-User and can access any portal.
        $hasRoleAccess = ($user->role === 'admin') || in_array($user->role, $roles, true);

        if (!$hasRoleAccess) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => "Your account does not have access to this portal."]);
        }

        // 2. Portal Session Lock (Enterprise Security)
        // We determine which portal the current URL belongs to.
        $currentPortal = match (true) {
            $request->is('admin*') => 'admin',
            $request->is('cashier*') => 'cashier',
            $request->is('kds*') => 'kitchen',
            default => 'admin',
        };

        // STRICT LOCK: Regardless of role (Admin or Staff), if the current URL portal
        // doesn't match the session's active portal, it's a session mismatch.
        // This prevents a user who logged into POS from "backing" into the Admin dashboard.
        if ($activePortal && $activePortal !== $currentPortal) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => "Session mismatch. Please log in to the correct portal."]);
        }

        return $next($request);
    }
}