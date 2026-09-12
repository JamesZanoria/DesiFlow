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
@endphp
<div class="bg-white border rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="text-gray-500 text-xs border-b">
            <tr>
                <th class="px-4 py-3 font-medium">ID</th>
                <th class="px-4 py-3 font-medium">Name</th>
                <th class="px-4 py-3 font-medium">Email</th>
                <th class="px-4 py-3 font-medium">Role</th>
                <th class="px-4 py-3 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody id="staff-table-body" class="divide-y">
            @forelse ($staff as $member)
                <tr id="staff-row-{{ $member->user_id }}">
                    <td class="px-4 py-3 text-gray-400 cell-id">#{{ $member->user_id }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-800 cell-name">{{ $member->name }}</td>
                    <td class="px-4 py-3 text-gray-600 cell-email">{{ $member->email }}</td>
                    <td class="px-4 py-3 cell-role">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $roleBadge[$member->role] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $roleLabel[$member->role] ?? ucfirst($member->role) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button type="button"
                            class="edit-staff-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                            title="Edit"
                            data-id="{{ $member->user_id }}"
                            data-name="{{ $member->name }}"
                            data-email="{{ $member->email }}"
                            data-role="{{ $member->role }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                        </button>
                        <button type="button"
                            class="delete-staff-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50"
                            title="Delete"
                            data-id="{{ $member->user_id }}"
                            data-name="{{ $member->name }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                        </button>
                    </td>
                </tr>
            @empty
            @endforelse
        </tbody>
    </table>
    <p id="staff-empty-state" class="px-4 py-6 text-center text-gray-400 {{ $staff->isNotEmpty() ? 'hidden' : '' }}">
        @if (request('search') || request('role'))
            No staff match your search or filter.
        @else
            No staff accounts yet. Click "Add Staff" to create one.
        @endif
    </p>

    <div class="flex items-center justify-between px-4 py-3 border-t">
        <p id="staff-pagination-summary" class="text-sm text-gray-500">
            Showing {{ $staff->firstItem() ?? 0 }} to {{ $staff->lastItem() ?? 0 }} of {{ $staff->total() }} staff
        </p>
        <div class="flex gap-2">
            <a href="{{ $staff->onFirstPage() ? '#' : $staff->previousPageUrl() }}"
                data-ajax-page="prev"
                class="pagination-link p-2 rounded-lg border {{ $staff->onFirstPage() ? 'text-gray-300 border-gray-200 pointer-events-none' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </a>
            <a href="{{ $staff->hasMorePages() ? $staff->nextPageUrl() : '#' }}"
                data-ajax-page="next"
                class="pagination-link p-2 rounded-lg border {{ $staff->hasMorePages() ? 'text-gray-600 border-gray-300 hover:bg-gray-50' : 'text-gray-300 border-gray-200 pointer-events-none' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        </div>
    </div>
</div>