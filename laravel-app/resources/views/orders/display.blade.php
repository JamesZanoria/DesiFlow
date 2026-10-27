<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Pickup Display | DesiFlow</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-950 text-white">
    <main class="mx-auto max-w-screen-2xl px-6 py-10">
        <header class="mb-8 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-amber-400">Desi Zaika</p>
                <h1 class="mt-2 text-4xl font-black sm:text-5xl">Order pickup</h1>
            </div>
            <p id="display-updated" class="text-sm text-gray-400">Connecting...</p>
        </header>
        <div id="display-message" class="mb-5 hidden rounded-lg border border-red-700 bg-red-950 px-4 py-3 text-sm text-red-200" role="alert"></div>
        <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <h2 class="mb-4 border-b border-gray-800 pb-3 text-xl font-bold text-amber-300">Preparing</h2>
                <div id="display-pending" class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
            </div>
            <div>
                <h2 class="mb-4 border-b border-gray-800 pb-3 text-xl font-bold text-green-300">Ready for pickup</h2>
                <div id="display-ready" class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
            </div>
        </section>
    </main>
    <script>
        (() => {
            const message = document.getElementById('display-message');
            function render(container, orders, ready) {
                container.replaceChildren();
                orders.forEach((order) => {
                    const card = document.createElement('article');
                    card.className = `rounded-xl border p-4 text-center ${ready ? 'border-green-800 bg-green-950/50' : 'border-gray-700 bg-gray-900'}`;
                    const code = document.createElement('p');
                    code.className = `mt-1 font-mono text-4xl font-black tracking-widest ${ready ? 'text-green-300' : 'text-amber-300'}`;
                    code.textContent = order.code;
                    card.append(code);
                    container.append(card);
                });
                if (!orders.length) {
                    const empty = document.createElement('p');
                    empty.className = 'col-span-full py-6 text-sm text-gray-500';
                    empty.textContent = ready ? 'No orders are ready for pickup.' : 'No orders are being prepared.';
                    container.append(empty);
                }
            }
            async function refresh() {
                try {
                    const response = await fetch('{{ route('display.orders') }}', { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Could not load pickup orders.');
                    render(document.getElementById('display-pending'), data.orders.filter((order) => order.status !== 'ready'), false);
                    render(document.getElementById('display-ready'), data.orders.filter((order) => order.status === 'ready'), true);
                    document.getElementById('display-updated').textContent = `Updated ${new Date().toLocaleTimeString()}`;
                    message.classList.add('hidden');
                } catch (error) {
                    message.textContent = error.message;
                    message.classList.remove('hidden');
                }
            }
            refresh();
            window.setInterval(refresh, 5000);
        })();
    </script>
</body>
</html>
