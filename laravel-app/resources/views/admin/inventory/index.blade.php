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
            <a href="{{ route('admin.inventory.create') }}"
                class="flex items-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Ingredient
            </a>
        </div>
    </div>

    <div id="inventory-stats-container">
        @include('admin.inventory._current_stock_stats')
    </div>

    @include('admin.inventory._tabs', ['activeTab' => 'stock'])

    <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex items-center justify-between gap-2 mb-4 flex-wrap">
        <div class="flex items-center gap-2">
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

    <div id="inventory-table-container">
        @include('admin.inventory._table')
    </div>

    {{-- Waste Details Popover --}}
    <div id="waste-popover" class="hidden fixed z-[60] bg-white border shadow-xl rounded-lg p-4 w-72 max-w-[calc(100vw-32px)] max-h-[80vh] overflow-y-auto pointer-events-auto">
        <div class="flex items-center justify-between mb-3 pb-2 border-b sticky top-0 bg-white z-10">
            <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wide">Waste Details</h4>
            <button type="button" id="waste-popover-close" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div id="waste-popover-content" class="space-y-3">
            {{-- Content injected by JS --}}
        </div>
        <div class="mt-4 pt-2 border-t sticky bottom-0 bg-white">
            <p class="text-[10px] text-gray-400 italic">Quantities based on Naive FIFO stock movement.</p>
        </div>
    </div>

    {{-- Add / Edit popup --}}
    <div id="ingredient-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md my-8 max-h-[90vh] flex flex-col">
            <div class="bg-indigo-50 rounded-t-xl px-6 py-4 flex items-center justify-between flex-none">
                <h2 id="ingredient-modal-title" class="text-lg font-semibold text-gray-800">Add Ingredient</h2>
                <button type="button" id="ingredient-modal-close" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto p-6">
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Linked Menu Items</label>
                        <div class="relative" data-multi-combo-select="menu_items" data-placeholder="Select menu items">
                            <button type="button" id="menu-items-trigger" class="multi-combo-select-trigger w-full flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm text-left hover:border-gray-400 focus:outline-none min-h-[42px]">
                                <div class="multi-combo-select-value flex flex-wrap gap-1 overflow-hidden text-gray-400">Select menu items</div>
                                <svg class="combo-select-chevron w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>

                            <div class="multi-combo-select-popover hidden absolute left-0 right-0 mt-2 bg-white border rounded-lg shadow-lg z-30 overflow-hidden">
                                <div class="p-2 border-b bg-gray-50 flex gap-2">
                                    <input type="text" class="multi-combo-select-search w-full border rounded-md px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-amber-400" placeholder="Search menu items...">
                                    <button type="button" class="multi-combo-select-all text-xs text-amber-600 hover:text-amber-700 font-medium px-1">All</button>
                                    <button type="button" class="multi-combo-select-clear text-xs text-gray-500 hover:text-gray-700 font-medium px-1">Clear</button>
                                </div>
                                <div class="combo-select-list max-h-48 overflow-y-auto py-1">
                                    @foreach ($menuItems as $item)
                                        <button type="button" class="combo-select-option w-full flex items-center justify-between gap-2 px-3 py-2 text-xs text-left hover:bg-gray-50 text-gray-600" data-value="{{ $item->menu_item_id }}" data-label="{{ $item->item_name }}">
                                            <span class="truncate">{{ $item->item_name }}</span>
                                            <svg class="combo-select-check hidden w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        </button>
                                    @endforeach
                                </div>
                                <p class="combo-select-empty hidden px-3 py-4 text-center text-xs text-gray-400">No matching menu items.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measurement</label>
                            <div class="relative" data-combo-select="unit_of_measurement" data-allow-custom="false" data-placeholder="Select a unit">
                                <input type="hidden" name="unit_of_measurement" value="">
                                <button type="button"
                                    class="combo-select-trigger w-full flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm text-left hover:border-gray-400 focus:outline-none">
                                    <span class="combo-select-value min-w-0 truncate text-gray-400">Select a unit</span>
                                    <svg class="combo-select-chevron w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                </button>

                                <div class="combo-select-popover hidden absolute left-0 right-0 mt-2 bg-white border rounded-lg shadow-lg z-30 overflow-hidden">
                                    <div class="p-2 border-b">
                                        <input type="text" class="combo-select-search w-full border rounded-md px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-amber-400" placeholder="Search units...">
                                    </div>
                                    <div class="combo-select-list max-h-48 overflow-y-auto py-1">
                                        @foreach ($units as $unit)
                                            <button type="button" class="combo-select-option w-full flex items-center justify-between gap-2 px-3 py-2 text-xs text-left hover:bg-gray-50 text-gray-600" data-value="{{ $unit }}" data-label="{{ $unit }}">
                                                <span class="truncate">{{ $unit }}</span>
                                                <svg class="combo-select-check hidden w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            </button>
                                        @endforeach
                                    </div>
                                    <p class="combo-select-empty hidden px-3 py-4 text-center text-xs text-gray-400">No matching units.</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Stock Alert Level</label>
                            <input type="number" step="0.01" min="0" name="restock_threshold" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="e.g. 5.00">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (₱)</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                    </div>

                    <div id="ingredient-create-only-fields">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient Photo (optional)</label>
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
                </form>
            </div>

            <div class="px-6 py-4 border-t bg-gray-50 rounded-b-xl flex justify-end gap-3 flex-none">
                <button type="button" id="ingredient-modal-cancel"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit" id="ingredient-form-save-btn" form="ingredient-form"
                    class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600">
                    Save Ingredient
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('ingredient-modal');
            const form = document.getElementById('ingredient-form');
            const title = document.getElementById('ingredient-modal-title');
            const saveBtn = document.getElementById('ingredient-form-save-btn');
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
            // Dirty-check: in Edit mode, Save stays disabled until at least one
            // of the editable fields (name, category, unit, threshold) differs
            // from what the modal was opened with. Create mode is never gated
            // this way, there's nothing to compare a brand-new ingredient
            // against. Category and Unit are combo-selects backed by hidden
            // inputs, setComboSelectValue() (admin.js) dispatches a real
            // 'change' event on them so this listens for that alongside plain
            // 'input' on the two text fields.
            // ---------------------------------------------------------------
            let initialSnapshot = null;

            function snapshotEditableFields() {
                return JSON.stringify({
                    name: form.ingredient_name.value.trim(),
                    menu_items: form.elements['menu_items[]'] ? Array.from(form.elements['menu_items[]']).map(i => i.value).sort() : [],
                    unit: form.unit_of_measurement.value,
                    threshold: form.restock_threshold.value,
                    cost: form.unit_cost.value,
                });
            }

            function isFormValid() {
                const name = form.ingredient_name.value.trim();
                const unit = form.unit_of_measurement.value;
                const threshold = form.restock_threshold.value;
                const cost = form.unit_cost.value;

                return name !== '' && unit !== '' && threshold !== '' && cost !== '';
            }

            ['ingredient_name', 'restock_threshold', 'unit_cost'].forEach((name) => {
                form.elements[name]?.addEventListener('input', () => {});
            });
            // Use a proxy or delegation for multi-select change since it's not a simple input
            document.addEventListener('change', (e) => {
                if (e.target && e.target.name === 'menu_items[]') {
                }
            });
            form.unit_of_measurement.addEventListener('change', () => {
                const trigger = document.querySelector('[data-combo-select="unit_of_measurement"] .combo-select-trigger');
                if (trigger) {
                    trigger.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                }
            });

            // ---------------------------------------------------------------
            // Field error display
            // ---------------------------------------------------------------
            const errorableFields = ['ingredient_name', 'unit_of_measurement'];

            function clearFieldErrors() {
                errorableFields.forEach((name) => {
                    form.elements[name]?.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                });
                document.getElementById('menu-items-trigger')?.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
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

                let field = form.elements[fieldName];

                // For combo-selects, target the trigger button instead of the hidden input
                if (fieldName === 'unit_of_measurement') {
                    const container = document.querySelector(`[data-combo-select="${fieldName}"]`);
                    if (container) {
                        field = container.querySelector('.combo-select-trigger');
                    }
                }

                if (field) {
                    field.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                    field.focus();
                    if (field.select) field.select();
                }
            }

            function showDuplicateError(mode) {
                showToast('Ingredient already exists', 'error');

                if (mode === 'create') {
                    form.reset();
                    clearFieldErrors();
                    resetImageDropzone(null);
                    document.querySelectorAll('[data-combo-select]').forEach((c) => setComboSelectValue(c, '', ''));
                    createOnlyFields.classList.remove('hidden');
                    currentStockDisplay.classList.add('hidden');
                }

                form.ingredient_name.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                form.ingredient_name.focus();
                if (mode !== 'create') form.ingredient_name.select();
            }

            errorableFields.forEach((name) => {
                const field = form.elements[name];
                if (!field) return;
                field.addEventListener('input', () => {
                    field.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                });
            });

            // ---------------------------------------------------------------
            // Image dropzone
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
            // Table + stats + category filter refresh
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

                    if (data.statsHtml) {
                        document.getElementById('inventory-stats-container').innerHTML = data.statsHtml;
                    }
                    if (data.categoryOptionsHtml) {
                        const categoryPopover = document.querySelector('[data-filter-select][data-name="category"] .filter-select-popover');
                        if (categoryPopover) categoryPopover.innerHTML = data.categoryOptionsHtml;
                    }
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

            // Event delegation for all table interactions (Pagination, Edit, Delete, Waste)
            // This replaces the need to re-attach listeners after every refreshTable() call.
            (function() {
                const container = tableContainer();
                if (!container) return;

                container.addEventListener('click', (e) => {
                    // Pagination
                    const paginationLink = e.target.closest('.pagination-link');
                    if (paginationLink) {
                        if (paginationLink.classList.contains('pointer-events-none')) return;
                        e.preventDefault();
                        refreshTable(paginationLink.href);
                        return;
                    }

                    // Mark as Waste
                    const wasteBtn = e.target.closest('.mark-waste-btn');
                    if (wasteBtn) {
                        markAsWaste(wasteBtn);
                        return;
                    }

                    // Edit
                    const editBtn = e.target.closest('.edit-ingredient-btn');
                    if (editBtn) {
                        openModal('edit', editBtn.dataset);
                        return;
                    }

                    // Delete
                    const deleteBtn = e.target.closest('.delete-ingredient-btn');
                    if (deleteBtn) {
                        deleteIngredient(deleteBtn);
                        return;
                    }
                });
            })();

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
                clearFieldErrors();
                document.querySelectorAll('[data-combo-select]').forEach((c) => setComboSelectValue(c, '', ''));

                if (mode === 'create') {
                    title.textContent = 'Add Ingredient';
                    form.dataset.mode = 'create';
                    form.dataset.ingredientId = '';
                    createOnlyFields.classList.remove('hidden');
                    currentStockDisplay.classList.add('hidden');
                    resetImageDropzone(null);

                    initialSnapshot = null;
                } else {
                    title.textContent = 'Edit Ingredient';
                    form.dataset.mode = 'edit';
                    form.dataset.ingredientId = data.id;
                    form.ingredient_name.value = data.name;

                    // Handle multi-select for Linked Menu Items
                    const multiSelect = document.querySelector('[data-multi-combo-select="menu_items"]');
                    const linkedItemsIds = data.linkedItemIds ? data.linkedItemIds.split(',') : [];
                    window.syncIngredientMenuItems(linkedItemsIds);

                    setComboSelectValue(document.querySelector('[data-combo-select="unit_of_measurement"]'), data.unit || '', data.unit || '');
                    form.restock_threshold.value = data.threshold;
                    form.unit_cost.value = data.cost;

                    createOnlyFields.classList.add('hidden');

                    currentStockDisplay.classList.remove('hidden');
                    currentStockValue.textContent = numberFormat(data.currentStock ?? 0);
                    currentStockUnit.textContent = data.unit;

                    // Snapshot taken AFTER the fields above are populated, this
                    // is the "unchanged" baseline Save stays disabled against.
                    initialSnapshot = snapshotEditableFields();
                }

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');

                // Comprehensive reset
                form.reset();
                clearFieldErrors();
                resetImageDropzone(null);

                // Reset custom selects UI
                document.querySelectorAll('[data-combo-select]').forEach((c) => {
                    setComboSelectValue(c, '', '');
                    const trigger = c.querySelector('.combo-select-trigger');
                    if (trigger) trigger.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                });

                // Reset multi-select UI
                if (window.syncIngredientMenuItems) {
                    window.syncIngredientMenuItems([]);
                }
            }

            // Multi-combo-select logic
            (function() {
                const container = document.querySelector('[data-multi-combo-select="menu_items"]');
                if (!container) return;

                const trigger = container.querySelector('.multi-combo-select-trigger');
                const popover = container.querySelector('.multi-combo-select-popover');
                const searchInput = container.querySelector('.multi-combo-select-search');
                const allBtn = container.querySelector('.multi-combo-select-all');
                const clearBtn = container.querySelector('.multi-combo-select-clear');
                const options = container.querySelectorAll('.combo-select-option');
                const emptyMsg = container.querySelector('.combo-select-empty');

                let selectedValues = [];
                const optionsMap = {};
                options.forEach(opt => {
                    optionsMap[opt.dataset.value] = opt.dataset.label;
                });

                function updateUI() {
                    setMultiComboSelectValue(container, selectedValues, optionsMap);

                    // Clear error state if items are selected
                    if (selectedValues.length > 0) {
                        trigger.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                    }

                    // Sync hidden inputs for the form (arrays in Laravel)
                    const currentHiddens = container.querySelectorAll('input[name="menu_items[]"]');
                    currentHiddens.forEach(h => h.remove());
                    selectedValues.forEach(val => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'menu_items[]';
                        input.value = val;
                        container.appendChild(input);
                    });
                }

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!container.contains(e.target)) {
                        popover.classList.add('hidden');
                    }
                });

                trigger.addEventListener('click', (e) => {
                    const removeBtn = e.target.closest('.remove-tag');
                    if (removeBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const val = removeBtn.dataset.value;
                        selectedValues = selectedValues.filter(v => v !== val);
                        updateUI();
                        return;
                    }

                    popover.classList.toggle('hidden');
                    if (!popover.classList.contains('hidden')) {
                        searchInput.value = '';
                        searchInput.dispatchEvent(new Event('input'));
                        searchInput.focus();
                    }
                });

                options.forEach(opt => {
                    opt.addEventListener('click', () => {
                        const val = opt.dataset.value;
                        if (selectedValues.includes(val)) {
                            selectedValues = selectedValues.filter(v => v !== val);
                        } else {
                            selectedValues.push(val);
                        }
                        updateUI();
                    });
                });

                searchInput.addEventListener('input', () => {
                    const query = searchInput.value.trim().toLowerCase();
                    let anyVisible = false;
                    options.forEach(opt => {
                        const label = opt.dataset.label.toLowerCase();
                        const matches = label.includes(query);
                        opt.classList.toggle('hidden', !matches);
                        if (matches) anyVisible = true;
                    });
                    emptyMsg.classList.toggle('hidden', anyVisible);
                });

                allBtn.addEventListener('click', () => {
                    // Select only currently visible/filtered items
                    const visibleValues = Array.from(options)
                        .filter(opt => !opt.classList.contains('hidden'))
                        .map(opt => opt.dataset.value);

                    // Merge with existing to avoid deselecting things that are currently filtered out
                    const uniqueSet = new Set([...selectedValues, ...visibleValues]);
                    selectedValues = Array.from(uniqueSet);
                    updateUI();
                });

                clearBtn.addEventListener('click', () => {
                    selectedValues = [];
                    updateUI();
                });

                // Delegate tag removal
                trigger.addEventListener('click', (e) => {
                    const removeBtn = e.target.closest('.remove-tag');
                    if (removeBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const val = removeBtn.dataset.value;
                        selectedValues = selectedValues.filter(v => v !== val);
                        updateUI();
                    }
                });

                // Sync with Edit mode (called from openModal)
                window.syncIngredientMenuItems = (ids) => {
                    selectedValues = ids || [];
                    updateUI();
                };
            })();

            async function submitForm(e) {

                e.preventDefault();
                const mode = form.dataset.mode;
                const ingredientId = form.dataset.ingredientId;

                if (!ingredientId && mode === 'edit') {
                    showToast('Error: Ingredient ID is missing.', 'error');
                    return;
                }

                // Linked Menu Items validation
                const menuItems = Array.from(form.querySelectorAll('input[name="menu_items[]"]')).filter(i => i.value.trim() !== '');
                if (menuItems.length === 0) {
                    showToast({title: 'Validation Error', message: 'Please select a menu item.'}, 'error');
                    const trigger = document.getElementById('menu-items-trigger');
                    if (trigger) {
                        trigger.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                        trigger.focus();
                    }
                    return;
                }

                if (!form.unit_of_measurement.value) {
                    showFieldError('unit_of_measurement', 'Please select a unit of measurement.');
                    return;
                }

                await submitAjaxForm({
                    form,
                    url: mode === 'create' ? storeUrl : `${baseUrl}/${ingredientId}`,
                    method: mode === 'create' ? 'POST' : 'PUT',
                    toastKind: mode === 'create' ? 'success' : 'edit',
                    onSuccess: async () => {
                        await refreshTable(mode === 'create' ? indexUrl : window.location.href);
                        closeModal();
                    },
                    onValidationError: (data) => {
                        const errors = data?.errors || {};

                        const duplicateMsg = errors.ingredient_name?.find((m) => m.toLowerCase().includes('already'));
                        if (duplicateMsg) {
                            showDuplicateError(mode);
                            return;
                        }

                        const invalidInputMsg = Object.values(errors).flat().find((m) => m === 'Invalid input');
                        if (invalidInputMsg) {
                            showToast('Invalid input', 'error');
                            form.reset();
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

                        if (errors.image?.length) {
                            showImageError(errors.image[0]);
                            return;
                        }

                        showToast({title: 'Submission Error', message: (data && data.message) || 'Please check the form for missing or invalid fields.'}, 'error');
                    },
                });
            }

            function markAsWaste(btn) {
                submitAjaxAction({
                    url: `${baseUrl}/${btn.dataset.id}/waste-expired`,
                    method: 'PUT',
                    confirmMessage: {
                        title: 'Mark Expired Stock as Waste?',
                        message: `Are you sure you want to mark the expired stock of "${btn.dataset.name}" as waste? The system will automatically calculate and remove only the quantity that has passed its shelf life.`,
                        actionLabel: 'Mark as Waste',
                        type: 'warning'
                    },
                    toastKind: 'delete',
                    onSuccess: () => {
                        refreshTable(window.location.href);
                    },
                });
            }

            function deleteIngredient(btn) {
                submitAjaxAction({
                    url: `${baseUrl}/${btn.dataset.id}`,
                    method: 'DELETE',
                    confirmMessage: {
                        title: 'Delete Ingredient?',
                        message: `Are you sure you want to delete "${btn.dataset.name}"? This action cannot be undone.`,
                        actionLabel: 'Delete',
                        type: 'danger'
                    },
                    toastKind: 'delete',
                    onSuccess: () => refreshTable(window.location.href),
                });
            }

            window.showWasteDetails = function(event, data) {
                event.stopPropagation();
                const popover = document.getElementById('waste-popover');
                const content = document.getElementById('waste-popover-content');

                content.innerHTML = `
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">Ingredient:</span>
                            <span class="text-xs font-semibold text-gray-800">${data.name}</span>
                        </div>
                        <div class="p-3 bg-rose-50 rounded-lg border border-rose-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] font-medium text-rose-600">Already Expired:</span>
                                <span class="text-xs font-bold text-rose-700">${data.expired} ${data.unit}</span>
                            </div>
                        </div>
                        <div class="p-3 bg-orange-50 rounded-lg border border-orange-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] font-medium text-orange-600">Expiring Soon:</span>
                                <span class="text-xs font-bold text-orange-700">${data.expiring} ${data.unit}</span>
                            </div>
                        </div>
                    </div>
                `;

                popover.classList.remove('hidden');

                const trigger = event.target.closest('button');
                const triggerRect = trigger.getBoundingClientRect();
                const popoverRect = popover.getBoundingClientRect();
                const popoverHeight = popoverRect.height;
                const popoverWidth = popoverRect.width;

                let top = triggerRect.bottom + 8;
                if (top + popoverHeight > window.innerHeight) {
                    top = triggerRect.top - popoverHeight - 8;
                }
                popover.style.top = `${Math.max(8, top)}px`;

                let left = triggerRect.left;
                if (left + popoverWidth > window.innerWidth) {
                    left = window.innerWidth - popoverWidth - 16;
                }
                if (left < 16) {
                    left = 16;
                }
                popover.style.left = `${left}px`;
            };

            document.getElementById('waste-popover-close')?.addEventListener('click', () => {
                document.getElementById('waste-popover').classList.add('hidden');
            });

            document.addEventListener('click', (e) => {
                const popover = document.getElementById('waste-popover');
                if (popover && !popover.contains(e.target) && !e.target.closest('[onclick="showWasteDetails(...)"]')) {
                    popover.classList.add('hidden');
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.getElementById('waste-popover')?.classList.add('hidden');
                }
            });

            function sanitizeCsvCell(text) {
                return /^[=+\-@\t\r]/.test(text) ? `'${text}` : text;
            }

            document.getElementById('inventory-export-btn').addEventListener('click', () => {
                const currentParams = window.location.search;
                window.location.href = `{{ route('admin.inventory.export') }}${currentParams}`;
            });

            document.getElementById('add-ingredient-btn').addEventListener('click', () => openModal('create'));
            document.getElementById('ingredient-modal-close').addEventListener('click', closeModal);
            document.getElementById('ingredient-modal-cancel').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            form.addEventListener('submit', submitForm);
        })();
    </script>

@endsection