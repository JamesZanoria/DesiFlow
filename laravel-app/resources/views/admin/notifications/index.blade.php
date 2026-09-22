@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="flex items-start justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
            <p class="text-sm text-gray-500 mt-1">Manage and review your system alerts and activity history.</p>
        </div>

        <button type="button" id="mark-all-read-btn"
            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Mark all as read
        </button>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex items-center gap-1 border-b border-gray-200 mb-6 overflow-x-auto">
        @php
            $tabs = [
                'all' => 'All Notifications',
                'orders' => 'Orders',
                'inventory' => 'Inventory',
                'system' => 'System'
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.notifications.index', ['type' => $key]) }}"
               class="px-4 py-2 text-sm font-medium transition-colors relative {{ $selectedType === $key ? 'text-amber-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
                @if($selectedType === $key)
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-amber-500"></div>
                @endif
            </a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($notifications as $note)
            <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-start gap-4 hover:border-amber-200 transition-colors duration-200 shadow-sm">
                {{-- Icon Column --}}
                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{
                    $note['level'] === 'critical' ? 'bg-red-50 text-red-500' : 'bg-gray-50 text-gray-400'
                }}">
                    @if($note['icon'] === 'shopping-cart')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293a1 1 0 00.707 1.707H17a1 1 0 00.707-1.707L15.414 13M7 13a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v6a2 2 0 01-2 2h-2" /></svg>
                    @elseif($note['icon'] === 'warning')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.032 0 1.847-1.15 1.463-2.17a12.17 12.17 0 00-1.463-4.32c-.372-1.72-1.452-3.13-2.882-3.92L12 3l-3.118 3.38a12.17 12.17 0 00-2.882 3.92c-.372 1.72-1.452 3.13-2.882 3.92l-3.118 3.38" /></svg>
                    @elseif($note['icon'] === 'info')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    @elseif($note['icon'] === 'check')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    @endif
                </div>

                {{-- Content Column --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-bold text-gray-900 truncate">{{ $note['title'] }}</h4>
                            @if($note['level'] === 'critical')
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-600 uppercase tracking-tighter">Critical</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ $note['created_at']->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-gray-500 leading-relaxed mb-3">{{ $note['message'] }}</p>

                    @if($note['action_label'])
                        <a href="{{ $note['action_url'] }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-amber-600 border border-amber-200 hover:bg-amber-50 transition-colors">
                            {{ $note['action_label'] }}
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-gray-300 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                </div>
                <p class="text-sm text-gray-500">No notifications found for this filter.</p>
            </div>
        @endforelse
    </div>

    <script>
        document.getElementById('mark-all-read-btn').addEventListener('click', async () => {
            try {
                const response = await fetch('{{ route('admin.notifications.markRead') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    // In a real app, we would refresh the list or clear notifications here
                    window.location.reload();
                }
            } catch (err) {
                showToast('Failed to mark notifications as read', 'error');
            }
        });
    </script>
@endsection