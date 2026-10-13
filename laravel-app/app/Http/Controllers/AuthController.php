<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller{
    // Show the login form. There is no registration route
    // Every account is created by an admin through Staff Management instead.
    public function showLoginForm(){
        return view('auth.login');
    }

    // Password Reset Logic
    public function showResetForm(){
        return view('auth.passwords.email');
    }

    public function sendResetLink(Request $request){
        $request->validate(['email' => 'required|email']);

        // Standard Laravel Password Broker handles the token generation and mailing
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'We have emailed your password reset link!');
    }

    public function showResetPasswordForm(Request $request, $token = null){
        return view('auth.passwords.reset', ['token' => $token]);
    }

    public function resetPassword(Request $request){
        $request->validate([
            'token' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        // Using the Password broker to verify the token and update the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->save();
                return true;
            }
        );

        if (!$status) {
            throw ValidationException::withMessages([
                'password' => 'The provided token is invalid or has expired.',
            ]);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset successfully.');
    }

    // Authenticate a staff account. Requires a valid
    // email + password, denies access and shows an error on any failure.

    // Staff roles are redirected to their own modules after authentication.
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

        if (! in_array(Auth::user()->role, ['admin', 'cashier', 'kitchen'], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => "Your account doesn't have access to this portal.",
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        $landingRoute = match (Auth::user()->role) {
            'cashier' => 'cashier.index',
            'kitchen' => 'kds.index',
            default => 'admin.dashboard.index',
        };

        return redirect()->route($landingRoute);
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // 5 attempts per minute, keyed by email + IP together, keyed on so
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