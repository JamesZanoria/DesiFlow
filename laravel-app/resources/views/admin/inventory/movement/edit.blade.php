@extends('layouts.app')

@section('title', 'Edit Stock Movement')

@section('content')
    <div class="h-screen overflow-hidden flex flex-col p-6 bg-slate-50/50 font-sans">
        {{-- Breadcrumb Navigation --}}
        <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400 mb-6 shrink-0">
            <a href="{{ route('admin.inventory.index') }}" class="hover:text-amber-600 transition-colors flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                Inventory Management
            </a>
            <span class="text-slate-300">
                <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </span>
            <span class="text-slate-300">
                <a href="{{ route('admin.inventory.ledger') }}" class="hover:text-amber-600 transition-colors">Stock Ledger</a>
            </span>
            <span class="text-slate-300">
                <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </span>
            <span class="text-slate-900">Edit Movement</span>
        </nav>

        <div class="flex-1 min-h-0 overflow-hidden flex flex-col items-center justify-center">
            <div class="w-full max-w-3xl min-h-0 overflow-hidden flex flex-col px-4">
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-8 md:p-12 relative flex flex-col h-full overflow-hidden">

                    {{-- Stepper Progress --}}
                    <div class="mb-16 relative px-2 flex-none">
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-slate-100 w-full -z-10"></div>
                        <div id="studio-progress-bar" class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-amber-500 transition-all duration-500 -z-10" style="width: 0%"></div>

                        <div class="flex items-center justify-between relative">
                            @php $steps = ['Item & Type', 'Quantities', 'Audit & Notes', 'Review']; @endphp
                            @foreach($steps as $index => $step)
                                <div class="step-indicator flex flex-col items-center gap-3 z-10" data-step="{{ $index + 1 }}">
                                    <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center text-xs font-bold text-slate-400 transition-all duration-300 shadow-sm">{{ $index + 1 }}</div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $step }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto relative custom-scrollbar pb-12">
                        <form id="movement-form" class="h-full space-y-12" action="" method="POST">
                            @csrf
                            @method('PUT')

                            {{-- STEP 1: ITEM & TYPE (LOCKED) --}}
                            <div id="step-1" class="studio-step h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Item & Type</h3>
                                    <p class="text-sm text-slate-500">These core details are locked for audit integrity.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-8">
                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">
                                            Ingredient
                                        </label>
                                        <div class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm bg-slate-50 text-slate-500 font-medium cursor-not-allowed">
                                            {{ $movement->ingredient->ingredient_name }} ({{ $movement->ingredient->unit_of_measurement }})
                                        </div>
                                    </div>

                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest">
                                            Movement Type
                                        </label>
                                        <div class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm bg-slate-50 text-slate-500 font-medium cursor-not-allowed">
                                            {{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- STEP 2: QUANTITIES --}}
                            <div id="step-2" class="studio-step hidden h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Quantities</h3>
                                    <p class="text-sm text-slate-500">Adjust the quantity for this stock movement.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-8">
                                    <div class="group">
                                        <label id="qty-label" class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Quantity <span class="text-red-400">*</span>
                                        </label>
                                        <div class="relative">
                                            <input type="number" step="0.01" name="qty_changed" id="qty_changed" required
                                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300"
                                                placeholder="0.00" value="{{ $draft['inputs']['qty_changed'] ?? $movement->qty_changed }}">
                                        </div>
                                        <p id="qty-hint" class="text-xs text-slate-400 mt-2 italic">Update the recorded quantity.</p>
                                        <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1">Quantity is required.</p>
                                    </div>
                                </div>
                            </div>

                            {{-- STEP 3: AUDIT & NOTES --}}
                            <div id="step-3" class="studio-step hidden h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Audit & Notes</h3>
                                    <p class="text-sm text-slate-500">Update supporting details and provide a reason for this edit.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="group">
                                            <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                                Delivery Date
                                            </label>
                                            <input type="date" name="delivery_date" id="delivery_date"
                                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 disabled:bg-slate-50 disabled:text-slate-400"
                                                value="{{ $draft['inputs']['delivery_date'] ?? $movement->delivery_date ? $movement->delivery_date->format('Y-m-d') : '' }}">
                                        </div>
                                        <div class="group">
                                            <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                                Expiration Date
                                            </label>
                                            <input type="date" name="expiration_date" id="expiration_date"
                                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 disabled:bg-slate-50 disabled:text-slate-400"
                                                value="{{ $draft['inputs']['expiration_date'] ?? $movement->expiration_date ? $movement->expiration_date->format('Y-m-d') : '' }}">
                                        </div>
                                    </div>

                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Reference Number / Invoice
                                        </label>
                                        <input type="text" name="reference_number" id="reference_number"
                                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 disabled:bg-slate-50 disabled:text-slate-400"
                                            placeholder="e.g. INV-2024-001" value="{{ $draft['inputs']['reference_number'] ?? $movement->reference_number }}">
                                    </div>

                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Remarks / Notes
                                        </label>
                                        <textarea name="notes" id="notes" rows="3"
                                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300"
                                            placeholder="Add any internal remarks here...">{{ $draft['inputs']['notes'] ?? $movement->notes }}</textarea>
                                    </div>

                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Reason for Edit <span class="text-red-400">*</span>
                                        </label>
                                        <input type="text" name="edit_reason" id="edit_reason" required
                                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300"
                                            placeholder="e.g. Typo in quantity" value="{{ $draft['inputs']['edit_reason'] ?? '' }}">
                                        <p class="error-text hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1">Edit reason is required.</p>
                                    </div>

                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Date of Transaction (Optional Override)
                                        </label>
                                        <input type="date" name="recorded_at" id="recorded_at"
                                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300 disabled:bg-slate-50 disabled:text-slate-400"
                                            value="{{ $draft['inputs']['recorded_at'] ?? $movement->recorded_at ? $movement->recorded_at->format('Y-m-d') : '' }}">
                                    </div>
                                </div>
                            </div>

                            {{-- STEP 4: REVIEW --}}
                            <div id="step-4" class="studio-step hidden h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Final Review</h3>
                                    <p class="text-sm text-slate-500">Verify the adjusted details before updating the ledger.</p>
                                </div>

                                <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Adjustment Summary</p>
                                    </div>
                                    <div class="p-8 space-y-5">
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Ingredient</span>
                                            <span id="review-ingredient" class="text-sm font-bold text-slate-900">{{ $movement->ingredient->ingredient_name }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Movement Type</span>
                                            <span id="review-type" class="text-sm font-bold text-slate-900">{{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Quantity</span>
                                            <span id="review-qty" class="text-sm font-bold text-slate-900">-</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Delivery Date</span>
                                            <span id="review-delivery" class="text-sm font-bold text-slate-900">-</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Expiration Date</span>
                                            <span id="review-expiration" class="text-sm font-bold text-slate-900">-</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Reference #</span>
                                            <span id="review-ref" class="text-sm font-bold text-slate-900">-</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Edit Reason</span>
                                            <span id="review-reason" class="text-sm font-bold text-amber-600">-</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2">
                                            <span class="text-sm font-medium text-slate-500">Notes</span>
                                            <span id="review-notes" class="text-sm font-bold text-slate-900 text-right max-w-[200px] truncate">-</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-center text-xs text-slate-400 italic px-6">Your update will be recorded as a modification in the audit trail.</p>
                            </div>
                        </form>
                    </div>

                    {{-- Footer Actions --}}
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
                            <button type="submit" id="save-movement-btn" form="movement-form" class="hidden px-8 py-3 bg-slate-900 hover:bg-black text-white text-sm font-bold rounded-xl shadow-lg shadow-slate-900/20 transition-all active:scale-95 flex items-center justify-center gap-2">
                                <span id="save-spinner" class="hidden w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span>Update Movement</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Confirmation Modal --}}
        <div id="confirm-modal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in-95 duration-200 border border-slate-200">
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-red-600 text-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-500/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 mb-2 tracking-tight">Discard Changes?</h3>
                    <p class="text-sm text-slate-500 mb-8">Any unsaved edits will be permanently lost. This action cannot be undone.</p>
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

        {{-- Toast Container --}}
        <div class="fixed bottom-6 right-6 z-50 pointer-events-none">
            <div id="toast-container" class="flex flex-col gap-3"></div>
        </div>

        <script>
            (function () {
                const form = document.getElementById('movement-form');
                const saveBtn = document.getElementById('save-movement-btn');
                const nextBtn = document.getElementById('studio-next-btn');
                const prevBtn = document.getElementById('studio-prev-btn');
                const discardBtn = document.getElementById('studio-discard-btn');
                const saveSpinner = document.getElementById('save-spinner');

                let currentStep = 1;
                const totalSteps = 4;
                const draft = @json($draft ?? []);
                const saveDraftUrl = '{{ route('admin.inventory.movement.saveEditDraft', $movement->movement_id) }}';

                // UI Elements
                const studioSteps = document.querySelectorAll('.studio-step');
                const progressBar = document.getElementById('studio-progress-bar');

                // Review Elements
                const revQty = document.getElementById('review-qty');
                const revDelivery = document.getElementById('review-delivery');
                const revExpiration = document.getElementById('review-expiration');
                const revRef = document.getElementById('review-ref');
                const revReason = document.getElementById('review-reason');
                const revNotes = document.getElementById('review-notes');

                // Modal Elements
                const confirmModal = document.getElementById('confirm-modal');
                const confirmCancel = document.getElementById('confirm-cancel');
                const confirmDiscard = document.getElementById('confirm-discard');

                let saveTimeout;

                window.showToast = function(message, type = 'success') {
                    const container = document.getElementById('toast-container');
                    if (!container) return;
                    const toast = document.createElement('div');
                    toast.className = `px-6 py-4 rounded-2xl shadow-2xl border text-sm font-bold transition-all duration-500 transform translate-y-20 opacity-0 flex items-center gap-3 ${
                        type === 'success' ? 'bg-white border-green-100 text-green-800' : 'bg-white border-red-100 text-red-800'
                    }`;
                    toast.innerHTML = `
                        <div class="w-2 h-2 rounded-full ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}"></div>
                        <span>${message}</span>
                    `;
                    container.appendChild(toast);
                    setTimeout(() => {
                        toast.classList.remove('translate-y-20', 'opacity-0');
                    }, 10);
                    setTimeout(() => {
                        toast.classList.add('opacity-0', 'translate-y-10');
                        setTimeout(() => toast.remove(), 500);
                    }, 4000);
                };

                async function saveDraftToServer() {
                    const formData = new FormData(form);
                    const data = {
                        currentStep,
                        inputs: {}
                    };

                    formData.forEach((value, key) => {
                        if (!key.includes('_token') && !key.includes('_method')) {
                            data.inputs[key] = value;
                        }
                    });

                    try {
                        await fetch(saveDraftUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(data)
                        });
                    } catch (e) {
                        console.error('Draft auto-save failed:', e);
                    }
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

                    const visibleErrors = currentStepEl.querySelectorAll('.error-text:not(.hidden)');
                    if (visibleErrors.length > 0) {
                        allFilled = false;
                    }

                    nextBtn.disabled = !allFilled;
                }

                function updateStepperUI() {
                    document.querySelectorAll('.studio-step').forEach((s, i) => {
                        s.classList.toggle('hidden', i + 1 !== currentStep);
                    });

                    prevBtn.classList.toggle('hidden', currentStep === 1);

                    if (currentStep === totalSteps) {
                        nextBtn.classList.add('hidden');
                        saveBtn.classList.remove('hidden');
                    } else {
                        nextBtn.classList.remove('hidden');
                        saveBtn.classList.add('hidden');
                    }

                    const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
                    progressBar.style.width = `${progress}%`;

                    document.querySelectorAll('.step-indicator').forEach((ind, i) => {
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
                            circle.innerHTML = (i + 1);
                        }
                    });

                    validateCurrentStep();
                }

                function updateLivePreview() {
                    const qty = document.getElementById('qty_changed').value;
                    const delivery = document.getElementById('delivery_date').value;
                    const expiration = document.getElementById('expiration_date').value;
                    const ref = document.getElementById('reference_number').value;
                    const reason = document.getElementById('edit_reason').value;
                    const notes = document.getElementById('notes').value;

                    revQty.textContent = qty || '—';
                    revDelivery.textContent = delivery || '—';
                    revExpiration.textContent = expiration || '—';
                    revRef.textContent = ref || '—';
                    revReason.textContent = reason || '—';
                    revNotes.textContent = notes || '—';

                    validateCurrentStep();

                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(saveDraftToServer, 500);
                }

                document.getElementById('studio-discard-btn').addEventListener('click', () => {
                    confirmModal.classList.remove('hidden');
                });

                confirmCancel.addEventListener('click', () => {
                    confirmModal.classList.add('hidden');
                });

                confirmDiscard.addEventListener('click', async () => {
                    await fetch(saveDraftUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ clear: true })
                    });
                    window.location.href = '{{ route('admin.inventory.ledger') }}';
                });

                nextBtn.addEventListener('click', async () => {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        updateStepperUI();
                        saveDraftToServer();
                    }
                });

                prevBtn.addEventListener('click', () => {
                    if (currentStep > 1) {
                        currentStep--;
                        updateStepperUI();
                        saveDraftToServer();
                    }
                });

                form.addEventListener('input', (event) => {
                    updateLivePreview();
                    const input = event.target;
                    if (input && input.parentElement) {
                        const errorEl = input.parentElement.querySelector('.error-text');
                        if (errorEl) {
                            errorEl.classList.add('hidden');
                        }
                    }
                });

                if (draft) {
                    if (draft.currentStep) currentStep = draft.currentStep;
                    if (draft.inputs) {
                        Object.entries(draft.inputs).forEach(([key, value]) => {
                            const input = form.querySelector(`[name="${key}"]`);
                            if (input) input.value = value;
                        });
                    }
                }

                updateStepperUI();
                updateLivePreview();

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    saveBtn.disabled = true;
                    saveSpinner.classList.remove('hidden');

                    try {
                        const formData = new FormData(form);
                        const movementId = formData.get('movement_id') || '{{ $movement->movement_id }}';

                        const response = await fetch(`{{ url('admin/inventory/movement') }}/${movementId}`, {
                            method: 'PUT',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        if (response.ok) {
                            await fetch(saveDraftUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ clear: true })
                            });

                            window.location.href = "{{ route('admin.inventory.ledger') }}";
                        } else if (response.status === 409) {
                            const data = await response.json();
                            window.showToast(data.message || 'This movement is locked and cannot be edited.', 'error');
                        } else {
                            const data = await response.json();
                            if (data.errors) {
                                Object.entries(data.errors).forEach(([field, messages]) => {
                                    const input = form.elements[field];
                                    if (input) {
                                        const errorEl = input.parentElement.querySelector('.error-text');
                                        if (errorEl) {
                                            errorEl.textContent = messages[0];
                                            errorEl.classList.remove('hidden');
                                        }
                                    }
                                });
                            } else {
                                window.showToast(data.message || 'A system error occurred.', 'error');
                            }
                        }
                    } catch (err) {
                        console.error('Submission error:', err);
                        alert('A system error occurred. Please try again.');
                    } finally {
                        saveBtn.disabled = false;
                        saveSpinner.classList.add('hidden');
                    }
                });
            })();
        </script>
@endsection