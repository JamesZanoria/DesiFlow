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

 * Registered as 'auth' route middleware runs first (see web.php), so
 * Auth::user() is guaranteed non-null here, this only needs to check role.
 */
class EnsureUserHasRole{
    public function handle(Request $request, Closure $next, string ...$roles): Response{
        if (! in_array(Auth::user()->role, $roles, true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => "Your account doesn't have access to this portal."]);
        }

        return $next($request);
    }
}