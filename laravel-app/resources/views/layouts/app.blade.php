<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Desi Zaika') | DesiFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
<div class="flex h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-gray-900 text-gray-300 flex flex-col">
    <div class="px-5 py-5">
        <p class="text-lg font-bold text-white">Desi Zaika</p>
        <p class="text-xs text-gray-400">Restaurant Admin</p>
    </div>

    <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
        {{-- TO DO: MIGUEL  --}}
        <a href="{{ route('admin.dashboard.index') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.dashboard.index') ? 'bg-amber-500 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012 2.25v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
            Dashboard
        </a>

        <a href="{{ route('admin.orders.index') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.orders.*') ? 'bg-amber-500 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M15.75 3v1.5M3 15h18M4.5 6h15A1.5 1.5 0 0121 7.5v10.5a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 18V7.5A1.5 1.5 0 014.5 6z" /></svg>
            Real-Time Orders
        </a>

        <a href="{{ route('admin.menu.index') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.menu.*') ? 'bg-amber-500 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
            Menu Management
        </a>

        <a href="{{ route('admin.inventory.index') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.inventory.*') ? 'bg-amber-500 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
            Inventory
        </a>

        <a href="{{ route('admin.staff.index') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.staff.*') ? 'bg-amber-500 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
            Staff Management
        </a>

        {{-- TO DO: MIGUEL  --}}
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-500 cursor-not-allowed" title="Coming soon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
            Sales Reports
        </a>

        <a href="{{ route('admin.analytics.index') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.analytics.*') ? 'bg-amber-500 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
            Analytics
        </a>
    </nav>

    <div class="px-3 pb-4 border-t border-gray-800 pt-3 space-y-1">
        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-gray-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Settings
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-gray-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                Logout
            </button>
        </form>
    </div>
</aside>

<!-- Main Content -->
<div class="flex-1 flex flex-col overflow-hidden">

    <!-- Top bar -->
    <header class="bg-white border-b px-6 py-3 flex items-center justify-end">
        <div class="flex items-center gap-4">
            <div class="relative">
                <button type="button" id="notification-bell-btn" data-url="{{ route('admin.notifications.index') }}" class="relative text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                    <span id="notification-dot" class="hidden absolute -top-0.5 -right-0.5 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
            </div>
            <button class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
            </button>
            <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs font-semibold"
                title="{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})">
                {{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => strtoupper($part[0] ?? ''))->take(2)->implode('') }}
            </div>
        </div>
    </header>

    <!-- Page content -->
    <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
    </main>
</div>
</div>

<div id="notification-dropdown" class="hidden fixed w-80 bg-white border rounded-xl shadow-2xl z-[9999] overflow-hidden transition-all duration-200 ease-in-out opacity-0 scale-95">
    <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
        <h3 class="text-sm font-bold text-gray-800">Notifications</h3>
        <button type="button" id="notification-mark-read" class="text-xs font-semibold text-amber-600 hover:text-amber-700 transition-colors">Mark all as read</button>
    </div>
    <div id="notification-list" class="divide-y max-h-96 overflow-y-auto bg-white"></div>
    <p id="notification-empty" class="hidden px-4 py-8 text-center text-sm text-gray-400">No alerts right now.</p>
    <div class="px-4 py-3 bg-gray-50 border-t text-center">
        <a href="{{ route('admin.notifications.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-700 transition-colors">View all notifications</a>
    </div>
</div>

{{-- Global confirmation modal --}}
<div id="confirmation-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4 transition-all duration-200">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-95 opacity-0" id="confirmation-modal-panel">
        <div class="p-8 text-center">
            {{-- Icon Container --}}
            <div id="confirmation-modal-icon-wrap" class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-6 transition-colors duration-200">
                <svg id="confirmation-modal-icon" class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"></svg>
            </div>

            <h3 id="confirmation-modal-title" class="text-xl font-bold text-gray-900 mb-2">Confirm Action</h3>
            <p id="confirmation-modal-message" class="text-sm text-gray-500 leading-relaxed px-4"></p>
        </div>

        <div class="px-6 py-6 bg-gray-50 flex flex-col sm:flex-row-reverse gap-3">
            <button type="button" id="confirmation-modal-confirm" class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-sm font-bold text-white transition-all duration-200 shadow-sm hover:shadow-md active:scale-95">
                Confirm
            </button>
            <button type="button" id="confirmation-modal-cancel" class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-200 transition-all duration-200">
                Cancel
            </button>
        </div>
    </div>
</div>

{{-- Global toast container. The actual showToast()/submitAjaxForm()/submitAjaxAction()
     implementations live in resources/js/admin.js (bundled via Vite below), so every
     page shares one copy instead of each page defining its own. --}}
<div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2 pointer-events-none"></div>

{{-- This tiny bit has to stay here rather than in admin.js: it's bridging a
     server-side Laravel flash message into a client-side showToast() call,
     which only Blade can do. Everything else toast-related lives in admin.js. --}}
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
            function initNotifications() {
                console.log('🔔 Notification system initializing...');
                const bellBtn = document.getElementById('notification-bell-btn');
                const dropdown = document.getElementById('notification-dropdown');
                const list = document.getElementById('notification-list');
                const emptyMsg = document.getElementById('notification-empty');
                const markReadBtn = document.getElementById('notification-mark-read');
                const dot = document.getElementById('notification-dot');

                console.log('Bell button found:', !!bellBtn);
                console.log('Dropdown found:', !!dropdown);

                if (!bellBtn || !dropdown) {
                    console.error('🔔 Notification initialization failed: bellBtn or dropdown not found');
                    return;
                }

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
                                if (emptyMsg) {
                                    emptyMsg.classList.remove('hidden');
                                }
                                if (dot) {
                                    dot.classList.add('hidden');
                                }
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
                    console.log('🚀 NOTIFICATION BELL CLICKED');
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

</body>
</html>