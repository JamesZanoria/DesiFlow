<?php $__env->startSection('title', 'Staff Studio'); ?>

<?php $__env->startSection('content'); ?>
    <div class="h-screen overflow-hidden flex flex-col p-6 bg-slate-50/50 font-sans">
        
        <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400 mb-6 shrink-0">
            <a href="<?php echo e(route('admin.staff.index')); ?>" class="hover:text-amber-600 transition-colors flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                Staff Management
            </a>
            <span class="text-slate-300">
                <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </span>
            <span class="text-slate-900">Creation Studio</span>
        </nav>

        <div class="flex-1 min-h-0 overflow-hidden flex items-center justify-center">
            <div class="w-full max-w-6xl bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden flex flex-col h-full max-h-full relative">

                
                <div class="flex-1 overflow-hidden p-12 bg-white">
                    <form id="staff-studio-form" class="h-full flex flex-col justify-center space-y-16" action="<?php echo e(route('admin.staff.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                            <div class="lg:col-span-1 space-y-2">
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">Account Identity</h3>
                                <p class="text-sm text-slate-500">Define the core professional details for this staff member.</p>
                            </div>
                            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="group">
                                    <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                        Full Name <span class="text-red-400">*</span>
                                    </label>
                                    <input type="text" name="name" required
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300"
                                        placeholder="e.g. Maria Santos">
                                    <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1"></p>
                                </div>
                                <div class="group">
                                    <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                        Email Address <span class="text-red-400">*</span>
                                    </label>
                                    <input type="email" name="email" required inputmode="email"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300"
                                        placeholder="name@example.com">
                                    <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1"></p>
                                </div>
                            </div>
                        </div>

                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                            <div class="lg:col-span-1 space-y-2">
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">Role & Permissions</h3>
                                <p class="text-sm text-slate-500">Assign a specific operational role within the restaurant.</p>
                            </div>
                            <div class="lg:col-span-2">
                                <div class="max-w-md group relative">
                                    <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                        Assigned Role <span class="text-red-400">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="role" required
                                            class="w-full appearance-none rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 cursor-pointer">
                                            <option value="" disabled selected>Select a role</option>
                                            <option value="cashier">Cashier</option>
                                            <option value="kitchen">Kitchen Staff</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </div>
                                    <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1"></p>
                                </div>
                            </div>
                        </div>

                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                            <div class="lg:col-span-1 space-y-2">
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">Security Controls</h3>
                                <p class="text-sm text-slate-500">Set up secure access credentials for the staff member.</p>
                            </div>
                            <div class="lg:col-span-2">
                                <div class="bg-slate-50 rounded-3xl p-8 border border-slate-100 space-y-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="group relative">
                                            <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                                Password <span class="text-red-400">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="password" name="password" id="password" required
                                                    class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 placeholder:text-slate-300"
                                                    placeholder="Enter a strong password">
                                                <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-target="password">
                                                    <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0,010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                                </button>
                                            </div>
                                            <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1"></p>
                                        </div>
                                        <div class="group relative">
                                            <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                                Confirm Password <span class="text-red-400">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="password" name="password_confirmation" id="password_confirmation" required
                                                    class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 placeholder:text-slate-300"
                                                    placeholder="Re-enter password">
                                                <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-target="password_confirmation">
                                                    <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0,010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                                </button>
                                            </div>
                                            <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1"></p>
                                        </div>
                                    </div>

                                    <div class="pt-6 border-t border-slate-200/60">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Password Strength</span>
                                            <span id="strength-label" class="text-xs font-bold uppercase tracking-wider text-slate-400">Not entered</span>
                                        </div>
                                        <div class="flex gap-1.5 h-1.5">
                                            <div class="strength-segment flex-1 rounded-full bg-slate-200 transition-colors duration-300"></div>
                                            <div class="strength-segment flex-1 rounded-full bg-slate-200 transition-colors duration-300"></div>
                                            <div class="strength-segment flex-1 rounded-full bg-slate-200 transition-colors duration-300"></div>
                                            <div class="strength-segment flex-1 rounded-full bg-slate-200 transition-colors duration-300"></div>
                                        </div>
                                        <p id="strength-hint" class="hidden text-xs text-slate-500 mt-3 italic"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                
                <div class="px-12 py-6 border-t border-slate-100 flex items-center justify-end gap-3 bg-white shrink-0 z-10">
                    <button type="button" id="discard-btn"
                        class="px-6 py-3 text-sm font-bold text-slate-400 hover:text-slate-600 hover:bg-slate-50 border border-transparent hover:border-slate-200 rounded-xl transition-all text-center">
                        Discard
                    </button>
                    <button type="submit" form="staff-studio-form" id="save-btn"
                        class="px-8 py-3 bg-slate-900 hover:bg-black text-white text-sm font-bold rounded-xl shadow-lg shadow-slate-900/20 transition-all active:scale-95">
                        Save Staff Account
                    </button>
                </div>
            </div>
        </div>

        
        <div id="confirm-modal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in-95 duration-200 border border-slate-200">
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-red-600 text-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-500/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 mb-2 tracking-tight">Discard Changes?</h3>
                    <p class="text-sm text-slate-500 mb-8">All unsaved data in this form will be permanently lost. This action cannot be undone.</p>
                    <div class="flex gap-3">
                        <button type="button" id="confirm-cancel" class="flex-1 px-4 py-3 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                            Keep Editing
                        </button>
                        <button type="button" id="confirm-discard" class="flex-1 px-4 py-3 rounded-xl text-sm font-bold text-white bg-red-600 hover:bg-red-700 shadow-lg shadow-red-500/20 transition-all active:scale-95">
                            Discard
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function () {
                const form = document.getElementById('staff-studio-form');
                const passwordInput = document.getElementById('password');
                const confirmInput = document.getElementById('password_confirmation');
                const strengthLabel = document.getElementById('strength-label');
                const strengthHint = document.getElementById('strength-hint');
                const segments = document.querySelectorAll('.strength-segment');
                const saveBtn = document.getElementById('save-btn');
                const confirmModal = document.getElementById('confirm-modal');

                const strengthRules = {
                    length: (v) => v.length >= 10,
                    mixedCase: (v) => /[a-z]/.test(v) && /[A-Z]/.test(v),
                    number: (v) => /[0-9]/.test(v),
                    symbol: (v) => /[^A-Za-z0-9]/.test(v),
                };

                const ruleLabels = {
                    length: 'at least 10 characters',
                    mixedCase: 'upper & lower case',
                    number: 'a number',
                    symbol: 'a symbol',
                };

                const levels = [
                    { min: 0, label: 'Too Weak', color: 'bg-red-500', text: 'text-red-600' },
                    { min: 1, label: 'Weak', color: 'bg-red-400', text: 'text-red-500' },
                    { min: 2, label: 'Fair', color: 'bg-amber-400', text: 'text-amber-600' },
                    { min: 3, label: 'Good', color: 'bg-yellow-400', text: 'text-yellow-600' },
                    { min: 4, label: 'Strong', color: 'bg-green-500', text: 'text-green-600' },
                ];

                function updateStrength(value) {
                    if (value.length === 0) {
                        strengthLabel.textContent = 'Not entered';
                        strengthLabel.className = 'text-xs font-bold uppercase tracking-wider text-slate-400';
                        segments.forEach(s => s.className = 'strength-segment flex-1 rounded-full bg-slate-200 transition-colors duration-300');
                        strengthHint.classList.add('hidden');
                        return;
                    }

                    const score = Object.values(strengthRules).filter(check => check(value)).length;
                    const level = [...levels].reverse().find(l => score >= l.min);

                    strengthLabel.textContent = level.label;
                    strengthLabel.className = `text-xs font-bold uppercase tracking-wider ${level.text}`;

                    segments.forEach((s, i) => {
                        s.className = `strength-segment flex-1 rounded-full transition-colors duration-300 ${i < score ? level.color : 'bg-slate-200'}`;
                    });

                    const missing = Object.keys(strengthRules)
                        .filter(r => !strengthRules[r](value))
                        .map(r => ruleLabels[r]);

                    if (missing.length > 0) {
                        strengthHint.textContent = 'Needs: ' + missing.join(', ');
                        strengthHint.classList.remove('hidden');
                    } else {
                        strengthHint.classList.add('hidden');
                    }
                }

                passwordInput.addEventListener('input', (e) => {
                    updateStrength(e.target.value);
                    validateForm();
                });

                confirmInput.addEventListener('input', validateForm);

                function validateForm() {
                    const nameFilled = form.name.value.trim().length > 0;
                    const emailFilled = form.email.value.trim().length > 0;
                    const roleFilled = form.role.value !== '';
                    const passwordValue = passwordInput.value;
                    let isPasswordValid = true;
                    let passwordsMatch = true;

                    if (passwordValue.length > 0) {
                        isPasswordValid = Object.values(strengthRules).every(check => check(passwordValue));
                        passwordsMatch = passwordValue === confirmInput.value;
                    }

                    saveBtn.disabled = !(isPasswordValid && passwordsMatch && nameFilled && emailFilled && roleFilled);
                    saveBtn.classList.toggle('opacity-50', saveBtn.disabled);
                    saveBtn.classList.toggle('cursor-not-allowed', saveBtn.disabled);
                }

                document.querySelectorAll('.password-toggle').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const target = document.getElementById(btn.dataset.target);
                        const isPw = target.type === 'password';
                        target.type = isPw ? 'text' : 'password';
                        btn.querySelector('.eye-open').classList.toggle('hidden', isPw);
                        btn.querySelector('.eye-closed').classList.toggle('hidden', !isPw);
                    });
                });

                document.getElementById('discard-btn').addEventListener('click', () => {
                    confirmModal.classList.remove('hidden');
                });

                document.getElementById('confirm-cancel').addEventListener('click', () => {
                    confirmModal.classList.add('hidden');
                });

                document.getElementById('confirm-discard').addEventListener('click', () => {
                    window.location.href = "<?php echo e(route('admin.staff.index')); ?>";
                });

                const formFields = ['name', 'email', 'role'];
                formFields.forEach(field => {
                    const input = form.elements[field];
                    if (input) {
                        input.value = localStorage.getItem('staff_draft_' + field) || input.value;
                        input.addEventListener('input', (e) => {
                            localStorage.setItem('staff_draft_' + field, e.target.value);
                            validateForm();
                        });
                    }
                });

                document.getElementById('staff-studio-form').addEventListener('submit', () => {
                    formFields.forEach(field => localStorage.removeItem('staff_draft_' + field));
                });

                validateForm();
            })();
        </script>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/staff/create.blade.php ENDPATH**/ ?>