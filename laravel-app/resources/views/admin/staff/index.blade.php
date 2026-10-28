@extends('layouts.app')

@section('title', 'Staff Management')

@section('content')
    <div class="overflow-hidden flex flex-col p-2 space-y-2">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-0 gap-4 flex-none">
            <div class="w-full md:w-auto">
                <h1 class="text-2xl font-bold text-gray-800">Staff Management</h1>
                <p class="text-sm text-gray-500 mt-1 mb-3">Manage staff accounts and their assigned roles.</p>
            </div>
            <a href="{{ route('admin.staff.create') }}"
                class="w-full md:w-auto flex items-center justify-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 whitespace-nowrap transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Staff
            </a>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (window.showToast) {
                        window.showToast("{{ session('success') }}", 'success');
                    }
                });
            </script>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-3 animate-in fade-in slide-in-from-top-2 duration-300">
                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                {{ $errors->first() }}
            </div>
        @endif

    <div id="staff-table-container" class="flex-1 w-full min-w-0 overflow-visible block">
        @include('admin.staff._table')
    </div>

    <script>
        (function () {
            function tableContainer() { return document.getElementById('staff-table-container'); }

            function toggleStaffMenu(btn, menuId) {
                const menu = document.getElementById(menuId);
                if (!menu) return;

                // Close all other open menus first
                document.querySelectorAll('.action-menu-dropdown').forEach(m => {
                    if (m.id !== menuId) {
                        m.classList.add('opacity-0', 'invisible');
                        m.classList.remove('opacity-100', 'visible');
                    }
                });

                // Toggle current menu
                if (menu.classList.contains('opacity-0')) {
                    menu.classList.remove('opacity-0', 'invisible');
                    menu.classList.add('opacity-100', 'visible');
                } else {
                    menu.classList.add('opacity-0', 'invisible');
                    menu.classList.remove('opacity-100', 'visible');
                }
            }
            window.toggleStaffMenu = toggleStaffMenu;

            async function refreshTable(url) {
                const targetUrl = url || window.location.href;
                try {
                    const response = await fetch(targetUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const data = await response.json();
                    const container = tableContainer();
                    container.innerHTML = data.html;
                    attachRowListeners(container);
                    attachPaginationListeners(container);
                } catch (err) { console.error(err); }
            }
            function attachPaginationListeners(scope) {
                scope.querySelectorAll('.pagination-link').forEach((link) => {
                    if (link.classList.contains('pointer-events-none')) return;
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const url = link.getAttribute('href');
                        refreshTable(url);
                    });
                });
            }
            function attachRowListeners(scope) {
                scope.querySelectorAll('.delete-staff-btn').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        submitAjaxAction({
                            url: `{{ url('admin/staff') }}/${btn.dataset.id}`,
                            method: 'DELETE',
                            confirmMessage: {
                                title: 'Delete Staff?',
                                message: `Are you sure you want to delete this staff account? This action cannot be undone.`,
                                actionLabel: 'Delete',
                                type: 'danger'
                            },
                            onSuccess: () => {
                                const card = document.getElementById(`staff-card-${btn.dataset.id}`);
                                if (card) {
                                    card.classList.add('opacity-0', 'scale-95', 'pointer-events-none', 'transition-all', 'duration-300');
                                    setTimeout(() => card.remove(), 300);
                                }
                                refreshTable(window.location.href);
                            },
                        });
                    });
                });
            }

            // Global click listener to close menus when clicking outside
            document.addEventListener('click', () => {
                document.querySelectorAll('.action-menu-dropdown').forEach(m => {
                    m.classList.add('opacity-0', 'invisible');
                    m.classList.remove('opacity-100', 'visible');
                });
            });

            attachRowListeners(tableContainer());
            attachPaginationListeners(tableContainer());
        })();
    </script>
@endsection