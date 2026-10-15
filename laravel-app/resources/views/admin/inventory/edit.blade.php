@extends('layouts.app')

@section('title', 'Edit Ingredient')

@section('content')
    <div class="h-screen overflow-hidden flex flex-col p-6 bg-slate-50/50">
        {{-- Breadcrumb Navigation --}}
        <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400 mb-6">
            <a href="{{ route('admin.inventory.index') }}" class="hover:text-amber-600 transition-colors flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                Inventory Management
            </a>
            <span class="text-slate-300">
                <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </span>
            <span class="text-slate-900">Edit Studio: {{ $ingredient->ingredient_name }}</span>
        </nav>

        <div class="flex-1 min-h-0 overflow-hidden flex flex-col lg:flex-row gap-8">
            {{-- LEFT: Configuration Area --}}
            <div class="flex-1 min-h-0 overflow-hidden flex flex-col pr-2">
                <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm p-8 md:p-12 relative flex flex-col h-full overflow-hidden">

                    <div class="mb-12 space-y-2">
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">Modify Ingredient Identity</h3>
                        <p class="text-sm text-slate-500">Update the procurement details and alert levels for this item.</p>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto relative custom-scrollbar pb-12">
                        <form id="ingredient-form" class="space-y-12" action="{{ route('admin.inventory.update', $ingredient->ingredient_id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 gap-10">
                                {{-- Ingredient Name --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                    <div class="md:col-span-1">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                            Ingredient Name <span class="text-red-400">*</span>
                                        </label>
                                        <p class="text-xs text-slate-400 mt-1">The official name used across recipes and stock reports.</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input type="text" name="ingredient_name" value="{{ $ingredient->ingredient_name }}" required
                                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300"
                                            placeholder="e.g. Basmati Rice">
                                    </div>
                                </div>

                                {{-- Linked Menu Items --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                    <div class="md:col-span-1">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                            Linked Menu Items
                                        </label>
                                        <p class="text-xs text-slate-400 mt-1">Which dishes rely on this ingredient? Useful for impact analysis.</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div class="relative" data-multi-combo-select="menu_items" data-placeholder="Select menu items">
                                            <button type="button" id="menu-items-trigger" class="multi-combo-select-trigger w-full flex items-center justify-between gap-2 border rounded-xl px-4 py-3 text-sm text-left hover:border-slate-400 focus:outline-none min-h-[48px] bg-white transition-all">
                                                <div class="multi-combo-select-value flex flex-wrap gap-1 overflow-hidden text-gray-400">Select menu items</div>
                                                <svg class="combo-select-chevron w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                            </button>

                                            <div class="multi-combo-select-popover hidden absolute left-0 right-0 mt-2 bg-white border rounded-2xl shadow-2xl z-30 overflow-hidden">
                                                <div class="p-3 border-b bg-slate-50 flex gap-2">
                                                    <input type="text" class="multi-combo-select-search w-full border rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-amber-400" placeholder="Search menu items...">
                                                    <button type="button" class="multi-combo-select-all text-xs text-amber-600 hover:text-amber-700 font-bold px-2">All</button>
                                                    <button type="button" class="multi-combo-select-clear text-xs text-gray-500 hover:text-gray-700 font-bold px-2">Clear</button>
                                                </div>
                                                <div class="combo-select-list max-h-64 overflow-y-auto py-1">
                                                    @foreach ($menuItems as $item)
                                                        <button type="button" class="combo-select-option w-full flex items-center justify-between gap-2 px-4 py-3 text-xs text-left hover:bg-amber-50 text-slate-600 transition-colors" data-value="{{ $item->menu_item_id }}" data-label="{{ $item->item_name }}">
                                                            <span class="truncate font-medium">{{ $item->item_name }}</span>
                                                            <svg class="combo-select-check hidden w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                        </button>
                                                    @endforeach
                                                </div>
                                                <p class="combo-select-empty hidden px-4 py-8 text-center text-xs text-slate-400 italic">No matching menu items found.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                                    {{-- Unit of Measurement --}}
                                    <div class="grid grid-cols-1 gap-4 items-start">
                                        <div class="md:col-span-1">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                                Measurement Unit <span class="text-red-400">*</span>
                                            </label>
                                            <p class="text-xs text-slate-400 mt-1">The base unit for all stock movements (e.g. kg, L, pcs).</p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <div class="relative" data-combo-select="unit_of_measurement" data-allow-custom="false" data-placeholder="Select a unit">
                                                <input type="hidden" name="unit_of_measurement" value="{{ $ingredient->unit_of_measurement }}">
                                                <button type="button" class="combo-select-trigger w-full flex items-center justify-between gap-2 border rounded-xl px-4 py-3 text-sm text-left hover:border-slate-400 focus:outline-none bg-white transition-all">
                                                    <span class="combo-select-value min-w-0 truncate text-gray-400">{{ $ingredient->unit_of_measurement }}</span>
                                                    <svg class="combo-select-chevron w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                                </button>
                                                <div class="combo-select-popover hidden absolute left-0 right-0 mt-2 bg-white border rounded-2xl shadow-2xl z-30 overflow-hidden">
                                                    <div class="p-3 border-b">
                                                        <input type="text" class="combo-select-search w-full border rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-amber-400" placeholder="Search units...">
                                                    </div>
                                                    <div class="combo-select-list max-h-64 overflow-y-auto py-1">
                                                        @foreach ($units as $unit)
                                                            <button type="button" class="combo-select-option w-full flex items-center justify-between gap-2 px-4 py-3 text-xs text-left hover:bg-amber-50 text-slate-600 transition-colors" data-value="{{ $unit }}" data-label="{{ $unit }}">
                                                                <span class="truncate font-medium">{{ $unit }}</span>
                                                                <svg class="combo-select-check hidden w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                    <p class="combo-select-empty hidden px-4 py-8 text-center text-xs text-slate-400 italic">No matching units found.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Restock Threshold --}}
                                    <div class="grid grid-cols-1 gap-4 items-start">
                                        <div class="md:col-span-1">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                                Alert Level <span class="text-red-400">*</span>
                                            </label>
                                            <p class="text-xs text-slate-400 mt-1">System triggers a "Low Stock" alert when quantity falls below this.</p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="number" step="0.01" min="0" name="restock_threshold" value="{{ $ingredient->restock_threshold }}" required
                                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300"
                                                placeholder="e.g. 5.00">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-10 pt-6 border-t border-slate-100">
                                    <div class="md:col-span-1">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                            Unit Cost (₱) <span class="text-red-400">*</span>
                                        </label>
                                        <p class="text-xs text-slate-400 mt-1">The average cost per unit of measurement for financial reporting.</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input type="number" step="0.01" min="0" name="unit_cost" value="{{ $ingredient->unit_cost }}" required
                                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                {{-- Image Upload --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                                    <div class="md:col-span-1">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                            Ingredient Photo
                                        </label>
                                        <p class="text-xs text-slate-400 mt-1">Upload a clear photo to help staff identify the item quickly.</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div id="ingredient-image-dropzone" class="relative border-2 border-dashed border-slate-200 rounded-2xl p-10 text-center cursor-pointer transition-all hover:border-amber-500 hover:bg-amber-50/30 group bg-slate-50/50">
                                            <input type="file" id="ingredient-image-input" name="image" accept="image/jpeg,image/png,.jpg,.jpeg,.png" class="hidden">

                                            <div id="ingredient-image-placeholder" class="pointer-events-none">
                                                <div class="w-16 h-16 rounded-full bg-white shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3.75 3.75M12 9.75l3.75 3.75M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                                                </div>
                                                <p class="text-sm font-bold text-slate-600">Click or drag to upload</p>
                                                <p class="text-xs text-slate-400 mt-1">JPG, PNG up to 2MB</p>
                                            </div>

                                            <div id="ingredient-image-dragover-state" class="hidden pointer-events-none">
                                                <div class="w-16 h-16 rounded-full bg-amber-500 text-white flex items-center justify-center mx-auto mb-4 shadow-lg shadow-amber-500/30">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                                                </div>
                                                <p class="text-sm font-bold text-amber-600">Release to upload</p>
                                            </div>

                                            <div id="ingredient-image-preview-wrap" class="hidden pointer-events-none">
                                                <div class="relative inline-block">
                                                    <img id="ingredient-image-preview" class="w-32 h-32 object-cover rounded-2xl mx-auto mb-4 shadow-md border-4 border-white" src="{{ $ingredient->image_url ?? 'https://placehold.co/300x200?text=No+Image' }}" alt="">
                                                    <div class="absolute -top-2 -right-2 bg-amber-500 text-white rounded-full p-1 shadow-sm">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                    </div>
                                                </div>
                                                <p id="ingredient-image-filename" class="text-xs font-medium text-slate-600 truncate block max-w-[200px] mx-auto">Current Photo</p>
                                                <button type="button" id="ingredient-image-clear" class="pointer-events-auto text-xs font-bold text-red-500 hover:text-red-700 mt-2 underline">Remove Photo</button>
                                            </div>

                                            <div id="ingredient-image-uploading" class="hidden pointer-events-none">
                                                <div class="w-48 bg-slate-200 rounded-full h-2 mx-auto mb-3 overflow-hidden">
                                                    <div id="ingredient-image-upload-bar" class="bg-amber-500 h-full transition-all duration-150" style="width: 0%"></div>
                                                </div>
                                                <p id="ingredient-image-upload-text" class="text-xs font-bold text-slate-500">Uploading... 0%</p>
                                            </div>
                                        </div>
                                        <p id="ingredient-image-error" class="hidden text-xs font-bold text-red-500 mt-2 text-center"></p>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="mt-12 pt-8 border-t border-slate-100 flex items-center justify-between flex-none">
                        <button type="button" id="studio-discard-btn"
                            class="px-6 py-3 text-sm font-bold text-slate-400 hover:text-slate-600 hover:bg-slate-50 border border-transparent hover:border-slate-200 rounded-xl transition-all">
                            Discard
                        </button>
                        <div class="flex-1"></div>
                        <button type="submit" id="save-ingredient-btn" form="ingredient-form"
                            class="px-10 py-3 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-lg shadow-amber-500/20 transition-all active:scale-95">
                            Update Ingredient
                        </button>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Visual Reference --}}
            <div class="w-full lg:w-[400px] flex flex-col items-center justify-center bg-slate-200/60 p-12 rounded-[3rem] border border-slate-200 relative">
                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center mb-12">System Reference</h4>

                <div class="w-full max-w-[300px] bg-white rounded-3xl shadow-[0_30px_60px_-12px_rgba(0,0,0,0.12)] border border-white transition-all duration-500 relative overflow-hidden">
                    <div class="h-48 bg-slate-100 flex items-center justify-center relative">
                        <img id="ref-card-image" src="{{ $ingredient->image_url ?? 'https://placehold.co/300x200?text=No+Image' }}" class="w-full h-full object-cover transition-transform duration-1000" alt="">
                        <div class="absolute top-4 left-4 px-3 py-1 rounded-full bg-white/70 backdrop-blur-md text-[9px] font-black text-slate-800 uppercase tracking-wider shadow-sm border border-white/50">
                            Ingredient
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-start gap-3">
                            <div class="space-y-1 overflow-hidden">
                                <h3 id="ref-card-name" class="text-xl font-black text-slate-900 truncate leading-tight">{{ $ingredient->ingredient_name }}</h3>
                                <div class="flex items-center gap-1.5">
                                    <span id="ref-card-unit" class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ $ingredient->unit_of_measurement }}</span>
                                </div>
                            </div>
                            <span id="ref-card-cost" class="text-xl font-black text-amber-600 whitespace-nowrap">₱{{ number_format($ingredient->unit_cost, 2) }}</span>
                        </div>
                        <div class="pt-4 border-t border-slate-50">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Alert Level</span>
                                <span id="ref-card-threshold" class="text-xs font-bold text-slate-900">{{ number_format($ingredient->restock_threshold, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>

    <script>
        (function () {
            const form = document.getElementById('ingredient-form');
            const saveBtn = document.getElementById('save-ingredient-btn');
            const discardBtn = document.getElementById('studio-discard-btn');

            // Reference Card Elements
            const refName = document.getElementById('ref-card-name');
            const refUnit = document.getElementById('ref-card-unit');
            const refCost = document.getElementById('ref-card-cost');
            const refThreshold = document.getElementById('ref-card-threshold');
            const refImg = document.getElementById('ref-card-image');

            function updateReference() {
                const name = form.elements['ingredient_name']?.value || 'Ingredient Name';
                const unit = document.querySelector('[data-combo-select="unit_of_measurement"] .combo-select-value')?.textContent || 'Unit';
                const cost = parseFloat(form.elements['unit_cost']?.value) || 0;
                const threshold = parseFloat(form.elements['restock_threshold']?.value) || 0;

                refName.textContent = name;
                refUnit.textContent = unit === 'Select a unit' ? 'Unit' : unit;
                refCost.textContent = `₱${cost.toFixed(2)}`;
                refThreshold.textContent = `${threshold.toFixed(2)}`;
            }

            // Image Handling
            const dropzone = document.getElementById('ingredient-image-dropzone');
            const fileInput = document.getElementById('ingredient-image-input');
            const imagePlaceholder = document.getElementById('ingredient-image-placeholder');
            const dragoverState = document.getElementById('ingredient-image-dragover-state');
            const previewWrap = document.getElementById('ingredient-image-preview-wrap');
            const previewImg = document.getElementById('ingredient-image-preview');
            const previewName = document.getElementById('ingredient-image-filename');
            const uploadingState = document.getElementById('ingredient-image-uploading');
            const imageErrorMsg = document.getElementById('ingredient-image-error');

            function setDropzoneState(state) {
                imagePlaceholder.classList.add('hidden');
                dragoverState.classList.add('hidden');
                previewWrap.classList.add('hidden');
                uploadingState.classList.add('hidden');

                if (state === 'idle') imagePlaceholder.classList.remove('hidden');
                else if (state === 'dragover') dragoverState.classList.remove('hidden');
                else if (state === 'preview') previewWrap.classList.remove('hidden');
                else if (state === 'uploading') uploadingState.classList.remove('hidden');
            }

            function showImagePreview(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    refImg.src = e.target.result;
                    previewName.textContent = file.name;
                    setDropzoneState('preview');
                };
                reader.readAsDataURL(file);
            }

            dropzone.addEventListener('click', (e) => {
                if (e.target.id !== 'ingredient-image-clear') fileInput.click();
            });

            fileInput.addEventListener('change', () => {
                const file = fileInput.files[0];
                if (file) showImagePreview(file);
            });

            document.getElementById('ingredient-image-clear').addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.value = '';
                refImg.src = 'https://placehold.co/300x200?text=No+Image';
                setDropzoneState('idle');
            });

            // Event Listeners
            form.addEventListener('input', updateReference);

            discardBtn.addEventListener('click', async () => {
                const confirmed = await window.confirmAction({
                    title: 'Discard Changes?',
                    message: 'Are you sure you want to discard your progress? This action cannot be undone.',
                    actionLabel: 'Discard',
                    type: 'discard'
                });
                if (confirmed) {
                    window.location.href = '{{ route('admin.inventory.index') }}';
                }
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';

                await submitAjaxForm({
                    form,
                    url: '{{ route('admin.inventory.update', $ingredient->ingredient_id) }}',
                    method: 'PUT',
                    toastKind: 'success',
                    onSuccess: () => {
                        window.location.href = '{{ route('admin.inventory.index') }}';
                    },
                });
            });
        })();
    </script>
@endsection
