<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password | DesiFlow Admin</title>
    @vite(['resources/css/app.css'])
    <style>
        .login-bg {
            background: linear-gradient(to bottom right, #020617, #0f172a);
        }
        .brand-accent {
            color: #d97706; /* amber-600 */
        }
        .btn-primary {
            background-color: #d97706; /* amber-600 */
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-primary:hover {
            background-color: #b45309; /* amber-700 */
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(217, 119, 6, 0.3);
        }
        .btn-primary:active {
            background-color: #92400e; /* amber-800 */
            transform: scale(0.98);
        }
        .btn-primary:disabled {
            background-color: #f59e0b;
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 2px solid #fff;
            width: 18px;
            height: 18px;
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

    <div class="w-full max-w-md mx-auto px-4 flex flex-col">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-600 text-white mb-4 shadow-xl shadow-amber-600/30 ring-4 ring-amber-600/10 transition-transform hover:scale-110 duration-300">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <p class="text-3xl font-bold brand-accent tracking-tight">Desi Zaika</p>
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-[0.2em] mt-1">DesiFlow Restaurant Admin</p>

            <!-- Admin Context Badge -->
            <div class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-800 border border-slate-700 text-[10px] font-bold text-slate-300 uppercase tracking-wider">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                Administrator Portal
            </div>
        </div>

        <!-- Request Card -->
        <div id="requestCard" class="bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transition-all duration-500">
            <div class="p-8 md:p-10">

                @if (session('status'))
                    <div class="mb-8 text-center animate-in fade-in zoom-in-95 duration-300">
                        <div class="mb-6 flex justify-center">
                            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center ring-8 ring-green-50">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900 mb-3">Check Your Email</h2>
                        <p class="text-sm text-slate-500 mb-8 leading-relaxed">
                            {{ session('status') }}
                        </p>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center w-full btn-primary text-white py-3 rounded-lg text-base font-semibold shadow-md">
                            Return to Log In
                        </a>
                    </div>
                @else
                    <div id="formContent">
                        <div class="mb-8 text-center md:text-left">
                            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Forgot Password?</h1>
                            <p class="text-sm text-slate-500 mt-1.5">Enter your email address and we'll send you a secure link to reset your password.</p>
                        </div>

                        @if ($errors->any())
                            <div class="mb-6 flex items-start gap-3 text-sm text-red-700 bg-red-50 border border-red-100 rounded-xl px-4 py-3 animate-in fade-in slide-in-from-top-2">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                <p class="font-medium">{{ $errors->first() }}</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}" id="resetRequestForm" class="space-y-6">
                            @csrf
                            <div class="group">
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5 transition-colors group-focus-within:text-amber-600">Email Address</label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-600 transition-colors duration-200 pointer-events-none">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                                        class="w-full border border-slate-200 rounded-lg pl-10 pr-10 py-3 text-sm transition-all outline-none placeholder:text-slate-400
                                        focus:ring-4 focus:ring-amber-500/15 focus:border-amber-500 {{ $errors->has('email') ? 'border-red-400 ring-red-500/10' : '' }}"
                                        placeholder="name@example.com">
                                </div>
                            </div>

                            <button type="submit" id="submitButton" class="w-full btn-primary text-white py-3 rounded-lg text-base font-semibold shadow-md focus:ring-4 focus:ring-amber-500/30 outline-none">
                                <span id="btnText">Send Reset Link</span>
                            </button>
                        </form>

                        <div class="mt-8 text-center">
                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                Back to Log In
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Support Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-slate-500">
                Need help? <a href="mailto:admin-support@desiflow.com" class="font-semibold text-amber-600 hover:text-amber-700 underline underline-offset-4">Contact Support</a>
            </p>
        </div>

        <!-- Minimalist Footer Links -->
        <div class="mt-8 mb-8 flex flex-wrap justify-center gap-x-6 gap-y-2 text-[11px] font-medium text-slate-500 uppercase tracking-wider">
            <a href="#" class="hover:text-slate-300 transition-colors">Privacy Policy</a>
            <a href="#" class="hover:text-slate-300 transition-colors">Terms of Service</a>
            <a href="#" class="hover:text-slate-300 transition-colors">System Status</a>
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
