<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password | DesiFlow</title>
    @vite(['resources/css/app.css'])
    <style>
        .login-bg {
            background: radial-gradient(circle at top left, #f8fafc, #f1f5f9);
        }
        .btn-transition {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 2px solid #fff;
            width: 16px;
            height: 16px;
            animation: spin 0.8s linear infinite;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-amber-600 text-white mb-4 shadow-xl shadow-amber-600/20 ring-4 ring-white">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">DesiFlow</h1>
            <p class="text-sm text-slate-500 mt-1 font-medium">Secure Password Update</p>
        </div>

        <!-- Reset Card -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="bg-amber-600 py-3 px-6 text-center">
                <span class="text-[10px] font-black text-white uppercase tracking-[0.2em]">
                    Security Update
                </span>
            </div>

            <div class="p-8 md:p-10">
                <div class="mb-8 text-center">
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Reset Password</h2>
                    <p class="text-sm text-slate-500 mt-1">Please enter your new secure password below.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 flex items-center gap-3 text-sm text-red-600 bg-red-50 border border-red-100 rounded-2xl px-4 py-3 animate-in fade-in slide-in-from-top-2">
                        <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <p class="font-medium">{{ $errors->first() }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="space-y-4">
                        <div class="group">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">New Password</label>
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-600 transition-colors pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </div>
                                <input type="password" name="password" required autocomplete="new-password" id="reset-password"
                                    class="w-full border border-slate-200 rounded-xl pl-10 pr-11 py-3 text-sm transition-all outline-none placeholder:text-slate-400
                                    focus:ring-4 focus:ring-amber-500/20 focus:border-amber-600 {{ $errors->has('password') ? 'border-red-400 ring-red-500/10' : '' }}"
                                    placeholder="••••••••">
                                <button type="button" id="reset-password-toggle" tabindex="-1"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors p-1.5 rounded-full hover:bg-slate-100">
                                    <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                        </div>
                        <div class="group">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Confirm New Password</label>
                            <input type="password" name="password_confirmation" required autocomplete="new-password"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm transition-all outline-none placeholder:text-slate-400
                                focus:ring-4 focus:ring-amber-500/20 focus:border-amber-600 {{ $errors->has('password_confirmation') ? 'border-red-400 ring-red-500/10' : '' }}"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" id="submitButton" class="w-full bg-amber-600 text-white py-3.5 rounded-xl text-base font-bold shadow-lg shadow-amber-600/20 btn-transition hover:opacity-90 active:scale-[0.98] focus:ring-4 focus:ring-amber-500/30 outline-none">
                        <span id="btnText">Update Password</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-sm text-slate-500">
                Need help? <a href="mailto:support@desiflow.com" class="font-semibold text-amber-600 hover:text-amber-700 transition-colors">Contact Support</a>
            </p>
        </div>
    </div>

    <script>
        document.getElementById('reset-password-toggle').addEventListener('click', function () {
            const field = document.getElementById('reset-password');
            const isHidden = field.type === 'password';
            field.type = isHidden ? 'text' : 'password';
            this.querySelector('.eye-open').classList.toggle('hidden', isHidden);
            this.querySelector('.eye-closed').classList.toggle('hidden', !isHidden);
        });

        document.getElementById('resetPasswordForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitButton');
            const text = document.getElementById('btnText');
            if (!this.checkValidity()) return;
            btn.disabled = true;
            text.innerHTML = '<span class="spinner"></span> Updating...';
        });
    </script>
</body>
</html>