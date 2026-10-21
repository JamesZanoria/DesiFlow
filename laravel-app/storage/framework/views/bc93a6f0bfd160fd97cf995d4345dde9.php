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

        <div class="flex-1 min-h-0 overflow-hidden flex flex-col lg:flex-row gap-8">
            
            <div class="flex-1 min-h-0 overflow-hidden flex flex-col pr-2">
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-8 md:p-12 relative flex flex-col h-full overflow-hidden">
                    
                    <div class="mb-16 relative px-2 flex-none">
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-slate-100 w-full -z-10"></div>
                        <div id="studio-progress-bar" class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-amber-500 transition-all duration-500 -z-10" style="width: 0%"></div>

                        <div class="flex items-center justify-between relative">
                            <?php $steps = ['Identity', 'Permissions', 'Security', 'Review']; ?>
                            <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="step-indicator flex flex-col items-center gap-3 z-10" data-step="<?php echo e($index + 1); ?>">
                                    <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center text-xs font-bold text-slate-400 transition-all duration-300 shadow-sm"><?php echo e($index + 1); ?></div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?php echo e($step); ?></span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto relative custom-scrollbar pb-12">
                        <form id="staff-studio-form" class="h-full space-y-12" action="<?php echo e(route('admin.staff.store')); ?>" method="POST">
                            <?php echo csrf_field(); ?>

                            
                            <div id="step-1" class="studio-step h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Account Identity</h3>
                                    <p class="text-sm text-slate-500">Define the core professional details for the staff member.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-8">
                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Full Name <span class="text-red-400">*</span>
                                        </label>
                                        <input type="text" name="name" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300" placeholder="e.g. Maria Santos">
                                        <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1">This field is required.</p>
                                    </div>

                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Email Address <span class="text-red-400">*</span>
                                        </label>
                                        <input type="email" name="email" required inputmode="email" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300" placeholder="name@example.com">
                                        <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1">This field is required.</p>
                                    </div>
                                </div>
                            </div>

                            
                            <div id="step-2" class="studio-step hidden h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Role & Permissions</h3>
                                    <p class="text-sm text-slate-500">Assign the operational authority level for this account.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-8">
                                    <div class="group max-w-md">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Assigned Role <span class="text-red-400">*</span>
                                        </label>
                                        <div class="relative">
                                            <select name="role" required class="w-full appearance-none rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 cursor-pointer">
                                                <option value="" disabled selected>Select a role</option>
                                                <option value="cashier">Cashier</option>
                                                <option value="kitchen">Kitchen Staff</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                            </div>
                                        </div>
                                        <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1">Please select a role.</p>
                                    </div>
                                </div>
                            </div>

                            
                            <div id="step-3" class="studio-step hidden h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Security Controls</h3>
                                    <p class="text-sm text-slate-500">Establish a secure access key for the account.</p>
                                </div>

                                <div class="bg-slate-50 rounded-3xl p-8 border border-slate-100 space-y-10">
                                    <div class="grid grid-cols-1 gap-8">
                                        <div class="group relative">
                                            <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                                Password <span class="text-red-400">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="password" name="password" id="password" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 placeholder:text-slate-300" placeholder="Enter a strong password">
                                                <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-target="password">
                                                    <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0,010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                                </button>
                                            </div>
                                            <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1">This field is required.</p>
                                        </div>
                                        <div class="group relative">
                                            <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                                Confirm Password <span class="text-red-400">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="password" name="password_confirmation" id="password_confirmation" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 placeholder:text-slate-300" placeholder="Re-enter password">
                                                <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-target="password_confirmation">
                                                    <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0,010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                                </button>
                                            </div>
                                            <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1">This field is required.</p>
                                        </div>
                                    </div>

                                    <div class="pt-6 border-t border-slate-200/60">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Security Strength</span>
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

                            
                            <div id="step-4" class="studio-step hidden h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Final Review</h3>
                                    <p class="text-sm text-slate-500">Verify all account configuration details before finalizing.</p>
                                </div>

                                <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Account Summary</p>
                                    </div>
                                    <div class="p-8 space-y-5">
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Full Name</span>
                                            <span id="review-name" class="text-sm font-bold text-slate-900">-</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Email</span>
                                            <span id="review-email" class="text-sm font-bold text-slate-900">-</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2">
                                            <span class="text-sm font-medium text-slate-500">Assigned Role</span>
                                            <span id="review-role" class="text-sm font-bold text-amber-600">-</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-center text-xs text-slate-400 italic px-6">Ensure all details are correct. You can go back to any previous step to make changes.</p>
                            </div>
                        </form>
                    </div>

                    
                    <div class="mt-12 pt-8 border-t border-slate-100 flex items-center justify-between flex-none">
                        <button type="button" id="studio-prev-btn" class="hidden px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-50 border border-transparent hover:border-slate-200 rounded-xl transition-all">
                            Back
                        </button>
                        <div class="flex-1"></div>
                        <div class="flex items-center gap-4">
                            <button type="button" id="studio-discard-btn"
                               class="px-6 py-2.5 text-sm font-bold text-slate-400 hover:text-slate-600 hover:bg-slate-50 border border-transparent hover:border-slate-200 rounded-xl transition-all text-center">
                                Discard
                            </button>
                            <button type="button" id="studio-next-btn" class="px-8 py-3 bg-amber-500 hover:bg-amber-600 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl shadow-lg shadow-amber-500/20 transition-all active:scale-95">
                                Next Step
                            </button>
                            <button type="submit" id="staff-modal-save" form="staff-studio-form" class="hidden px-8 py-3 bg-slate-900 hover:bg-black text-white text-sm font-bold rounded-xl shadow-lg shadow-slate-900/20 transition-all active:scale-95 flex items-center justify-center gap-2">
                                <span id="save-spinner" class="hidden w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span>Save Account</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="w-full lg:w-[480px] flex flex-col items-center justify-center bg-slate-200/60 p-12 rounded-[3rem] border border-slate-200 relative">
                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center mb-12">Admin Panel Preview</h4>

                <div class="w-full max-w-[340px] bg-white rounded-xl shadow-[0_30px_60px_-12px_rgba(0,0,0,0.12)] border border-white transition-all duration-500 group hover:shadow-amber-500/10 relative">
                    <div class="relative h-48 bg-slate-100 overflow-hidden rounded-t-xl flex items-center justify-center">
                        <div class="w-24 h-24 rounded-full bg-slate-200 border-4 border-white shadow-sm flex items-center justify-center text-slate-400 overflow-hidden">
                            <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                        </div>
                        <div id="preview-role-badge" class="absolute top-4 right-4 px-3 py-1 rounded-full bg-white/70 backdrop-blur-md text-[9px] font-black text-slate-800 uppercase tracking-wider shadow-sm border border-white/50">
                            Role
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/10 to-transparent pointer-events-none"></div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-start gap-3">
                            <div class="space-y-1 overflow-hidden">
                                <h3 id="preview-card-name" class="text-xl font-black text-slate-900 truncate leading-tight">Staff Name</h3>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span class="text-[10px] text-slate-400 font-medium">Verified Account</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-50">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email</span>
                                <span id="preview-card-email" class="text-xs font-medium text-slate-600 truncate ml-4">example@email.com</span>
                            </div>
                        </div>
                    </div>
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
                const saveBtn = document.getElementById('staff-modal-save');
                const nextBtn = document.getElementById('studio-next-btn');
                const prevBtn = document.getElementById('studio-prev-btn');
                const progressBar = document.getElementById('studio-progress-bar');
                const indicators = document.querySelectorAll('.step-indicator');
                const confirmModal = document.getElementById('confirm-modal');
                const saveSpinner = document.getElementById('save-spinner');

                const prevName = document.getElementById('preview-card-name');
                const prevEmail = document.getElementById('preview-card-email');
                const prevRole = document.getElementById('preview-role-badge');
                const revName = document.getElementById('review-name');
                const revEmail = document.getElementById('review-email');
                const revRole = document.getElementById('review-role');

                let currentStep = parseInt(localStorage.getItem('staff_studio_step')) || 1;
                const totalSteps = 4;

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
                    validateCurrentStep();
                });

                confirmInput.addEventListener('input', validateCurrentStep);

                function updateStepper() {
                    document.querySelectorAll('.studio-step').forEach((s, i) => {
                        s.classList.toggle('hidden', i + 1 !== currentStep);
                    });

                    prevBtn.classList.toggle('hidden', currentStep === 1);

                    if (currentStep === totalSteps) {
                        nextBtn.classList.add('hidden');
                        saveBtn.classList.remove('hidden');
                        updateReviewSummary();
                    } else {
                        nextBtn.classList.remove('hidden');
                        saveBtn.classList.add('hidden');
                    }

                    const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
                    progressBar.style.width = `${progress}%`;

                    indicators.forEach((ind, i) => {
                        const circle = ind.querySelector('.step-circle');
                        if (i + 1 < currentStep) {
                            circle.classList.add('bg-amber-500', 'text-white', 'border-amber-500');
                            circle.classList.remove('bg-white', 'text-slate-400', 'border-slate-200');
                            circle.innerHTML = '✓';
                        } else if (i + 1 === currentStep) {
                            circle.classList.add('border-amber-500', 'text-amber-600', 'ring-2', 'ring-amber-500/20');
                            circle.classList.remove('bg-amber-500', 'text-white', 'border-slate-200');
                            circle.innerHTML = (i + 1);
                        } else {
                            circle.classList.add('bg-white', 'text-slate-400', 'border-slate-200');
                            circle.classList.remove('bg-amber-500', 'text-white', 'border-amber-500', 'ring-2', 'ring-amber-500/20');
                            circle.innerHTML = (i + 1) ;
                        }
                    });

                    validateCurrentStep();
                }

                function validateCurrentStep() {
                    const currentStepEl = document.getElementById(`step-${currentStep}`);
                    if (!currentStepEl) return;
                    const requiredInputs = currentStepEl.querySelectorAll('[required]');
                    let allFilled = true;

                    requiredInputs.forEach(input => {
                        if (!input.value || (typeof input.value === 'string' && !input.value.trim())) {
                            allFilled = false;
                        }
                    });

                    if (currentStep === 3) {
                        const passwordValue = passwordInput.value;
                        if (passwordValue.length > 0) {
                            const isStrong = Object.values(strengthRules).every(check => check(passwordValue));
                            const matches = passwordValue === confirmInput.value;
                            if (!isStrong || !matches) allFilled = false;
                        }
                    }

                    nextBtn.disabled = !allFilled;
                }

                function updateLivePreview() {
                    const name = form.elements['name']?.value || '';
                    const email = form.elements['email']?.value || '';
                    const role = form.elements['role']?.value || '';

                    // Persist form data
                    const formData = { name, email, role };
                    localStorage.setItem('staff_studio_data', JSON.stringify(formData));

                    prevName.textContent = name || 'Staff Name';
                    prevEmail.textContent = email || 'example@email.com';
                    prevRole.textContent = role || 'Role';

                    validateCurrentStep();
                }

                function updateReviewSummary() {
                    const name = form.elements['name']?.value || '';
                    const email = form.elements['email']?.value || '';
                    const role = form.elements['role']?.value || '';

                    revName.textContent = name || '-';
                    revEmail.textContent = email || '-';
                    revRole.textContent = role || '-';
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

                document.getElementById('studio-discard-btn').addEventListener('click', () => {
                    confirmModal.classList.remove('hidden');
                });

                document.getElementById('confirm-cancel').addEventListener('click', () => {
                    confirmModal.classList.add('hidden');
                });

                document.getElementById('confirm-discard').addEventListener('click', () => {
                    localStorage.removeItem('staff_studio_data');
                    localStorage.removeItem('staff_studio_step');
                    window.location.href = "<?php echo e(route('admin.staff.index')); ?>";
                });

                nextBtn.addEventListener('click', () => {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        localStorage.setItem('staff_studio_step', currentStep);
                        updateStepper();
                    }
                });

                prevBtn.addEventListener('click', () => {
                    if (currentStep > 1) {
                        currentStep--;
                        localStorage.setItem('staff_studio_step', currentStep);
                        updateStepper();
                    }
                });

                form.addEventListener('input', updateLivePreview);

                updateStepper();
                updateLivePreview();

                // Restore saved data
                const savedData = JSON.parse(localStorage.getItem('staff_studio_data') || '{}');
                if (savedData.name) form.elements['name'].value = savedData.name;
                if (savedData.email) form.elements['email'].value = savedData.email;
                if (savedData.role) form.elements['role'].value = savedData.role;

                updateLivePreview();
            })();
        </script>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/jamesninobertgalon/Desktop/DesiFlow/laravel-app/resources/views/admin/staff/create.blade.php ENDPATH**/ ?>