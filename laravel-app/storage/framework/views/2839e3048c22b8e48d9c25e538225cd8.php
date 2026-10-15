<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Log In | DesiFlow Admin</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
    <style>
        .login-bg {
            background: linear-gradient(to bottom right, #020617, #0f172a); /* from-slate-950 to-slate-900 */
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

        /* Custom Checkbox Animation */
        .custom-checkbox {
            appearance: none;
            -webkit-appearance: none;
            width: 1.1rem;
            height: 1.1rem;
            border: 2px solid #cbd5e1;
            border-radius: 0.25rem;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            display: grid;
            place-content: center;
        }
        .custom-checkbox:checked {
            background-color: #d97706;
            border-color: #d97706;
        }
        .custom-checkbox:checked::before {
            content: "";
            width: 0.65rem;
            height: 0.65rem;
            background-color: white;
            border-radius: 50%;
            animation: scale-in 0.15s ease-out;
        }
        .custom-checkbox:focus {
            box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.15);
        }
        @keyframes scale-in {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
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

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transition-all duration-300">
            <div class="p-8 md:p-10">
                <div class="mb-8 text-center md:text-left">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Welcome Back</h1>
                    <p class="text-sm text-slate-500 mt-1.5">Sign in to your administrator account.</p>
                </div>

                <?php if($errors->any()): ?>
                    <div class="mb-6 flex items-start gap-3 text-sm text-red-700 bg-red-50 border border-red-100 rounded-xl px-4 py-3 animate-in fade-in slide-in-from-top-2">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        <p class="font-medium"><?php echo e($errors->first()); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('login')); ?>" id="loginForm" class="space-y-6">
                    <?php echo csrf_field(); ?>

                    <div class="space-y-5">
                        <div class="group">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5 transition-colors group-focus-within:text-amber-600">Email Address</label>
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-600 transition-colors duration-200 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <input type="email" name="email" id="email-input" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="username"
                                    class="w-full border border-slate-200 rounded-lg pl-10 pr-10 py-3 text-sm transition-all outline-none placeholder:text-slate-400
                                    focus:ring-4 focus:ring-amber-500/15 focus:border-amber-500 <?php echo e($errors->has('email') ? 'border-red-400 ring-red-500/10' : ''); ?>"
                                    placeholder="name@example.com">
                                <button type="button" id="clear-email" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-full hover:bg-slate-100 hidden">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>

                        <div class="group">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5 transition-colors group-focus-within:text-amber-600">Password</label>
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-600 transition-colors duration-200 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </div>
                                <input type="password" name="password" required autocomplete="current-password" id="login-password"
                                    class="w-full border border-slate-200 rounded-lg pl-10 pr-11 py-3 text-sm transition-all outline-none placeholder:text-slate-400
                                    focus:ring-4 focus:ring-amber-500/15 focus:border-amber-500 <?php echo e($errors->has('password') ? 'border-red-400 ring-red-500/10' : ''); ?>"
                                    placeholder="••••••••">
                                <button type="button" id="login-password-toggle" tabindex="-1"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors p-1.5 rounded-full hover:bg-slate-100">
                                    <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                            <div id="caps-lock-warning" class="hidden mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-amber-600 animate-in fade-in slide-in-from-left-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 15c-.77 1.333.192 3 1.732 3z" /></svg>
                                Caps Lock is ON
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer group transition-colors hover:text-slate-900">
                            <input type="checkbox" name="remember" class="custom-checkbox">
                            <span class="group-hover:text-slate-900 transition-colors">Remember me</span>
                        </label>
                        <a href="<?php echo e(route('password.request')); ?>" class="text-sm font-semibold text-amber-600 hover:text-amber-700 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 rounded px-1">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" id="submitButton" class="w-full btn-primary text-white py-3 rounded-lg text-base font-semibold shadow-md focus:ring-4 focus:ring-amber-500/30 outline-none">
                        <span id="btnText">Log In</span>
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-sm text-slate-500 hover:text-amber-600 transition-colors">
                        Having trouble logging in? <a href="mailto:admin-support@desiflow.com" class="font-semibold text-amber-600 hover:text-amber-700 underline underline-offset-4">Contact Support</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Minimalist Footer Links -->
        <div class="mt-8 flex flex-wrap justify-center gap-x-6 gap-y-2 text-[11px] font-medium text-slate-500 uppercase tracking-wider">
            <a href="#" class="hover:text-slate-300 transition-colors">Privacy Policy</a>
            <a href="#" class="hover:text-slate-300 transition-colors">Terms of Service</a>
            <a href="#" class="hover:text-slate-300 transition-colors">System Status</a>
        </div>
    </div>

    <script>
        // Password Visibility Toggle
        document.getElementById('login-password-toggle').addEventListener('click', function () {
            const field = document.getElementById('login-password');
            const isHidden = field.type === 'password';
            field.type = isHidden ? 'text' : 'password';
            this.querySelector('.eye-open').classList.toggle('hidden', isHidden);
            this.querySelector('.eye-closed').classList.toggle('hidden', !isHidden);
        });

        // Clear Email Input
        const emailInput = document.getElementById('email-input');
        const clearEmailBtn = document.getElementById('clear-email');

        const updateClearButton = () => {
            clearEmailBtn.classList.toggle('hidden', emailInput.value === '');
        };

        emailInput.addEventListener('input', updateClearButton);
        clearEmailBtn.addEventListener('click', () => {
            emailInput.value = '';
            updateClearButton();
            emailInput.focus();
        });
        updateClearButton();

        // Caps Lock Detection
        const passwordField = document.getElementById('login-password');
        const capsWarning = document.getElementById('caps-lock-warning');

        passwordField.addEventListener('keydown', async (e) => {
            if (e.getModifierState('CapsLock')) {
                capsWarning.classList.remove('hidden');
            } else {
                capsWarning.classList.add('hidden');
            }
        });

        passwordField.addEventListener('focus', (e) => {
            if (e.getModifierState('CapsLock')) {
                capsWarning.classList.remove('hidden');
            } else {
                capsWarning.classList.add('hidden');
            }
        });

        // Loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitButton');
            const text = document.getElementById('btnText');

            btn.disabled = true;
            text.innerHTML = '<span class="spinner"></span> Signing in...';
        });
    </script>
</body>
</html><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/auth/login.blade.php ENDPATH**/ ?>