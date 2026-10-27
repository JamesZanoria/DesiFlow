<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Order Module') | DesiFlow</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900">
    <header class="sticky top-0 z-30 border-b border-slate-800 bg-slate-900 text-slate-200 shadow-lg shadow-slate-900/10">
        <div class="mx-auto flex min-h-16 max-w-screen-2xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="@yield('home-url', url('/'))" class="flex shrink-0 items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-[10px] font-extrabold text-white shadow-lg shadow-amber-900/20">DZ</span>
                <span class="hidden font-bold text-white sm:inline">Desi Zaika <span class="font-normal text-slate-400">/ @yield('module-name', 'Orders')</span></span>
            </a>
            <div class="flex items-center gap-3 sm:gap-5">
                @if (auth()->user()?->role === 'cashier')
                    <nav class="flex items-center gap-1 text-xs font-semibold sm:gap-2 sm:text-sm" aria-label="Cashier navigation">
                        <a href="{{ route('cashier.index') }}" @class(['rounded-lg px-2.5 py-2 transition-colors sm:px-3', 'bg-amber-500 text-white shadow-sm' => request()->routeIs('cashier.index'), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('cashier.index')])>Dashboard</a>
                        <a href="{{ route('cashier.orders.index') }}" data-cashier-popup="Active orders" @class(['hidden rounded-lg px-2.5 py-2 transition-colors md:inline-flex md:px-3', 'bg-amber-500 text-white shadow-sm' => request()->routeIs('cashier.orders.index'), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('cashier.orders.index')])>Active orders</a>
                        <a href="{{ route('cashier.menu') }}" @class(['rounded-lg px-2.5 py-2 transition-colors sm:px-3', 'bg-amber-500 text-white shadow-sm' => request()->routeIs('cashier.menu'), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('cashier.menu')])>New order</a>
                        <a href="{{ route('cashier.transactions.index') }}" data-cashier-popup="Transaction history" @class(['hidden rounded-lg px-2.5 py-2 transition-colors md:inline-flex md:px-3', 'bg-amber-500 text-white shadow-sm' => request()->routeIs('cashier.transactions.*'), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('cashier.transactions.*')])>History</a>
                    </nav>
                @endif
                <span class="hidden border-l border-slate-700 pl-4 text-xs font-medium text-slate-300 lg:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-red-400/50 hover:bg-red-500/10 hover:text-red-300 sm:text-sm">Sign out</button>
                </form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-screen-2xl px-4 py-5 sm:px-6 sm:py-7">
        @if (session('status'))
            <div role="status" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    @if (auth()->user()?->role === 'cashier')
        <section id="cashier-popup" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-950/60 p-2 sm:p-4" role="dialog" aria-modal="true" aria-label="Cashier page">
            <div class="relative w-full max-w-7xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl sm:rounded-2xl">
                <button type="button" id="cashier-popup-close" class="absolute right-2 top-2 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-2xl leading-none text-gray-500 shadow hover:bg-white hover:text-gray-900 sm:right-3 sm:top-3" aria-label="Close popup">&times;</button>
                <p id="cashier-popup-loading" class="absolute inset-0 z-[5] flex items-center justify-center bg-white text-sm text-gray-500">Loading...</p>
                <iframe id="cashier-popup-frame" title="Cashier page" class="block w-full border-0 bg-gray-100" src="about:blank"></iframe>
            </div>
        </section>
        <script>
            (() => {
                if (window.self !== window.top) return;

                const modal = document.getElementById('cashier-popup');
                const frame = document.getElementById('cashier-popup-frame');
                const loading = document.getElementById('cashier-popup-loading');
                const closeButton = document.getElementById('cashier-popup-close');
                let previouslyFocused = null;
                let contentObserver = null;

                const resizeFrame = () => {
                    try {
                        const document = frame.contentDocument;
                        if (!document?.body || !document.documentElement) return;

                        document.body.querySelector(':scope > header')?.remove();
                        document.body.classList.remove('min-h-screen');
                        const contentHeight = Math.max(
                            document.body.scrollHeight,
                            document.documentElement.scrollHeight
                        );
                        const availableHeight = Math.max(240, window.innerHeight - 32);
                        frame.style.height = `${Math.min(contentHeight, availableHeight)}px`;
                    } catch (error) {
                        console.error('Could not resize the cashier popup.', error);
                    }
                };

                const close = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    frame.src = 'about:blank';
                    frame.style.height = '0px';
                    contentObserver?.disconnect();
                    contentObserver = null;
                    document.body.classList.remove('overflow-hidden');
                    previouslyFocused?.focus();
                };

                document.addEventListener('click', (event) => {
                    const link = event.target.closest('[data-cashier-popup]');
                    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                    if (new URL(link.href, window.location.href).pathname === window.location.pathname) return;

                    event.preventDefault();
                    previouslyFocused = link;
                    loading.classList.remove('hidden');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                    frame.style.height = '240px';
                    frame.src = link.href;
                });

                frame.addEventListener('load', () => {
                    loading.classList.add('hidden');
                    contentObserver?.disconnect();
                    resizeFrame();
                    try {
                        const content = frame.contentDocument;
                        if (content?.body && 'ResizeObserver' in window) {
                            contentObserver = new ResizeObserver(resizeFrame);
                            contentObserver.observe(content.body);
                            contentObserver.observe(content.documentElement);
                        }
                    } catch (error) {
                        console.error('Could not observe cashier popup content.', error);
                    }
                });
                window.addEventListener('resize', resizeFrame);
                closeButton.addEventListener('click', close);
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) close();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
                });
            })();
        </script>
    @endif
</body>
</html>
