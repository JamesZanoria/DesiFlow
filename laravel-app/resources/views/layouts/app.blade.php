<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Desi Zaika') | DesiFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx', 'resources/js/admin.js', 'resources/js/assistant.js'])
    <style>
        .no-transition { transition: none !important; }
        .sidebar-transition { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-text-transition { transition: opacity 0.2s ease-in-out; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900">
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside id="main-sidebar" class="{{ request()->cookie('sidebar_collapsed') ? 'w-20' : 'w-64' }} bg-slate-900 text-slate-300 flex flex-col shadow-xl z-20 sidebar-transition relative no-transition">
            <!-- Logo Section -->
            <div class="px-6 py-6 flex items-center gap-3 border-b border-slate-800/50 overflow-hidden">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-bold shrink-0 shadow-lg shadow-amber-900/20">
                    <span class="text-[10px]">DZ</span>
                </div>
                <div class="flex flex-col overflow-hidden sidebar-text {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">
                    <p class="text-base font-bold text-white leading-tight truncate">Desi Zaika</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest font-medium truncate">Admin Studio</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-7 overflow-y-auto custom-scrollbar">
                <div class="space-y-3">
                    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-widest mb-3 sidebar-text {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Main Menu</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.dashboard.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.dashboard.index') ? 'bg-amber-500 text-white shadow-md shadow-amber-900/20 font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012 2.25v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                            <span class="sidebar-text transition-opacity duration-200 {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Dashboard</span>
                        </a>
                        <a href="{{ route('admin.orders.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.orders.*') ? 'bg-amber-500 text-white shadow-md shadow-amber-900/20 font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M15.75 3v1.5M3 15h18M4.5 6h15A1.5 1.5 0 0121 7.5v10.5a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 18V7.5A1.5 1.5 0 014.5 6z" /></svg>
                            <span class="sidebar-text transition-opacity duration-200 {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Real-Time Orders</span>
                        </a>
                        <a href="{{ route('admin.menu.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.menu.*') ? 'bg-amber-500 text-white shadow-md shadow-amber-900/20 font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                            <span class="sidebar-text transition-opacity duration-200 {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Menu Management</span>
                        </a>
                        <a href="{{ route('admin.inventory.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.inventory.*') ? 'bg-amber-500 text-white shadow-md shadow-amber-900/20 font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                            <span class="sidebar-text transition-opacity duration-200 {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Inventory</span>
                        </a>
                        <a href="{{ route('admin.staff.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.staff.*') ? 'bg-amber-500 text-white shadow-md shadow-amber-900/20 font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                            <span class="sidebar-text transition-opacity duration-200 {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Staff Management</span>
                        </a>
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="px-3 text-[10px] font-semibold text-slate-500 uppercase tracking-widest mb-3 sidebar-text {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Insights</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.analytics.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.analytics.*') ? 'bg-amber-500 text-white shadow-md shadow-amber-900/20 font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                            <span class="sidebar-text transition-opacity duration-200 {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Analytics</span>
                        </a>
                        <a href="{{ route('admin.assistant.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.assistant.*') ? 'bg-amber-500 text-white shadow-md shadow-amber-900/20 font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                            <span class="sidebar-text transition-opacity duration-200 {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Smart Assistant</span>
                        </a>
                        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-500 cursor-not-allowed group" title="Coming soon">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            <span class="sidebar-text transition-opacity duration-200 {{ request()->cookie('sidebar_collapsed') ? 'opacity-0 hidden' : '' }}">Sales Reports</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User / System Section -->
            <div class="px-4 pb-6 border-t border-slate-800 pt-6 space-y-2">
                <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition-all duration-200">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.725 1.725 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.725 1.725 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.725 1.725 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.725 1.725 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.725 1.725 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.725 1.725 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.725 1.725 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <span class="sidebar-text transition-opacity duration-200">Settings</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition-all duration-200">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                        <span class="sidebar-text transition-opacity duration-200">Logout</span>
                    </button>
                </form>
            </div>

            <!-- Collapse Trigger -->
            <button id="sidebar-collapse-btn" class="w-full flex items-center justify-center py-3 bg-slate-950 text-slate-400 hover:text-amber-500 hover:bg-slate-800 transition-all duration-300 border-t border-slate-800 group">
                <div class="flex items-center gap-3">
                    <svg id="collapse-icon" class="w-4 h-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    <span class="sidebar-text text-[10px] font-bold uppercase tracking-widest transition-opacity duration-200">Collapse Menu</span>
                </div>
            </button>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top bar -->
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between z-10">
                <div class="flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-widest">
                    <span>System</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    <span class="text-slate-600">@yield('title')</span>
                </div>

                <div class="flex items-center gap-5">
                    <div class="relative">
                        <button type="button" id="notification-bell-btn" data-url="{{ route('admin.notifications.index') }}" class="relative p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-full transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                            <span id="notification-dot" class="hidden absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                        </button>
                    </div>
                    <div class="h-8 w-px bg-slate-200"></div>
                    <div class="flex items-center gap-3 pl-1">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-slate-800 leading-none">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-slate-400 uppercase font-medium mt-1">{{ ucfirst(auth()->user()->role) }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center text-xs font-bold shadow-sm"
                            title="{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})">
                            {{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => strtoupper($part[0] ?? ''))->take(2)->implode('') }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-8 bg-slate-50 custom-scrollbar">
                @yield('content')
            </main>
        </div>

        <div id="notification-dropdown" class="hidden fixed w-80 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[9999] overflow-hidden transition-all duration-200 ease-in-out opacity-0 scale-95">
            <div class="px-4 py-4 bg-slate-50 border-b flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800">Notifications</h3>
                <button type="button" id="notification-mark-read" class="text-xs font-semibold text-amber-600 hover:text-amber-700 transition-colors">Mark all as read</button>
            </div>
            <div id="notification-list" class="divide-y max-h-96 overflow-y-auto bg-white"></div>
            <p id="notification-empty" class="hidden px-4 py-8 text-center text-sm text-slate-400">No alerts right now.</p>
            <div class="px-4 py-4 bg-slate-50 border-t text-center">
                <a href="{{ route('admin.notifications.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-700 transition-colors">View all notifications</a>
            </div>
        </div>

        {{-- Global confirmation modal --}}
        <div id="confirmation-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4 transition-all duration-200">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-95 opacity-0" id="confirmation-modal-panel">
                <div class="p-8 text-center">
                    <div id="confirmation-modal-icon-wrap" class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-6 transition-colors duration-200">
                        <svg id="confirmation-modal-icon" class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"></svg>
                    </div>
                    <h3 id="confirmation-modal-title" class="text-xl font-bold text-slate-900 mb-2">Confirm Action</h3>
                    <p id="confirmation-modal-message" class="text-sm text-slate-500 leading-relaxed px-4"></p>
                </div>

                <div class="px-6 py-6 bg-slate-50 flex flex-col sm:flex-row-reverse gap-3">
                    <button type="button" id="confirmation-modal-confirm" class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-sm font-bold text-white transition-all duration-200 shadow-sm hover:shadow-md active:scale-95">
                        Confirm
                    </button>
                    <button type="button" id="confirmation-modal-cancel" class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-200 transition-all duration-200">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2 pointer-events-none"></div>

        @if (session('success'))
            <script>
                (function() {
                    const triggerToast = () => showToast(@json(session('success')), 'success');
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', triggerToast);
                    } else {
                        triggerToast();
                    }
                })();
            </script>
        @endif
        @if (session('error'))
            <script>
                (function() {
                    const triggerToast = () => showToast(@json(session('error')), 'error');
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', triggerToast);
                    } else {
                        triggerToast();
                    }
                })();
            </script>
        @endif

        <script>
            (function() {
                // --- Sidebar Collapse Logic ---
                const sidebar = document.getElementById('main-sidebar');
                const collapseBtn = document.getElementById('sidebar-collapse-btn');
                const collapseIcon = document.getElementById('collapse-icon');

                window.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => {
                        sidebar.classList.remove('no-transition');
                    }, 100);
                });

                const getCookie = (name) => {
                    const value = `; ${document.cookie}`;
                    const parts = value.split(`; ${name}=`);
                    if (parts.length === 2) return parts.pop().split(';').shift();
                };

                let isCollapsed = getCookie('sidebar_collapsed') === 'true';

                if (isCollapsed) {
                    collapseIcon.classList.add('rotate-180');
                }

                collapseBtn.addEventListener('click', () => {
                    isCollapsed = !isCollapsed;
                    const date = new Date();
                    date.setFullYear(date.getFullYear() + 1);
                    document.cookie = `sidebar_collapsed=${isCollapsed}; expires=${date.toUTCString()}; path=/; SameSite=Lax`;

                    if (isCollapsed) {
                        // COLLAPSE: Hide text instantly, then shrink width
                        document.querySelectorAll('.sidebar-text').forEach(el => {
                            el.classList.add('opacity-0', 'pointer-events-none');
                        });
                        sidebar.classList.replace('w-64', 'w-20');
                        collapseIcon.classList.add('rotate-180');
                    } else {
                        // EXPAND: Expand width first, then fade in text
                        sidebar.classList.replace('w-20', 'w-64');
                        collapseIcon.classList.remove('rotate-180');

                        document.querySelectorAll('.sidebar-text').forEach(el => {
                            el.classList.remove('pointer-events-none');
                            setTimeout(() => {
                                el.classList.remove('opacity-0');
                            }, 100);
                        });
                    }
                });


                // --- Notifications Logic ---
                function initNotifications() {
                    const bellBtn = document.getElementById('notification-bell-btn');
                    const dropdown = document.getElementById('notification-dropdown');
                    const list = document.getElementById('notification-list');
                    const emptyMsg = document.getElementById('notification-empty');
                    const markReadBtn = document.getElementById('notification-mark-read');
                    const dot = document.getElementById('notification-dot');

                    if (!bellBtn || !dropdown) return;

                    function setDropdownState(isOpen) {
                        if (isOpen) {
                            dropdown.classList.remove('hidden', 'opacity-0', 'scale-95');
                            dropdown.classList.add('opacity-100', 'scale-100');
                            dropdown.style.display = 'block';
                            dropdown.style.visibility = 'visible';
                            dropdown.style.zIndex = '99999';
                        } else {
                            dropdown.classList.add('hidden', 'opacity-0', 'scale-95');
                            dropdown.classList.remove('opacity-100', 'scale-100');
                            dropdown.style.display = 'none';
                            dropdown.style.visibility = 'hidden';
                        }
                    }

                    function toggleDropdown() {
                        const isHidden = dropdown.classList.contains('hidden');
                        if (isHidden) {
                            const rect = bellBtn.getBoundingClientRect();
                            const dropdownWidth = 320;
                            dropdown.style.top = `${rect.bottom + 8}px`;
                            let leftPos = rect.right - dropdownWidth;
                            if (leftPos < 16) leftPos = 16;
                            if (leftPos + dropdownWidth > window.innerWidth - 16) {
                                leftPos = window.innerWidth - dropdownWidth - 16;
                            }
                            dropdown.style.left = `${leftPos}px`;
                            setDropdownState(true);
                            fetchNotifications();
                        } else {
                            setDropdownState(false);
                        }
                    }

                    async function fetchNotifications() {
                        try {
                            const response = await fetch(bellBtn.dataset.url + '?format=json');
                            if (!response.ok) throw new Error('Network response was not ok');
                            const data = await response.json();
                            const notes = data.notifications || [];

                            if (list) {
                                list.innerHTML = '';
                                if (notes.length === 0) {
                                    if (emptyMsg) emptyMsg.classList.remove('hidden');
                                    if (dot) dot.classList.add('hidden');
                                } else {
                                    if (emptyMsg) emptyMsg.classList.add('hidden');
                                    if (dot) dot.classList.remove('hidden');
                                    notes.forEach(note => {
                                        const item = document.createElement('div');
                                        item.className = 'px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors flex gap-3';
                                        item.innerHTML = `
                                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${note.level === 'critical' ? 'bg-red-50 text-red-500' : 'bg-gray-100 text-gray-400'}">
                                                ${getIcon(note.icon)}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start mb-0.5">
                                                    <h4 class="text-xs font-bold text-gray-800 truncate">${note.title}</h4>
                                                    <span class="text-[10px] text-gray-400 whitespace-nowrap ml-2">${note.created_at}</span>
                                                </div>
                                                <p class="text-xs text-gray-500 truncate">${note.message}</p>
                                            </div>
                                        `;
                                        item.onclick = (e) => {
                                            e.stopPropagation();
                                            if (note.action_url) window.location.href = note.action_url;
                                        };
                                        list.appendChild(item);
                                    });
                                }
                            }
                        } catch (err) {
                            console.error('Notifications fetch failed', err);
                        }
                    }

                    function getIcon(type) {
                        const icons = {
                            'shopping-cart': '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293a1 1 0 00.707 1.707H17a1 1 0 00.707-1.707L15.414 13M7 13a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v6a2 2 0 01-2 2h-2" /></svg>',
                            'warning': '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.032 0 1.847-1.15 1.463-2.17a12.17 12.17 0 00-1.463-4.32c-.372-1.72-1.452-3.13-2.882-3.92L12 3l-3.118 3.38a12.17 12.17 0 00-2.882 3.92c-.372 1.72-1.452 3.13-2.882 3.92l-3.118 3.38" /></svg>',
                            'info': '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                            'check': '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                        };
                        return icons[type] || icons['info'];
                    }

                    bellBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        toggleDropdown();
                    });

                    document.addEventListener('click', (e) => {
                        if (!dropdown.classList.contains('hidden') && !dropdown.contains(e.target) && e.target !== bellBtn) {
                            setDropdownState(false);
                        }
                    });

                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && !dropdown.classList.contains('hidden')) {
                            setDropdownState(false);
                        }
                    });

                    if (markReadBtn) {
                        markReadBtn.addEventListener('click', async (e) => {
                            e.stopPropagation();
                            try {
                                const response = await fetch('{{ route('admin.notifications.markRead') }}', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                });
                                if (response.ok) {
                                    showToast('All notifications marked as read', 'success');
                                    fetchNotifications();
                                }
                            } catch (err) {
                                showToast('Failed to mark as read', 'error');
                            }
                        });
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initNotifications);
                } else {
                    initNotifications();
                }
            })();
        </script>
    </div>
</body>
</html>