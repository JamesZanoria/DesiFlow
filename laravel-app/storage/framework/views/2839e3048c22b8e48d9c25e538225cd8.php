<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Log In | DesiFlow</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
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
            background-color: var(--accent-color, #d97706);
            border-color: var(--accent-color, #d97706);
        }
        .custom-checkbox:checked::before {
            content: "";
            width: 0.6rem;
            height: 0.6rem;
            background-color: white;
            border-radius: 50%;
            animation: scale-in 0.15s ease-out;
        }
        @keyframes scale-in {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4 font-sans relative">
    <?php
        $portal = request('portal', 'admin');
        $accentColor = 'amber-600';
        $accentBg = 'bg-amber-600';
        $accentRing = 'focus:ring-amber-500/20 focus:border-amber-600';
        $roleIdentity = 'ADMINISTRATION';
        $roleLabel = 'Administrator';
        $roleDesc = 'Management Portal';

        if ($portal === 'cashier') {
            $accentColor = 'emerald-600';
            $accentBg = 'bg-emerald-600';
            $accentRing = 'focus:ring-emerald-500/20 focus:border-emerald-600';
            $roleIdentity = 'CASHIER POS';
            $roleLabel = 'Cashier';
            $roleDesc = 'Point of Sale Terminal';
        } elseif ($portal === 'kitchen') {
            $accentColor = 'indigo-600';
            $accentBg = 'bg-indigo-600';
            $accentRing = 'focus:ring-indigo-500/20 focus:border-indigo-600';
            $roleIdentity = 'KITCHEN KDS';
            $roleLabel = 'Kitchen Staff';
            $roleDesc = 'Kitchen Display System';
        }
    ?>

    
    <div class="absolute top-6 right-6 flex items-center gap-2 bg-white/80 backdrop-blur-sm p-1.5 rounded-full border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3">Portal:</span>
        <a href="<?php echo e(route('login', ['portal' => 'admin'])); ?>"
           class="px-3 py-1 rounded-full text-[11px] font-bold transition-all <?php echo e($portal === 'admin' ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-200' : 'text-slate-500 hover:bg-slate-100'); ?>">
           Admin
        </a>
        <a href="<?php echo e(route('login', ['portal' => 'cashier'])); ?>"
           class="px-3 py-1 rounded-full text-[11px] font-bold transition-all <?php echo e($portal === 'cashier' ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : 'text-slate-500 hover:bg-slate-100'); ?>">
           Cashier
        </a>
        <a href="<?php echo e(route('login', ['portal' => 'kitchen'])); ?>"
           class="px-3 py-1 rounded-full text-[11px] font-bold transition-all <?php echo e($portal === 'kitchen' ? 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200' : 'text-slate-500 hover:bg-slate-100'); ?>">
           Kitchen
        </a>
    </div>

    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl <?php echo e($accentBg); ?> text-white mb-4 shadow-xl shadow-<?php echo e($accentColor); ?>/20 ring-4 ring-white">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">DesiFlow</h1>
            <p class="text-sm text-slate-500 mt-1 font-medium"><?php echo e($roleDesc); ?></p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            
            <div class="<?php echo e($accentBg); ?> py-3 px-6 text-center">
                <span class="text-[10px] font-black text-white uppercase tracking-[0.2em]">
                    <?php echo e($roleIdentity); ?>

                </span>
            </div>

            <div class="p-8 md:p-10">
                <div class="mb-8 text-center">
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Welcome Back</h2>
                    <p class="text-sm text-slate-500 mt-1">Please sign in to your <?php echo e($roleLabel); ?> account.</p>
                </div>

                
                <?php if($errors->any()): ?>
                    <div class="mb-6 flex items-center gap-3 text-sm text-red-600 bg-red-50 border border-red-100 rounded-2xl px-4 py-3 animate-in fade-in slide-in-from-top-2">
                        <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <p class="font-medium"><?php echo e($errors->first()); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('login')); ?>" id="loginForm" class="space-y-5">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="portal" value="<?php echo e($portal); ?>">

                    <div class="space-y-4">
                        <div class="group">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Email Address</label>
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-<?php echo e($accentColor); ?> transition-colors pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <input type="email" name="email" id="email-input" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="username"
                                    class="w-full border border-slate-200 rounded-xl pl-10 pr-10 py-3 text-sm transition-all outline-none placeholder:text-slate-400
                                    <?php echo e($accentRing); ?> <?php echo e($errors->has('email') ? 'border-red-400 ring-red-500/10' : ''); ?>"
                                    placeholder="name@example.com">
                                <button type="button" id="clear-email" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-full hover:bg-slate-100 hidden">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>

                        <div class="group">
                            <div class="flex justify-between items-center mb-1.5 ml-1">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Password</label>
                                <a href="<?php echo e(route('password.request')); ?>" class="text-xs font-bold text-<?php echo e($accentColor); ?> hover:text-<?php echo e($accentColor); ?>-700 transition-colors flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572l12.732-12.732z" /></svg>
                                    Reset Password
                                </a>
                            </div>
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-<?php echo e($accentColor); ?> transition-colors pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </div>
                                <input type="password" name="password" required autocomplete="current-password" id="login-password"
                                    class="w-full border border-slate-200 rounded-xl pl-10 pr-11 py-3 text-sm transition-all outline-none placeholder:text-slate-400
                                    <?php echo e($accentRing); ?> <?php echo e($errors->has('password') ? 'border-red-400 ring-red-500/10' : ''); ?>"
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
                            <input type="checkbox" name="remember" class="custom-checkbox" style="--accent-color: #<?php echo e($accentColor === 'amber-600' ? 'd97706' : ($accentColor === 'emerald-600' ? '059669' : '4f46e5')); ?>">
                            <span class="group-hover:text-slate-900 transition-colors">Remember me</span>
                        </label>
                    </div>

                    <button type="submit" id="submitButton" class="w-full <?php echo e($accentBg); ?> text-white py-3.5 rounded-xl text-base font-bold shadow-lg shadow-<?php echo e($accentColor); ?>/20 btn-transition hover:opacity-90 active:scale-[0.98] focus:ring-4 focus:ring-<?php echo e($accentColor); ?>/30 outline-none">
                        <span id="btnText">Sign In</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-sm text-slate-500">
                Need help accessing your account? <a href="mailto:support@desiflow.com" class="font-semibold text-<?php echo e($accentColor); ?> hover:text-<?php echo e($accentColor); ?>-700 transition-colors">Contact Support</a>
            </p>
        </div>
    </div>

    <script>
        // Force a page reload if the user lands here via the back button
        // This prevents the browser from showing a cached version of the login page
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };

        document.getElementById('login-password-toggle').addEventListener('click', function () {
            const field = document.getElementById('login-password');
            const isHidden = field.type === 'password';
            field.type = isHidden ? 'text' : 'password';
            this.querySelector('.eye-open').classList.toggle('hidden', isHidden);
            this.querySelector('.eye-closed').classList.toggle('hidden', !isHidden);
        });

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

        const passwordField = document.getElementById('login-password');
        const capsWarning = document.getElementById('caps-lock-warning');
        passwordField.addEventListener('keydown', async (e) => {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                capsWarning.classList.remove('hidden');
            } else {
                capsWarning.classList.add('hidden');
            }
        });
        passwordField.addEventListener('focus', (e) => {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                capsWarning.classList.remove('hidden');
            } else {
                capsWarning.classList.add('hidden');
            }
        });

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitButton');
            const text = document.getElementById('btnText');
            btn.disabled = true;
            text.innerHTML = '<span class="spinner"></span> Authenticating...';
        });
    </script>
</body>
</html><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/auth/login.blade.php ENDPATH**/ ?>