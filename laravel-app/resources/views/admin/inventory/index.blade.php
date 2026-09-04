@extends('layouts.app')

@section('title', 'Inventory Management')

@section('content')

    <div class="flex items-start justify-between mb-4 gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Inventory Management</h1>
            <p class="text-sm text-gray-500 mt-1">Track stock levels, monitor thresholds, and manage ingredients.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="inventory-export-btn"
                class="flex items-center gap-1.5 bg-white border px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Export
            </button>
            <button type="button" id="add-ingredient-btn"
                class="flex items-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Ingredient
            </button>
        </div>
    </div>

    <div id="inventory-stats-container">
        @include('admin.inventory._current_stock_stats')
    </div>

    @include('admin.inventory._tabs', ['activeTab' => 'stock'])

    <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex items-center justify-between gap-2 mb-4 flex-wrap">
        <div class="flex items-center gap-2">
            @include('admin.partials._filter_select', [
                'name' => 'category',
                'label' => 'Category',
                'value' => request('category'),
                'options' => $categories->mapWithKeys(fn ($c) => [$c => $c]),
            ])

            @include('admin.partials._filter_select', [
                'name' => 'status',
                'label' => 'Status',
                'value' => request('status'),
                'options' => [
                    'active' => 'Active',
                    'low_stock' => 'Low Stock',
                    'out_of_stock' => 'Out of Stock',
                    'expiring_soon' => 'Expiring Soon',
                    'expired' => 'Expired',
                ],
            ])

            @include('admin.partials._filter_select', [
                'name' => 'sort',
                'label' => 'Sort',
                'value' => request('sort'),
                'options' => ['critical' => 'Critical First'],
            ])
        </div>

        @include('admin.partials._filter_search', [
            'name' => 'search',
            'placeholder' => 'Search items...',
            'value' => request('search'),
            'suggestions' => $allIngredientNames,
        ])
    </form>

    {{-- Table + pagination now live in a shared partial, wrapped in this container so
         the JS can swap the whole thing out after Add/Edit/Delete or a pagination click
         without a full browser reload, see refreshTable() below. --}}
    <div id="inventory-table-container">
        @include('admin.inventory._table')
    </div>

    {{-- Add / Edit popup --}}
    <div id="ingredient-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md my-8 max-h-[90vh] overflow-y-auto">
            <div class="bg-indigo-50 rounded-t-xl px-6 py-4 flex items-center justify-between sticky top-0">
                <h2 id="ingredient-modal-title" class="text-lg font-semibold text-gray-800">Add Ingredient</h2>
                <button type="button" id="ingredient-modal-close" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>

            <div class="p-6">
                <div id="ingredient-modal-errors" class="hidden mb-4 text-sm"></div>

                {{-- Edit mode only: current stock is derived from the ledger, not editable here --}}
                <div id="ingredient-current-stock-display" class="hidden mb-4 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                    <p class="text-xs text-gray-500">Current Stock</p>
                    <p class="text-sm font-semibold text-gray-800"><span id="ingredient-current-stock-value">0</span> <span id="ingredient-current-stock-unit"></span></p>
                    <p class="text-xs text-gray-400 mt-1">To adjust stock, use Record Movement on the ledger page.</p>
                </div>

                <form id="ingredient-form">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient Name</label>
                        <input type="text" name="ingredient_name" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="e.g. Basmati Rice">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        {{-- Creatable select: pick an existing category, or type a new
                             one via "+ Add New Category". See initCreatableSelect() in
                             the script below — this markup shape (hidden input +
                             trigger button + popover with an options list and an
                             add-row) is reused for Unit of Measurement too. --}}
                        <div class="relative creatable-select" data-name="category" data-placeholder="Uncategorized" data-add-label="+ Add New Category" data-new-placeholder="New category name">
                            <input type="hidden" name="category">
                            <button type="button" class="creatable-select-trigger w-full border rounded-lg px-3 py-2 text-sm text-left bg-white flex items-center justify-between">
                                <span class="creatable-select-trigger-text text-gray-400">Uncategorized</span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            <div class="creatable-select-menu hidden absolute z-20 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-56 overflow-y-auto">
                                <div class="creatable-select-options">
                                    <button type="button" class="creatable-select-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50" data-value="">Uncategorized</button>
                                    @foreach ($categories as $category)
                                        <button type="button" class="creatable-select-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50" data-value="{{ $category }}">{{ $category }}</button>
                                    @endforeach
                                </div>
                                <div class="border-t">
                                    <button type="button" class="creatable-select-add-btn w-full text-left px-3 py-2 text-sm text-amber-600 hover:bg-amber-50 font-medium">+ Add New Category</button>
                                    <div class="creatable-select-add-row hidden p-2 flex gap-1.5">
                                        <input type="text" class="creatable-select-new-input flex-1 border rounded-lg px-2 py-1.5 text-sm" placeholder="New category name">
                                        <button type="button" class="creatable-select-new-confirm px-2.5 py-1.5 rounded-lg bg-amber-500 text-white text-xs font-medium hover:bg-amber-600">Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measurement</label>
                            <div class="relative creatable-select" data-name="unit_of_measurement" data-placeholder="Select unit" data-add-label="+ Add New Unit" data-new-placeholder="New unit (e.g. L, pcs)">
                                <input type="hidden" name="unit_of_measurement">
                                <button type="button" class="creatable-select-trigger w-full border rounded-lg px-3 py-2 text-sm text-left bg-white flex items-center justify-between">
                                    <span class="creatable-select-trigger-text text-gray-400">Select unit</span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                </button>
                                <div class="creatable-select-menu hidden absolute z-20 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-56 overflow-y-auto">
                                    <div class="creatable-select-options">
                                        @foreach ($units as $unit)
                                            <button type="button" class="creatable-select-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50" data-value="{{ $unit }}">{{ $unit }}</button>
                                        @endforeach
                                    </div>
                                    <div class="border-t">
                                        <button type="button" class="creatable-select-add-btn w-full text-left px-3 py-2 text-sm text-amber-600 hover:bg-amber-50 font-medium">+ Add New Unit</button>
                                        <div class="creatable-select-add-row hidden p-2 flex gap-1.5">
                                            <input type="text" class="creatable-select-new-input flex-1 border rounded-lg px-2 py-1.5 text-sm" placeholder="New unit (e.g. L, pcs)">
                                            <button type="button" class="creatable-select-new-confirm px-2.5 py-1.5 rounded-lg bg-amber-500 text-white text-xs font-medium hover:bg-amber-600">Add</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Stock Alert Level</label>
                            <input type="number" step="0.01" min="0" name="restock_threshold" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="e.g. 5.00">
                        </div>
                    </div>

                    {{-- Add mode only: unit cost, expiration date, and photo are set
                         once at creation and aren't editable from this form afterward
                         (see InventoryController::update()). Hidden as one group in
                         Edit mode rather than field-by-field. --}}
                    <div id="ingredient-create-only-fields">
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (₱)</label>
                                <input type="number" step="0.01" min="0" name="unit_cost" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expiration Date</label>
                                <input type="date" name="expiration_date" required class="w-full border rounded-lg px-3 py-2 text-sm">
                                <p class="text-xs text-gray-400 mt-1">Used to warn you before this ingredient expires.</p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient Photo (optional)</label>
                            {{-- Adapted directly from Menu Management's dropzone: same
                                 single-source-of-truth setDropzoneState() pattern, same
                                 JPG/JPEG/PNG-only restriction, same under-dropzone error
                                 slot instead of the shared top error box. IDs renamed
                                 ingredient-image-* to avoid colliding with Menu's if
                                 both were ever on the same page. --}}
                            <div id="ingredient-image-dropzone" class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer transition-colors hover:border-amber-400">
                                <input type="file" id="ingredient-image-input" name="image" accept="image/jpeg,image/png,.jpg,.jpeg,.png" class="hidden">
                                <input type="hidden" id="ingredient-remove-image" name="remove_image" value="0">

                                <div id="ingredient-image-placeholder" class="pointer-events-none">
                                    <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3.75 3.75M12 9.75l3.75 3.75M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                                    <p class="text-sm text-gray-600">Click or drag to upload image</p>
                                </div>

                                <div id="ingredient-image-dragover-state" class="hidden pointer-events-none">
                                    <svg class="w-8 h-8 text-amber-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                                    <p class="text-sm font-medium text-amber-600">Release to upload</p>
                                </div>

                                <div id="ingredient-image-preview-wrap" class="hidden pointer-events-none">
                                    <img id="ingredient-image-preview" class="w-16 h-16 object-cover rounded-lg mx-auto mb-2" src="" alt="">
                                    <p id="ingredient-image-filename" class="text-xs text-gray-600 truncate"></p>
                                    <button type="button" id="ingredient-image-clear" class="pointer-events-auto text-xs text-red-600 hover:underline mt-1">Remove</button>
                                </div>

                                <div id="ingredient-image-uploading" class="hidden pointer-events-none">
                                    <p id="ingredient-image-upload-text" class="text-xs text-gray-600 mb-2">Uploading... 0%</p>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div id="ingredient-image-upload-bar" class="bg-amber-500 h-2 rounded-full transition-all duration-150" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1.5">Accepted formats: JPG, JPEG, PNG only. Max 2MB.</p>
                            <p id="ingredient-image-error" class="hidden text-xs text-red-600 mt-1"></p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" id="ingredient-modal-cancel" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</button>
                        <button type="submit" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('ingredient-modal');
            const form = document.getElementById('ingredient-form');
            const title = document.getElementById('ingredient-modal-title');
            const errorBox = document.getElementById('ingredient-modal-errors');
            const storeUrl = '{{ route('admin.inventory.store') }}';
            const baseUrl = '{{ url('admin/inventory') }}';
            const indexUrl = '{{ route('admin.inventory.index') }}';
            const todayDateStr = '{{ now()->toDateString() }}';

            const createOnlyFields = document.getElementById('ingredient-create-only-fields');
            const currentStockDisplay = document.getElementById('ingredient-current-stock-display');
            const currentStockValue = document.getElementById('ingredient-current-stock-value');
            const currentStockUnit = document.getElementById('ingredient-current-stock-unit');

            function numberFormat(value) {
                return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // ---------------------------------------------------------------
            // Creatable select: a dropdown of existing values (Category, Unit
            // of Measurement) with a "+ Add New ..." row at the bottom that
            // swaps in a small text input for typing a brand-new one. Backed
            // by a plain hidden <input> that's what actually submits with the
            // form — form.category / form.unit_of_measurement below refer to
            // that hidden input, same as any other named field.
            //
            // One instance per .creatable-select wrapper; init() below reads
            // its own labels/placeholder from data-* attributes on the
            // wrapper, so this one function covers both fields.
            // ---------------------------------------------------------------
            function initCreatableSelect(root) {
                const hiddenInput = root.querySelector('input[type="hidden"]');
                const trigger = root.querySelector('.creatable-select-trigger');
                const triggerText = root.querySelector('.creatable-select-trigger-text');
                const menu = root.querySelector('.creatable-select-menu');
                const optionsContainer = root.querySelector('.creatable-select-options');
                const addBtn = root.querySelector('.creatable-select-add-btn');
                const addRow = root.querySelector('.creatable-select-add-row');
                const newInput = root.querySelector('.creatable-select-new-input');
                const newConfirm = root.querySelector('.creatable-select-new-confirm');
                const placeholder = root.dataset.placeholder || 'Select';

                function closeMenu() {
                    menu.classList.add('hidden');
                    addRow.classList.add('hidden');
                    addBtn.classList.remove('hidden');
                }

                function selectValue(value, label) {
                    hiddenInput.value = value;
                    triggerText.textContent = label || placeholder;
                    triggerText.classList.toggle('text-gray-400', !value);
                    triggerText.classList.toggle('text-gray-800', !!value);
                    closeMenu();
                    // Lets the shared red-outline error handling below react to
                    // a selection the same way it reacts to someone typing —
                    // this hidden input never gets a real user 'input' event.
                    hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                function optionButton(value) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'creatable-select-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50';
                    btn.dataset.value = value;
                    btn.textContent = value;
                    btn.addEventListener('click', () => selectValue(value, value));
                    return btn;
                }

                // Adding a new value here only affects this one dropdown for the
                // rest of the current page load — it's not persisted anywhere
                // client-side. It becomes a real option again (for every
                // ingredient's form) once the ingredient is saved and the page
                // or table refreshes, since Category/Unit's option lists come
                // from whatever distinct values already exist in the database.
                function addOption(rawValue) {
                    const value = rawValue.trim();
                    if (!value) return;

                    const existing = [...optionsContainer.querySelectorAll('.creatable-select-option')]
                        .find((btn) => btn.dataset.value.toLowerCase() === value.toLowerCase());

                    if (existing) {
                        selectValue(existing.dataset.value, existing.textContent.trim());
                        return;
                    }

                    optionsContainer.appendChild(optionButton(value));
                    selectValue(value, value);
                }

                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = !menu.classList.contains('hidden');
                    document.querySelectorAll('.creatable-select-menu').forEach((m) => m.classList.add('hidden'));
                    if (!isOpen) menu.classList.remove('hidden');
                });

                optionsContainer.querySelectorAll('.creatable-select-option').forEach((btn) => {
                    btn.addEventListener('click', () => selectValue(btn.dataset.value, btn.textContent.trim()));
                });

                addBtn.addEventListener('click', () => {
                    addBtn.classList.add('hidden');
                    addRow.classList.remove('hidden');
                    newInput.value = '';
                    newInput.focus();
                });

                newConfirm.addEventListener('click', () => addOption(newInput.value));
                newInput.addEventListener('keydown', (e) => {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    addOption(newInput.value);
                });
                newInput.addEventListener('click', (e) => e.stopPropagation());

                document.addEventListener('click', (e) => {
                    if (!root.contains(e.target)) closeMenu();
                });

                return {
                    reset(value, label) {
                        selectValue(value || '', value ? label : placeholder);
                    },
                };
            }

            const categorySelect = initCreatableSelect(document.querySelector('.creatable-select[data-name="category"]'));
            const unitSelect = initCreatableSelect(document.querySelector('.creatable-select[data-name="unit_of_measurement"]'));

            // ---------------------------------------------------------------
            // Field error display, shown only after Save is clicked and the
            // server rejects the submission (duplicate name, numbers in a text
            // field). Same visual language as Menu Management: a filled red
            // circle icon + plain red text, no boxed background, and a red
            // outline around whichever field caused the problem. The modal is
            // never closed by any of this.
            // ---------------------------------------------------------------
            const errorableFields = ['ingredient_name', 'unit_of_measurement'];

            // unit_of_measurement's actual form control is a hidden input (see
            // the creatable select above) — there's nothing visible to outline
            // there, so the outline goes on its trigger button instead.
            function highlightTarget(name) {
                const field = form.elements[name];
                if (!field) return null;
                const creatableRoot = field.closest('.creatable-select');
                return creatableRoot ? creatableRoot.querySelector('.creatable-select-trigger') : field;
            }

            function clearFieldErrors() {
                errorableFields.forEach((name) => {
                    highlightTarget(name)?.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                });
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
                errorBox.classList.remove('hidden');
                errorBox.innerHTML = `
                    <div class="flex items-start gap-2 text-red-700">
                        ${errorIconSvg()}
                        <p>${message}</p>
                    </div>
                `;

                const field = highlightTarget(fieldName);
                if (field) {
                    field.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                    if (field.focus) field.focus();
                    if (field.select) field.select();
                }
            }

            // Duplicate-name errors: Add mode resets the whole form (Ingredient
            // Name keeps its red outline even once emptied, as a persistent cue)
            // so the admin can immediately enter a different ingredient. Edit mode
            // just highlights the field instead, to avoid losing other changes.
            function showDuplicateError(mode) {
                errorBox.classList.remove('hidden');
                errorBox.innerHTML = `
                    <div class="flex items-start gap-2 text-red-700">
                        ${errorIconSvg()}
                        <p>This ingredient already exists in your inventory. Please check the name or edit the existing ingredient.</p>
                    </div>
                `;

                if (mode === 'create') {
                    form.reset();
                    clearFieldErrors();
                    resetImageDropzone(null);
                    createOnlyFields.classList.remove('hidden');
                    currentStockDisplay.classList.add('hidden');
                    categorySelect.reset();
                    unitSelect.reset();
                }

                form.ingredient_name.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                form.ingredient_name.focus();
                if (mode !== 'create') form.ingredient_name.select();
            }

            // Once the admin starts editing a field that previously errored
            // (e.g. the duplicate-name check), clear its red outline so it
            // doesn't linger after they've clearly started correcting it.
            errorableFields.forEach((name) => {
                const field = form.elements[name];
                if (!field) return;

                field.addEventListener('input', () => {
                    highlightTarget(name)?.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                });
            });

            // ---------------------------------------------------------------
            // Image dropzone, adapted directly from Menu Management, same
            // setDropzoneState() single-source-of-truth pattern that fixed the
            // "two states visible at once" bug there. See that page's
            // comments for the full rationale.
            // ---------------------------------------------------------------
            const dropzone = document.getElementById('ingredient-image-dropzone');
            const fileInput = document.getElementById('ingredient-image-input');
            const removeImageField = document.getElementById('ingredient-remove-image');
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
                dropzone.classList.remove('border-amber-400', 'bg-amber-50', 'border-red-400', 'bg-red-50');

                if (state !== 'error') {
                    imageErrorMsg.classList.add('hidden');
                }

                if (state === 'idle') {
                    imagePlaceholder.classList.remove('hidden');
                } else if (state === 'dragover') {
                    dragoverState.classList.remove('hidden');
                    dropzone.classList.add('border-amber-400', 'bg-amber-50');
                } else if (state === 'preview') {
                    previewWrap.classList.remove('hidden');
                } else if (state === 'uploading') {
                    uploadingState.classList.remove('hidden');
                } else if (state === 'error') {
                    imagePlaceholder.classList.remove('hidden');
                    dropzone.classList.add('border-red-400', 'bg-red-50');
                }
            }

            function showImageError(message) {
                imageErrorMsg.textContent = message;
                setDropzoneState('error');
            }

            function showImagePreview(file) {
                removeImageField.value = '0';
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    previewName.textContent = file.name;
                    setDropzoneState('preview');
                };
                reader.readAsDataURL(file);
            }

            function resetImageDropzone(existingImageUrl) {
                fileInput.value = '';
                removeImageField.value = '0';
                if (existingImageUrl) {
                    previewImg.src = existingImageUrl;
                    previewName.textContent = 'Current photo (click to replace)';
                    setDropzoneState('preview');
                } else {
                    setDropzoneState('idle');
                }
            }

            const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png'];
            const ACCEPTED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

            function isValidImageFile(file) {
                if (ACCEPTED_IMAGE_TYPES.includes(file.type)) return true;
                const ext = file.name.split('.').pop()?.toLowerCase();
                return !file.type && ACCEPTED_IMAGE_EXTENSIONS.includes(ext);
            }

            function rejectInvalidImage() {
                fileInput.value = '';
                showImageError('Please upload a JPG, JPEG, or PNG image.');
            }

            dropzone.addEventListener('click', (e) => {
                if (e.target.id !== 'ingredient-image-clear') fileInput.click();
            });
            fileInput.addEventListener('change', () => {
                const file = fileInput.files[0];
                if (!file) return;
                if (!isValidImageFile(file)) {
                    rejectInvalidImage();
                    return;
                }
                showImagePreview(file);
            });
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                setDropzoneState('dragover');
            });
            dropzone.addEventListener('dragleave', () => {
                setDropzoneState(fileInput.files[0] ? 'preview' : 'idle');
            });
            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                const file = e.dataTransfer.files[0];
                if (!file) {
                    setDropzoneState(fileInput.files[0] ? 'preview' : 'idle');
                    return;
                }
                if (!isValidImageFile(file)) {
                    rejectInvalidImage();
                    return;
                }
                fileInput.files = e.dataTransfer.files;
                showImagePreview(file);
            });
            document.getElementById('ingredient-image-clear').addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.value = '';
                removeImageField.value = '1';
                setDropzoneState('idle');
            });

            // ---------------------------------------------------------------
            // Table + stats + category filter refresh (fixes pagination going
            // stale after Add/Edit/Delete). Same pattern as Menu Management:
            // re-request this endpoint asking for JSON and swap in the fresh
            // fragments, instead of hand-patching the DOM.
            // ---------------------------------------------------------------
            function tableContainer() {
                return document.getElementById('inventory-table-container');
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

                    // An ingredient just added/edited/deleted can change every
                    // one of these: the stat cards (crossed a stock threshold,
                    // total item count), and the Category filter's options (a
                    // brand-new category, or the last item using one removed).
                    // Without this, both would silently show stale numbers
                    // until a real page reload, the same bug Menu
                    // Management's category filter had.
                    if (data.statsHtml) {
                        document.getElementById('inventory-stats-container').innerHTML = data.statsHtml;
                    }
                    if (data.categoryOptionsHtml) {
                        const categoryPopover = document.querySelector('[data-filter-select][data-name="category"] .filter-select-popover');
                        if (categoryPopover) categoryPopover.innerHTML = data.categoryOptionsHtml;
                    }

                    // Same idea for the search box's suggestion list, an
                    // ingredient just added/renamed/deleted should be
                    // reflected in suggestions immediately, not just in the
                    // table.
                    if (data.ingredientSuggestionsHtml) {
                        const suggestList = document.querySelector('[data-search-suggest] .search-suggest-list');
                        if (suggestList) suggestList.innerHTML = data.ingredientSuggestionsHtml;
                    }

                    history.replaceState(null, '', targetUrl);
                } catch (err) {
                    console.error('Failed to refresh inventory table', err);
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

            // ---------------------------------------------------------------
            // Automatic refresh, the admin should never need to click
            // anything or reload the page to see current stock levels: "The Admin Dashboard, Real-Time Order Monitoring view,
            // and low-stock alerts shall reflect changes in active orders and
            // inventory within 2 seconds, without requiring a manual page
            // refresh"). This page doesn't have a WebSocket push (Laravel
            // Echo/Reverb) wired in yet, that would be the more precise way
            // to hit that 2-second figure exactly, so short-interval polling
            // is the interim approach: it re-fetches on a timer instead of
            // waiting for the admin to take any action.
            
            // Every tick reuses the exact same refreshTable() used for
            // Add/Edit/Delete/pagination, so it honors whatever search,
            // category, sort, and page the admin currently has open rather
            // than resetting the view.
            
            // A tick is skipped (not rescheduled, just quietly skipped, the
            // interval keeps ticking) when:
            //   - the Add/Edit modal is open, so a background swap can't
            //     interrupt someone actively filling out a form;
            //   - the browser tab isn't visible, so an admin with this page
            //     open in an unfocused tab isn't silently hammering the
            //     server the whole time;
            //   - a refresh is already in flight, so a slow response can't
            //     overlap with the next tick and race against itself.
            // ---------------------------------------------------------------
            const AUTO_REFRESH_INTERVAL_MS = 5000;
            let refreshInFlight = false;

            setInterval(() => {
                if (refreshInFlight) return;
                if (!modal.classList.contains('hidden')) return;
                if (document.hidden) return;

                refreshInFlight = true;
                refreshTable().finally(() => {
                    refreshInFlight = false;
                });
            }, AUTO_REFRESH_INTERVAL_MS);

            function openModal(mode, data) {
                form.reset();
                errorBox.classList.add('hidden');
                errorBox.innerHTML = '';
                clearFieldErrors();

                if (mode === 'create') {
                    title.textContent = 'Add Ingredient';
                    form.dataset.mode = 'create';
                    form.dataset.ingredientId = '';
                    createOnlyFields.classList.remove('hidden');
                    currentStockDisplay.classList.add('hidden');
                    resetImageDropzone(null);
                    categorySelect.reset();
                    unitSelect.reset();
                    form.unit_cost.required = true;
                    form.expiration_date.required = true;
                    // No delivery yet to anchor an earlier date to (see
                    // InventoryController::store()) — can't pick before today.
                    form.expiration_date.min = todayDateStr;
                } else {
                    title.textContent = 'Edit Ingredient';
                    form.dataset.mode = 'edit';
                    form.dataset.ingredientId = data.id;
                    form.ingredient_name.value = data.name;
                    categorySelect.reset(data.category, data.category);
                    unitSelect.reset(data.unit, data.unit);
                    form.restock_threshold.value = data.threshold;

                    // Unit cost, expiration date, and photo aren't part of Edit
                    // (see InventoryController::update()) — hide the whole group
                    // and drop their required-ness so the hidden inputs can't
                    // block submission.
                    createOnlyFields.classList.add('hidden');
                    form.unit_cost.required = false;
                    form.expiration_date.required = false;

                    currentStockDisplay.classList.remove('hidden');
                    currentStockValue.textContent = numberFormat(data.currentStock ?? 0);
                    currentStockUnit.textContent = data.unit;
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
                const ingredientId = form.dataset.ingredientId;

                // The browser's own "required" enforcement skips hidden inputs
                // (that's what unit_of_measurement's creatable select is backed
                // by), so this has to be checked by hand before submitting.
                if (!form.unit_of_measurement.value) {
                    showFieldError('unit_of_measurement', 'Please select or add a unit of measurement.');
                    return;
                }

                await submitAjaxForm({
                    form,
                    url: mode === 'create' ? storeUrl : `${baseUrl}/${ingredientId}`,
                    method: mode === 'create' ? 'POST' : 'PUT',
                    errorBox,
                    toastKind: mode === 'create' ? 'success' : 'edit',
                    onSuccess: async () => {
                        // Re-fetch the current page from the server instead of
                        // patching the DOM by hand, guarantees row count, sort
                        // order, and pagination state are correct immediately.
                        await refreshTable(mode === 'create' ? indexUrl : window.location.href);
                        closeModal();
                    },
                    // A 422 lands here instead of the generic bullet-list, same
                    // duplicate-vs-numbers detection as Menu Management.
                    onValidationError: (data) => {
                        const errors = data?.errors || {};

                        const duplicateMsg = errors.ingredient_name?.find((m) => m.toLowerCase().includes('already'));
                        if (duplicateMsg) {
                            showDuplicateError(mode);
                            return;
                        }

                        // Server-side image errors (e.g. a file over 2MB, which
                        // the client-side check above never catches) get the
                        // same under-dropzone treatment as a client-rejected
                        // file, not the generic top-of-form error box.
                        if (errors.image?.length) {
                            showImageError(errors.image[0]);
                            return;
                        }

                        errorBox.classList.remove('hidden');
                        if (data && data.errors && typeof data.errors === 'object') {
                            errorBox.innerHTML = '<ul class="list-disc list-inside">' +
                                Object.values(data.errors).flat().map((m) => `<li>${escapeHtml(m)}</li>`).join('') + '</ul>';
                        } else {
                            errorBox.textContent = (data && data.message) || 'Please check the form and try again.';
                        }
                    },
                });
            }

            // Writes off an expired ingredient's entire current stock as a
            // spoilage entry in one click, rather than sending the admin to
            // the Ledger page to fill out Record Movement by hand for
            // something the system already knows the answer to (type:
            // spoilage, quantity: everything that's left). Reuses the same
            // recordMovement endpoint the Ledger's own form posts to, no new
            // backend route needed.
            function markAsWaste(btn) {
                const qty = parseFloat(btn.dataset.currentStock);
                submitAjaxAction({
                    url: `${baseUrl}/${btn.dataset.id}/movement`,
                    method: 'POST',
                    extraFields: {
                        movement_type: 'spoilage',
                        qty_changed: qty,
                        notes: 'Marked as waste, past shelf life',
                    },
                    confirmMessage: `Mark all ${numberFormat(qty)} ${btn.dataset.unit} of "${btn.dataset.name}" as waste? This will zero out its current stock and cannot be undone.`,
                    toastKind: 'delete',
                    onSuccess: () => {
                        refreshTable(window.location.href);
                    },
                });
            }

            function attachRowListeners(scope) {
                scope.querySelectorAll('.mark-waste-btn').forEach((btn) => {
                    btn.addEventListener('click', () => markAsWaste(btn));
                });
                scope.querySelectorAll('.edit-ingredient-btn').forEach((btn) => {
                    btn.addEventListener('click', () => openModal('edit', btn.dataset));
                });
            }

            // ---------------------------------------------------------------
            // Export, current page's visible table as CSV, generated
            // entirely client-side (no backend endpoint, no outbound network
            // call), consistent with the system's offline-capable
            // local-network design.
            
            // sanitizeCsvCell() guards against formula/CSV injection, see
            // the identical helper and full rationale in Analytics' export.
            // ---------------------------------------------------------------
            function sanitizeCsvCell(text) {
                return /^[=+\-@\t\r]/.test(text) ? `'${text}` : text;
            }

            document.getElementById('inventory-export-btn').addEventListener('click', () => {
                const table = tableContainer().querySelector('table');
                const rows = [...table.querySelectorAll('tr')].map((tr) =>
                    [...tr.querySelectorAll('th, td')]
                        .slice(0, -1) // drop the Actions column, nothing meaningful to export there
                        .map((cell) => `"${sanitizeCsvCell(cell.innerText.trim().replace(/\s+/g, ' ')).replace(/"/g, '""')}"`)
                        .join(',')
                );
                const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `desiflow-inventory-${new Date().toISOString().slice(0, 10)}.csv`;
                link.click();
                URL.revokeObjectURL(link.href);
            });

            document.getElementById('add-ingredient-btn').addEventListener('click', () => openModal('create'));
            document.getElementById('ingredient-modal-close').addEventListener('click', closeModal);
            document.getElementById('ingredient-modal-cancel').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            form.addEventListener('submit', submitForm);

            attachRowListeners(tableContainer());
            attachPaginationListeners(tableContainer());
        })();
    </script>

@endsection