@extends('layouts.module')

@section('title', 'Kitchen Display')
@section('module-name', 'Kitchen Display')
@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Kitchen Display System</h1>
            <p class="mt-1 text-sm text-gray-500">Update each ticket as the kitchen works on it.</p>
        </div>
        <span id="kds-last-updated" class="text-xs text-gray-500">Live refresh enabled</span>
    </div>
    <div id="kds-message" class="mb-4 hidden rounded-lg border px-4 py-3 text-sm" role="status"></div>
    <div id="kds-board">
        @include('kds._board', ['columns' => $columns])
    </div>
    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const board = document.getElementById('kds-board');
            const message = document.getElementById('kds-message');
            let busy = false;

            function showMessage(text, error = false) {
                message.textContent = text;
                message.className = `mb-4 rounded-lg border px-4 py-3 text-sm ${error ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800'}`;
                message.classList.remove('hidden');
            }

            async function refresh() {
                if (busy) return;
                try {
                    const response = await fetch('{{ route('kds.index') }}', { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Could not refresh the kitchen board.');
                    board.innerHTML = data.html;
                    document.getElementById('kds-last-updated').textContent = `Updated ${new Date().toLocaleTimeString()}`;
                } catch (error) {
                    showMessage(error.message, true);
                }
            }

            board.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-next-status]');
                if (!button || busy) return;
                busy = true;
                button.disabled = true;
                try {
                    const response = await fetch(button.dataset.url, {
                        method: 'PATCH',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ status: button.dataset.nextStatus }),
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Could not update this order.');
                    message.classList.add('hidden');
                    await refresh();
                } catch (error) {
                    showMessage(error.message, true);
                    button.disabled = false;
                } finally {
                    busy = false;
                }
            });

            window.setInterval(refresh, 5000);
        })();
    </script>
@endsection
