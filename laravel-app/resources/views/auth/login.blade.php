<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log In | DesiFlow</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <p class="text-2xl font-bold text-amber-500">Desi Zaika</p>
            <p class="text-sm text-gray-400">DesiFlow Restaurant Admin</p>
        </div>

        <div class="bg-white rounded-xl shadow-xl p-6">
            <h1 class="text-lg font-semibold text-gray-800 mb-1">Log in to your account</h1>
            <p class="text-xs text-gray-400 mb-5">Only administrator accounts have access to this portal.</p>

            @if ($errors->any())
                <div class="mb-4 flex items-start gap-2 text-sm text-red-700 bg-red-50 border border-red-100 rounded-lg px-3 py-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    <p>{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="w-full border rounded-lg px-3 py-2 text-sm {{ $errors->has('email') ? 'border-red-400' : '' }}"
                        placeholder="name@example.com">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" required autocomplete="current-password" id="login-password"
                            class="w-full border rounded-lg px-3 py-2 pr-10 text-sm {{ $errors->has('password') ? 'border-red-400' : '' }}"
                            placeholder="Enter your password">
                        <button type="button" id="login-password-toggle" tabindex="-1"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="w-full bg-amber-500 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-600">
                    Log In
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-500 mt-4">Need an account? Ask an administrator to add you in Staff Management.</p>
    </div>

    <script>
        document.getElementById('login-password-toggle').addEventListener('click', function () {
            const field = document.getElementById('login-password');
            const isHidden = field.type === 'password';
            field.type = isHidden ? 'text' : 'password';
            this.querySelector('.eye-open').classList.toggle('hidden', isHidden);
            this.querySelector('.eye-closed').classList.toggle('hidden', !isHidden);
        });
    </script>

</body>
</html>