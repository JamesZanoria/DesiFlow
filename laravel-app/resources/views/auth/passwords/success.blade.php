<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Password Reset Successful | DesiFlow Admin</title>
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

        <!-- Success Card -->
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transition-all duration-300">
            <div class="p-8 md:p-10 text-center">
                <div class="mb-8 flex justify-center">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center ring-8 ring-green-50 animate-in zoom-in duration-500">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mb-3 tracking-tight">Password Reset Complete</h1>
                <p class="text-sm text-slate-500 mb-10 leading-relaxed">Your security credentials have been updated successfully. You can now log in to your administrative account.</p>

                <a href="{{ route('login') }}" class="block w-full btn-primary text-white py-3 rounded-lg text-base font-semibold shadow-md focus:ring-4 focus:ring-amber-500/30 outline-none">
                    Go to Log In
                </a>
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
</body>
</html>
