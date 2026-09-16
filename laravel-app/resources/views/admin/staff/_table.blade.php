{{--
    Table + pagination fragment for Staff Accounts Management.

    Rendered two ways from StaffController@index:
      1. Normal page load , included directly inside admin.staff.index.
      2. AJAX refresh , returned alone (as HTML inside a JSON envelope) after
         an Add/Edit/Delete, or when a Prev/Next pagination link is clicked.
         This keeps pagination accurate automatically, without a manual reload.
--}}
@php
    $roleBadge = [
        'admin' => 'bg-indigo-100 text-indigo-700',
        'cashier' => 'bg-blue-100 text-blue-700',
        'kitchen' => 'bg-amber-100 text-amber-700',
    ];
    $roleLabel = [
        'admin' => 'Admin / Manager',
        'cashier' => 'Cashier',
        'kitchen' => 'Kitchen Staff',
    ];
    // Deterministic color per name
    $avatarPalette = ['bg-amber-500', 'bg-blue-500', 'bg-purple-500', 'bg-emerald-500', 'bg-rose-500', 'bg-indigo-500'];
@endphp
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left border-collapse">
        <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider font-semibold border-b border-gray-200">
            <tr class="h-10">
                <th class="px-6 py-4 font-semibold">Name</th>
                <th class="px-6 py-4 font-semibold">Email</th>
                <th class="px-6 py-4 font-semibold">Role</th>
                <th class="px-6 py-4 font-semibold text-right">Actions</th>
            </tr>
        </thead>
        <tbody id="staff-table-body" class="divide-y divide-gray-100">
            @forelse ($staff as $member)
                @php
                    $avatarColor = $avatarPalette[crc32($member->name) % count($avatarPalette)];
                @endphp
                <tr id="staff-row-{{ $member->user_id }}" class="hover:bg-gray-50 transition-colors duration-150 group">
                    <td class="px-6 py-4 cell-name">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full {{ $avatarColor }} text-white flex items-center justify-center text-xs font-semibold flex-shrink-0 shadow-sm">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            <span class="font-semibold text-gray-900">{{ $member->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-500 cell-email">{{ $member->email }}</td>
                    <td class="px-6 py-4 cell-role">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $roleBadge[$member->role] ?? 'bg-gray-100 text-gray-700' }} border border-transparent">
                            {{ $roleLabel[$member->role] ?? ucfirst($member->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if($member->role !== 'admin')
                            <div class="flex justify-end gap-1">
                                <button type="button"
                                    class="edit-staff-btn p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                    title="Edit"
                                    data-id="{{ $member->user_id }}"
                                    data-name="{{ $member->name }}"
                                    data-email="{{ $member->email }}"
                                    data-role="{{ $member->role }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                </button>
                                <button type="button"
                                    class="delete-staff-btn p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    title="Delete"
                                    data-id="{{ $member->user_id }}"
                                    data-name="{{ $member->name }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                                </button>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr id="staff-empty-row">
                    <td colspan="4" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H3" /></svg>
                            <p class="text-sm">{{ request('search') || request('role') ? 'No staff match your search or filter.' : 'No staff accounts yet. Click "Add Staff" to create one.' }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50/30">
        <p id="staff-pagination-summary" class="text-xs text-gray-500 font-medium uppercase tracking-wider">
            Showing {{ $staff->firstItem() ?? 0 }} to {{ $staff->lastItem() ?? 0 }} of {{ $staff->total() }} staff
        </p>
        <div class="flex gap-2">
            <a href="{{ $staff->onFirstPage() ? '#' : $staff->previousPageUrl() }}"
                data-ajax-page="prev"
                class="pagination-link p-2 rounded-lg border border-gray-200 bg-white transition-all {{ $staff->onFirstPage() ? 'text-gray-300 border-gray-100 pointer-events-none' : 'text-gray-600 hover:bg-gray-50 hover:border-gray-300' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </a>
            <a href="{{ $staff->hasMorePages() ? $staff->nextPageUrl() : '#' }}"
                data-ajax-page="next"
                class="pagination-link p-2 rounded-lg border border-gray-200 bg-white transition-all {{ $staff->hasMorePages() ? 'text-gray-600 hover:bg-gray-50 hover:border-gray-300' : 'text-gray-300 border-gray-100 pointer-events-none' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        </div>
    </div>
</div>