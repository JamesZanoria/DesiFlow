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
    // Show the login form.
    public function showLoginForm(Request $request){
        $portal = $request->query('portal', 'admin');

        return response()
            ->view('auth.login', ['portal' => $portal])
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
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

    // Authenticate a staff account.
    public function login(Request $request){
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited($request);

        $email = strtolower(trim($validated['email']));

        // Capture the portal from hidden input, query string, or default to 'admin'
        $portal = $request->input('portal') ?: $request->query('portal', 'admin');

        if (! Auth::attempt(['email' => $email, 'password' => $validated['password']], $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        // RBAC: Administrator is a Super-User and bypasses all portal restrictions.
        if ($user->role !== 'admin') {
            $isAllowed = match ($portal) {
                'cashier' => in_array($user->role, ['cashier', 'kitchen'], true),
                'kitchen' => in_array($user->role, ['cashier', 'kitchen'], true),
                default   => false, // Non-admins cannot access the administration portal
            };

            if (!$isAllowed) {
                Auth::logout();

                $roleNames = [
                    'admin' => 'Administrator',
                    'cashier' => 'Cashier',
                    'kitchen' => 'Kitchen Staff',
                ];
                $userRole = $roleNames[$user->role] ?? 'Unknown';

                throw ValidationException::withMessages([
                    'email' => "Your account is registered as {$userRole}. You are not authorized to access this portal.",
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        // PORTAL LOCK: Bind the session to the specific portal used for login.
        session(['active_portal' => $portal]);

        // DYNAMIC REDIRECTION:
        // Redirect based on the portal they used to log in.
        $landingRoute = match ($portal) {
            'cashier' => 'cashier.index',
            'kitchen' => 'kds.index',
            default => match ($user->role) {
                'cashier' => 'cashier.index',
                'kitchen' => 'kds.index',
                default => 'admin.dashboard.index',
            },
        };

        return redirect()->route($landingRoute);
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

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