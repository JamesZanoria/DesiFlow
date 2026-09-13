@extends('layouts.app')

@section('title', 'Staff Management')

@section('content')

    <div class="flex items-start justify-between mb-4 gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Staff Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage staff accounts and their assigned roles.</p>
        </div>

        <form method="GET" action="{{ route('admin.staff.index') }}" class="flex items-center gap-2">
            @include('admin.partials._filter_search', [
                'name' => 'search',
                'placeholder' => 'Search name or email...',
                'value' => request('search'),
                'suggestions' => $allStaffNames,
            ])

            @include('admin.partials._filter_select', [
                'name' => 'role',
                'label' => 'Role',
                'value' => request('role'),
                'options' => [
                    'admin' => 'Admin / Manager',
                    'cashier' => 'Cashier',
                    'kitchen' => 'Kitchen Staff',
                ],
            ])

            <button type="button" id="add-staff-btn"
                class="flex items-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Staff
            </button>
        </form>
    </div>

    {{-- Table + pagination now live in a shared partial, wrapped in this container so
         the JS can swap the whole thing out after Add/Edit/Delete or a Prev/Next click
         without a full browser reload, see refreshTable() below. --}}
    <div id="staff-table-container">
        @include('admin.staff._table')
    </div>

    {{-- Add / Edit popup --}}
    <div id="staff-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md my-8 max-h-[90vh] overflow-y-auto">
            <div class="bg-indigo-50 rounded-t-xl px-6 py-4 flex items-center justify-between sticky top-0">
                <h2 id="staff-modal-title" class="text-lg font-semibold text-gray-800">Add Staff</h2>
                <button type="button" id="staff-modal-close" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>

            <div class="p-6">
                    <form id="staff-form">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="name" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="e.g. Maria Santos">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" required inputmode="email" autocomplete="off"
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="name@example.com">
                        <p id="staff-email-hint" class="hidden text-xs text-red-600 mt-1">Please enter a valid email address (e.g. name@example.com).</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="">Select a role...</option>
                            <option value="admin" id="staff-role-admin-option" hidden>Admin / Manager</option>
                            <option value="cashier">Cashier</option>
                            <option value="kitchen">Kitchen Staff</option>
                        </select>
                        <p id="staff-role-admin-note" class="hidden text-xs text-gray-400 mt-1">The owner/admin role can't be reassigned here — only Cashier and Kitchen Staff accounts can be created or changed.</p>
                    </div>

                    <div class="mb-1">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <p id="staff-password-edit-hint" class="hidden text-xs text-gray-400">Leave blank to keep the current password</p>
                        </div>
                        <div class="relative">
                            <input type="password" name="password" autocomplete="new-password"
                                class="w-full border rounded-lg px-3 py-2 pr-10 text-sm" placeholder="Enter a strong password">
                            <button type="button" class="password-toggle absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" data-target="password" tabindex="-1">
                                <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Google-account-style strength meter: a segmented bar + word
                         (Weak/Fair/Good/Strong) instead of an itemized checklist.
                         Hidden until the field has content. The server-side Password
                         rule (min 10, mixed case, numbers, symbols) is the actual
                         source of truth and is re-checked on submit regardless of
                         what this shows. --}}
                    <div class="mb-4 mt-2">
                        <div id="password-strength-meter" class="hidden">
                            <div class="flex gap-1">
                                <span class="strength-segment h-1 flex-1 rounded-full bg-gray-200"></span>
                                <span class="strength-segment h-1 flex-1 rounded-full bg-gray-200"></span>
                                <span class="strength-segment h-1 flex-1 rounded-full bg-gray-200"></span>
                                <span class="strength-segment h-1 flex-1 rounded-full bg-gray-200"></span>
                            </div>
                            <p id="password-strength-label" class="text-xs mt-1 font-medium"></p>
                        </div>
                        <p id="password-strength-hint" class="hidden text-xs text-red-600 mt-1"></p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" autocomplete="new-password"
                                class="w-full border rounded-lg px-3 py-2 pr-10 text-sm" placeholder="Re-enter password">
                            <button type="button" class="password-toggle absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" data-target="password_confirmation" tabindex="-1">
                                <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        <p id="staff-confirm-hint" class="hidden text-xs text-red-600 mt-1">Passwords do not match.</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" id="staff-modal-cancel" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</button>
                        <button type="submit" id="staff-modal-save" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-amber-500">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('staff-modal');
            const form = document.getElementById('staff-form');
            const title = document.getElementById('staff-modal-title');
            const saveBtn = document.getElementById('staff-modal-save');
            const storeUrl = '{{ route('admin.staff.store') }}';
            const baseUrl = '{{ url('admin/staff') }}';
            const indexUrl = '{{ route('admin.staff.index') }}';

            // Snapshot of the values an Edit session opened with, so Save can
            // stay disabled until something actually differs from it. Stays
            // null in Create mode, where "no changes yet" isn't a meaningful
            // concept, there's nothing to compare against.
            let editSnapshot = null;

            // A typed password always counts as a change (it's the one field
            // that starts blank on purpose in Edit mode, blank means "leave
            // the current password alone", so blank never counts as a change,
            // but anything typed there does, regardless of the other fields).
            function hasUnsavedChanges() {
                if (!editSnapshot) return true;
                if (form.password.value.length > 0) return true;

                return form.name.value.trim() !== editSnapshot.name
                    || form.email.value.trim() !== editSnapshot.email
                    || form.role.value !== editSnapshot.role;
            }

            function refreshSaveButtonState() {
                saveBtn.disabled = editSnapshot !== null && !hasUnsavedChanges();
            }

            ['name', 'email', 'role', 'password'].forEach((fieldName) => {
                form.elements[fieldName].addEventListener('input', refreshSaveButtonState);
            });
            form.role.addEventListener('change', refreshSaveButtonState);

            const passwordEditHint = document.getElementById('staff-password-edit-hint');
            const emailHint = document.getElementById('staff-email-hint');
            const confirmHint = document.getElementById('staff-confirm-hint');
            const roleAdminOption = document.getElementById('staff-role-admin-option');
            const roleAdminNote = document.getElementById('staff-role-admin-note');

            // Locks the Role field to its current value when editing an
            // account that's already admin — the "admin" option is hidden
            // in every other case (create, or editing a cashier/kitchen
            // account), so there is no path in the UI that offers assigning
            // it. A native <select> has no readonly attribute (unlike
            // <input>), so "locked" is enforced by blocking the
            // interactions that would open or change it, rather than
            // `disabled` — which would also exclude it from the submitted
            // form data and break the server's required-field check.
            let roleLocked = false;

            function blockIfRoleLocked(e) {
                if (roleLocked) e.preventDefault();
            }
            form.role.addEventListener('mousedown', blockIfRoleLocked);
            form.role.addEventListener('keydown', blockIfRoleLocked);
            form.role.addEventListener('change', () => {
                if (roleLocked && form.role.value !== 'admin') {
                    form.role.value = 'admin';
                }
            });

            const errorableFields = ['name', 'email', 'role', 'password', 'password_confirmation'];

            function clearFieldErrors() {
                errorableFields.forEach((name) => {
                    form.elements[name]?.classList.remove('border-red-400', 'ring-1', 'ring-red-300', 'border-green-400');
                });
                emailTouched = false;
                passwordTouched = false;
                emailHint.classList.add('hidden');
                confirmHint.classList.add('hidden');
            }

            function errorIconSvg() {
                return `
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <circle cx="10" cy="10" r="10" />
                        <rect x="9" y="4.5" width="2" height="7" rx="1" fill="white" />
                        <circle cx="10" cy="14" r="1.15" fill="white" />
                    </svg>
                `;
            }

            function showFieldError(fieldName, message) {
                showToast({title: 'Validation Error', message: message}, 'error');

                const field = form.elements[fieldName];
                if (field) {
                    field.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                    field.focus();
                    if (field.select) field.select();
                }
            }

            // Duplicate-email errors: Add mode resets the whole form (Email keeps
            // its red outline even once emptied, as a persistent cue) so the admin
            // can immediately try a different address. Edit mode just highlights
            // the field instead, to avoid losing other changes. Mirrors Inventory's
            // showDuplicateError().
            function showDuplicateEmailError(mode) {
                showToast('Email already taken', 'error');

                if (mode === 'create') {
                    form.reset();
                    clearFieldErrors();
                    resetStrengthChecklist();
                }

                form.email.classList.remove('border-green-400');
                form.email.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                form.email.focus();
                if (mode !== 'create') form.email.select();
            }

            // ---------------------------------------------------------------
            // Live password-strength checklist
            // ---------------------------------------------------------------
            const strengthRules = {
                length: (v) => v.length >= 10,
                mixedCase: (v) => /[a-z]/.test(v) && /[A-Z]/.test(v),
                number: (v) => /[0-9]/.test(v),
                symbol: (v) => /[^A-Za-z0-9]/.test(v),
            };

            const strengthMeter = document.getElementById('password-strength-meter');
            const strengthSegments = document.querySelectorAll('#password-strength-meter .strength-segment');
            const strengthLabel = document.getElementById('password-strength-label');
            const strengthHint = document.getElementById('password-strength-hint');

            // Human-readable text for whichever rules aren't met yet, condensed
            // into one line rather than a persistent checklist, closer to how
            // Google's own "create a password" field just tells you the one
            // thing still missing rather than listing every rule at once.
            const ruleLabels = {
                length: 'at least 10 characters',
                mixedCase: 'upper & lower case letters',
                number: 'a number',
                symbol: 'a symbol (e.g. ! @ # $)',
            };

            const strengthLevels = [
                { min: 0, label: 'Too weak', barColor: 'bg-red-400', textColor: 'text-red-600', litSegments: 1 },
                { min: 1, label: 'Weak', barColor: 'bg-red-400', textColor: 'text-red-600', litSegments: 1 },
                { min: 2, label: 'Fair', barColor: 'bg-amber-400', textColor: 'text-amber-600', litSegments: 2 },
                { min: 3, label: 'Good', barColor: 'bg-yellow-400', textColor: 'text-yellow-600', litSegments: 3 },
                { min: 4, label: 'Strong', barColor: 'bg-green-500', textColor: 'text-green-600', litSegments: 4 },
            ];

            function resetStrengthChecklist() {
                strengthMeter.classList.add('hidden');
                strengthSegments.forEach((seg) => {
                    seg.className = 'strength-segment h-1 flex-1 rounded-full bg-gray-200';
                });
                strengthLabel.textContent = '';
                strengthHint.classList.add('hidden');
                strengthHint.textContent = '';
            }

            // Required check lives here rather than only at submit time: an empty
            // password field (in Create mode, or if the admin starts then clears
            // it) shows "Password is required" the same way any other required
            // field would, instead of silently doing nothing until Save is clicked.
            function updateStrengthChecklist(value) {
                const isCreate = form.dataset.mode === 'create';

                if (value.length === 0) {
                    strengthMeter.classList.add('hidden');
                    if (isCreate && passwordTouched) {
                        strengthHint.textContent = 'Password is required.';
                        strengthHint.classList.remove('hidden');
                    } else {
                        strengthHint.classList.add('hidden');
                    }
                    return;
                }

                const score = Object.values(strengthRules).filter((check) => check(value)).length;
                const level = [...strengthLevels].reverse().find((l) => score >= l.min);

                strengthMeter.classList.remove('hidden');
                strengthSegments.forEach((seg, i) => {
                    seg.className = `strength-segment h-1 flex-1 rounded-full ${i < level.litSegments ? level.barColor : 'bg-gray-200'}`;
                });
                strengthLabel.textContent = level.label;
                strengthLabel.className = `text-xs mt-1 font-medium ${level.textColor}`;

                const missing = Object.keys(strengthRules)
                    .filter((rule) => !strengthRules[rule](value))
                    .map((rule) => ruleLabels[rule]);

                if (missing.length > 0 && passwordTouched) {
                    strengthHint.textContent = `Still needs: ${missing.join(', ')}.`;
                    strengthHint.classList.remove('hidden');
                } else {
                    strengthHint.classList.add('hidden');
                }
            }

            function passwordMeetsPolicy(value) {
                return Object.values(strengthRules).every((check) => check(value));
            }

            // Same touched-gated pattern as the email field: red border (and the
            // hint above) only once the admin has left the field at least once,
            // so nothing flashes red on the very first keystroke. Green appears
            // the moment the password is strong enough, with no such delay.
            let passwordTouched = false;

            function evaluatePasswordField(showInvalid) {
                const value = form.password.value;
                form.password.classList.remove('border-red-400', 'ring-1', 'ring-red-300', 'border-green-400');

                if (value.length === 0) {
                    if (showInvalid && form.dataset.mode === 'create') {
                        form.password.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                    }
                    return;
                }

                if (passwordMeetsPolicy(value)) {
                    form.password.classList.add('border-green-400');
                } else if (showInvalid) {
                    form.password.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                }
            }

            form.password.addEventListener('input', () => {
                updateStrengthChecklist(form.password.value);
                evaluatePasswordField(passwordTouched);
                checkPasswordsMatch();
            });
            form.password.addEventListener('blur', () => {
                passwordTouched = true;
                evaluatePasswordField(true);
                updateStrengthChecklist(form.password.value);
            });

            // Confirm-password mismatch already surfaces immediately (rather than
            // waiting for blur) since a mismatch here is always meaningful the
            // moment it happens, there's no "still typing the first character"
            // ambiguity the way there is for email/password strength.
            function checkPasswordsMatch() {
                const pw = form.password.value;
                const confirm = form.password_confirmation.value;
                form.password_confirmation.classList.remove('border-red-400', 'ring-1', 'ring-red-300', 'border-green-400');

                if (confirm.length === 0) {
                    confirmHint.classList.add('hidden');
                    return;
                }

                const matches = pw === confirm;
                confirmHint.classList.toggle('hidden', matches);
                form.password_confirmation.classList.add(matches ? 'border-green-400' : 'border-red-400');
                if (!matches) {
                    form.password_confirmation.classList.add('ring-1', 'ring-red-300');
                }
            }

            form.password_confirmation.addEventListener('input', checkPasswordsMatch);

            // Show/hide toggles for both password fields.
            document.querySelectorAll('.password-toggle').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const field = form.elements[btn.dataset.target];
                    const isHidden = field.type === 'password';
                    field.type = isHidden ? 'text' : 'password';
                    btn.querySelector('.eye-open').classList.toggle('hidden', isHidden);
                    btn.querySelector('.eye-closed').classList.toggle('hidden', !isHidden);
                });
            });

            // ---------------------------------------------------------------
            // Email format feedback, soft client-side hint only; the server's
            // email:rfc,filter rule is the real source of truth.
            //
            // Deliberately doesn't touch errorBox (the banner above Full Name)
            // for a plain format problem, the red border + inline hint right
            // under the field is enough on its own, and reserves the banner
            // for things that aren't obvious from the field itself (like a
            // duplicate email, which still uses showDuplicateEmailError below).
            //
            // emailTouched delays the red state until the admin has actually
            // left the field once, so nothing turns red mid-typing on the
            // first pass, but green can appear immediately once valid, since
            // positive feedback doesn't have the same "don't be premature"
            // problem that a red error does.
            // ---------------------------------------------------------------
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            let emailTouched = false;

            function evaluateEmailField(showInvalid) {
                const value = form.email.value.trim();
                const valid = emailPattern.test(value);

                form.email.classList.remove('border-red-400', 'ring-1', 'ring-red-300', 'border-green-400');

                if (value.length === 0) {
                    emailHint.classList.add('hidden');
                    return;
                }

                if (valid) {
                    form.email.classList.add('border-green-400');
                    emailHint.classList.add('hidden');
                } else if (showInvalid) {
                    form.email.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                    emailHint.classList.remove('hidden');
                } else {
                    emailHint.classList.add('hidden');
                }
            }

            form.email.addEventListener('input', () => evaluateEmailField(emailTouched));
            form.email.addEventListener('blur', () => {
                emailTouched = true;
                evaluateEmailField(true);
            });

            // ---------------------------------------------------------------
            // Table refresh (fixes pagination going stale after Add/Edit/Delete)
            // Same pattern as Menu Management and Inventory.
            // ---------------------------------------------------------------
            function tableContainer() {
                return document.getElementById('staff-table-container');
            }

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

                    // Keeps the search suggestions list current, without
                    // this, an account just added/renamed/deleted wouldn't
                    // show up in suggestions until a real page reload.
                    if (data.staffSuggestionsHtml) {
                        const suggestList = document.querySelector('[data-search-suggest] .search-suggest-list');
                        if (suggestList) suggestList.innerHTML = data.staffSuggestionsHtml;
                    }

                    history.replaceState(null, '', targetUrl);
                } catch (err) {
                    console.error('Failed to refresh staff table', err);
                    showToast('Could not refresh the table. Please reload the page.', 'error');
                }
            }

            function attachPaginationListeners(scope) {
                scope.querySelectorAll('.pagination-link').forEach((link) => {
                    link.addEventListener('click', (e) => {
                        if (link.classList.contains('pointer-events-none')) return;
                        e.preventDefault();
                        refreshTable(link.href);
                    });
                });
            }

            function openModal(mode, data) {
                form.reset();
                clearFieldErrors();
                resetStrengthChecklist();

                if (mode === 'create') {
                    title.textContent = 'Add Staff';
                    form.dataset.mode = 'create';
                    form.dataset.userId = '';
                    form.password.required = true;
                    form.password.placeholder = 'Enter a strong password';
                    passwordEditHint.classList.add('hidden');
                    editSnapshot = null;
                    saveBtn.disabled = false;

                    roleLocked = false;
                    roleAdminOption.hidden = true;
                    roleAdminNote.classList.add('hidden');
                } else {
                    title.textContent = 'Edit Staff';
                    form.dataset.mode = 'edit';
                    form.dataset.userId = data.id;
                    form.name.value = data.name;
                    form.email.value = data.email;

                    // The admin option is only ever shown, and only ever
                    // locked, when the account being edited is already
                    // admin, an editing session for a cashier/kitchen
                    // account never even has "Admin / Manager" as a
                    // selectable option, so it can't be promoted into by
                    // accident or otherwise.
                    roleLocked = data.role === 'admin';
                    roleAdminOption.hidden = ! roleLocked;
                    roleAdminNote.classList.toggle('hidden', ! roleLocked);
                    form.role.value = data.role;

                    form.password.required = false;
                    form.password.placeholder = 'Leave blank to keep current password';
                    passwordEditHint.classList.remove('hidden');

                    // Captured after the fields above are populated, so it reflects
                    // exactly what the admin is looking at when the modal opens,
                    // Save stays disabled until something differs from this.
                    editSnapshot = {
                        name: data.name.trim(),
                        email: data.email.trim(),
                        role: data.role,
                    };
                    saveBtn.disabled = true;
                }

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            async function submitForm(e) {
                e.preventDefault();
                const mode = form.dataset.mode;
                const userId = form.dataset.userId;

                // Client-side pre-checks so an obviously-invalid submission never
                // even reaches the server, the server re-checks everything
                // regardless, this is purely to save the admin a round trip.
                const emailValue = form.email.value.trim();
                if (!emailPattern.test(emailValue)) {
                    emailTouched = true;
                    evaluateEmailField(true);
                    form.email.focus();
                    return;
                }

                const passwordValue = form.password.value;
                const isPasswordAttempt = mode === 'create' || passwordValue.length > 0;
                if (isPasswordAttempt && !passwordMeetsPolicy(passwordValue)) {
                    passwordTouched = true;
                    evaluatePasswordField(true);
                    updateStrengthChecklist(passwordValue);
                    form.password.focus();
                    return;
                }
                if (isPasswordAttempt && passwordValue !== form.password_confirmation.value) {
                    checkPasswordsMatch();
                    form.password_confirmation.focus();
                    return;
                }

                await submitAjaxForm({
                    form,
                    url: mode === 'create' ? storeUrl : `${baseUrl}/${userId}`,
                    method: mode === 'create' ? 'POST' : 'PUT',
                    toastKind: mode === 'create' ? 'success' : 'edit',
                    onSuccess: async () => {
                        // Re-fetch the current page from the server instead of
                        // patching the DOM by hand, guarantees row count, sort
                        // order, and Prev/Next state are correct immediately.
                        await refreshTable(mode === 'create' ? indexUrl : window.location.href);
                        closeModal();
                    },
                    // A 422 lands here instead of the generic bullet-list, same
                    // duplicate-vs-format detection style as Menu Management/Inventory.
                    onValidationError: (data) => {
                        const errors = data?.errors || {};

                        const duplicateMsg = errors.email?.find((m) => m.toLowerCase().includes('already registered'));
                        if (duplicateMsg) {
                            showDuplicateEmailError(mode);
                            return;
                        }

                        const invalidInputMsg = Object.values(errors).flat().find((m) => m === 'Invalid input');
                        if (invalidInputMsg) {
                            showToast('Invalid input', 'error');
                            Object.entries(errors).forEach(([field, messages]) => {
                                if (Array.isArray(messages) && messages.includes('Invalid input')) {
                                    const input = form.elements[field];
                                    if (input) {
                                        input.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                                    }
                                }
                            });
                            return;
                        }

                        if (errors.email?.length) {
                            emailTouched = true;
                            evaluateEmailField(true);
                            form.email.focus();
                            return;
                        }

                        if (errors.password?.some((m) => m.toLowerCase().includes('confirm'))) {
                            checkPasswordsMatch();
                            form.password_confirmation.focus();
                            return;
                        }

                        if (errors.password?.length) {
                            passwordTouched = true;
                            evaluatePasswordField(true);
                            updateStrengthChecklist(form.password.value);
                            form.password.focus();
                            return;
                        }

                        showToast({title: 'Submission Error', message: (data && data.message) || 'Please check the form for missing or invalid fields.'}, 'error');
                    },
                });
            }

            function deleteStaff(btn) {
                submitAjaxAction({
                    url: `${baseUrl}/${btn.dataset.id}`,
                    method: 'DELETE',
                    confirmMessage: `Remove "${btn.dataset.name}"'s staff account? This cannot be undone.`,
                    toastKind: 'delete',
                    onSuccess: () => {
                        // Deletion can shift pagination too, so resync from the server.
                        refreshTable(window.location.href);
                    },
                    // onError is intentionally left unhandled here, submitAjaxAction
                    // already shows the server's specific message (e.g. "can't delete,
                    // they have order or stock movement history") via the toast itself.
                });
            }

            function attachRowListeners(scope) {
                scope.querySelectorAll('.edit-staff-btn').forEach((btn) => {
                    btn.addEventListener('click', () => openModal('edit', btn.dataset));
                });
                scope.querySelectorAll('.delete-staff-btn').forEach((btn) => {
                    btn.addEventListener('click', () => deleteStaff(btn));
                });
            }

            document.getElementById('add-staff-btn').addEventListener('click', () => openModal('create'));
            document.getElementById('staff-modal-close').addEventListener('click', closeModal);
            document.getElementById('staff-modal-cancel').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            form.addEventListener('submit', submitForm);

            attachRowListeners(tableContainer());
            attachPaginationListeners(tableContainer());
        })();
    </script>

@endsection