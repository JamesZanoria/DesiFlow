<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller{
    // Show the login form. There is no registration route
    // Every account is created by an admin through Staff Management instead.
    public function showLoginForm(){
        return view('auth.login');
    }

    // Authenticate a staff account. Requires a valid
    // email + password, denies access and shows an error on any failure.
    
    // Deliberately restricted to role === 'admin' beyond just checking the
    // password: Cashier and Kitchen Staff accounts can already be created in
    // Staff Management, but their modules (POS, KDS) don't exist as views
    // yet, so letting them log in here would land them in an admin panel
    // they have no business.
    public function login(Request $request){
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited($request);

        $email = strtolower(trim($validated['email']));

        if (! Auth::attempt(['email' => $email, 'password' => $validated['password']], $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (Auth::user()->role !== 'admin') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => "Your account doesn't have access to this portal.",
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        // Default landing page for Admin.
        return redirect()->intended(route('admin.dashboard.index'));
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // 5 attempts per minute, keyed by email + IP together, keyed on both so
    // one attacker can't lock a real admin out just by repeatedly guessing
    // their email from a different device, and so the same IP trying many
    // different emails doesn't get an unfairly high combined budget.
    private function ensureIsNotRateLimited(Request $request): void{
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(Request $request): string{
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }
}