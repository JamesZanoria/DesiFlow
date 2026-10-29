<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password | DesiFlow</title>
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
            <p class="text-sm text-slate-500 mt-1 font-medium">Secure Account Recovery</p>
        </div>

        <!-- Request Card -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="bg-amber-600 py-3 px-6 text-center">
                <span class="text-[10px] font-black text-white uppercase tracking-[0.2em]">
                    Password Recovery
                </span>
            </div>

            <div class="p-8 md:p-10">
                @if (session('status'))
                    <div class="mb-8 text-center animate-in fade-in zoom-in-95 duration-300">
                        <div class="mb-6 flex justify-center">
                            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center ring-8 ring-emerald-50">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900 mb-3">Check Your Email</h2>
                        <p class="text-sm text-slate-500 mb-8 leading-relaxed">
                            {{ session('status') }}
                        </p>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center w-full bg-amber-600 text-white py-3 rounded-xl text-base font-bold shadow-lg shadow-amber-600/20 btn-transition hover:opacity-90 active:scale-[0.98]">
                            Return to Log In
                        </a>
                    </div>
                @else
                    <div id="formContent">
                        <div class="mb-8 text-center">
                            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Forgot Password?</h2>
                            <p class="text-sm text-slate-500 mt-1.5">Enter your email address and we'll send you a secure link to reset your password.</p>
                        </div>

                        @if ($errors->any())
                            <div class="mb-6 flex items-center gap-3 text-sm text-red-600 bg-red-50 border border-red-100 rounded-2xl px-4 py-3 animate-in fade-in slide-in-from-top-2">
                                <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <p class="font-medium">{{ $errors->first() }}</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}" id="resetRequestForm" class="space-y-6">
                            @csrf
                            <div class="group">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Email Address</label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-600 transition-colors pointer-events-none">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                                        class="w-full border border-slate-200 rounded-xl pl-10 pr-10 py-3 text-sm transition-all outline-none placeholder:text-slate-400
                                        focus:ring-4 focus:ring-amber-500/20 focus:border-amber-600 {{ $errors->has('email') ? 'border-red-400 ring-red-500/10' : '' }}"
                                        placeholder="name@example.com">
                                </div>
                            </div>

                            <button type="submit" id="submitButton" class="w-full bg-amber-600 text-white py-3.5 rounded-xl text-base font-bold shadow-lg shadow-amber-600/20 btn-transition hover:opacity-90 active:scale-[0.98] focus:ring-4 focus:ring-amber-500/30 outline-none">
                                <span id="btnText">Send Reset Link</span>
                            </button>
                        </form>

                        <div class="mt-8 text-center">
                            <a href="{{ route('login') }}" class="text-sm font-bold text-slate-400 hover:text-slate-900 transition-colors inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                Back to Log In
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-sm text-slate-500">
                Need help? <a href="mailto:support@desiflow.com" class="font-semibold text-amber-600 hover:text-amber-700 transition-colors">Contact Support</a>
            </p>
        </div>
    </div>

    <script>
        document.getElementById('resetRequestForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('submitButton');
            const text = document.getElementById('btnText');
            if (!this.checkValidity()) return;
            btn.disabled = true;
            text.innerHTML = '<span class="spinner"></span> Sending...';
        });
    </script>
</body>
</html>