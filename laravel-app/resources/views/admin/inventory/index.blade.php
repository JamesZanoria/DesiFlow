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
                'name' => 'sort',
                'label' => 'Status',
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
                        <input type="text" name="category" list="ingredient-category-options"
                            class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="e.g. Spices, Grains, Dairy (optional)">
                        <datalist id="ingredient-category-options">
                            @foreach ($categories as $category)
                                <option value="{{ $category }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measurement</label>
                            <input type="text" name="unit_of_measurement" list="ingredient-unit-options" required
                                class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="e.g. kg, g, pcs, L">
                            <datalist id="ingredient-unit-options">
                                @foreach ($units as $unit)
                                    <option value="{{ $unit }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (₱)</label>
                            <input type="number" step="0.01" min="0" name="unit_cost" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Restock Threshold</label>
                            <input type="number" step="0.01" min="0" name="restock_threshold" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="e.g. 5.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Shelf Life (days)</label>
                            <input type="number" min="0" name="shelf_life_days" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="optional">
                        </div>
                    </div>

                    {{-- Add mode only: sets initial stock via a real ledger entry, not a raw field --}}
                    <div id="ingredient-initial-stock-field" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Initial Stock (optional)</label>
                        <input type="number" step="0.01" min="0" name="initial_stock" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                        <p class="text-xs text-gray-400 mt-1">If you already have some on hand, enter it here, this records a stock-in movement automatically.</p>
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

            const initialStockField = document.getElementById('ingredient-initial-stock-field');
            const currentStockDisplay = document.getElementById('ingredient-current-stock-display');
            const currentStockValue = document.getElementById('ingredient-current-stock-value');
            const currentStockUnit = document.getElementById('ingredient-current-stock-unit');

            function numberFormat(value) {
                return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // ---------------------------------------------------------------
            // Field error display, shown only after Save is clicked and the
            // server rejects the submission (duplicate name, numbers in a text
            // field). Same visual language as Menu Management: a filled red
            // circle icon + plain red text, no boxed background, and a red
            // outline around whichever field caused the problem. The modal is
            // never closed by any of this.
            // ---------------------------------------------------------------
            const errorableFields = ['ingredient_name', 'unit_of_measurement'];

            function clearFieldErrors() {
                errorableFields.forEach((name) => {
                    form.elements[name]?.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
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

                const field = form.elements[fieldName];
                if (field) {
                    field.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                    field.focus();
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
                    initialStockField.classList.remove('hidden');
                    currentStockDisplay.classList.add('hidden');
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
                    field.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
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
                    initialStockField.classList.remove('hidden');
                    currentStockDisplay.classList.add('hidden');
                    resetImageDropzone(null);
                } else {
                    title.textContent = 'Edit Ingredient';
                    form.dataset.mode = 'edit';
                    form.dataset.ingredientId = data.id;
                    form.ingredient_name.value = data.name;
                    form.category.value = data.category || '';
                    form.unit_of_measurement.value = data.unit;
                    form.unit_cost.value = data.cost;
                    form.restock_threshold.value = data.threshold;
                    form.shelf_life_days.value = data.shelfLife || '';

                    initialStockField.classList.add('hidden');
                    currentStockDisplay.classList.remove('hidden');
                    currentStockValue.textContent = numberFormat(data.currentStock ?? 0);
                    currentStockUnit.textContent = data.unit;
                    resetImageDropzone(data.imageUrl || null);
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

            function deleteIngredient(btn) {
                submitAjaxAction({
                    url: `${baseUrl}/${btn.dataset.id}`,
                    method: 'DELETE',
                    confirmMessage: `Delete "${btn.dataset.name}"? This cannot be undone.`,
                    toastKind: 'delete',
                    onSuccess: () => {
                        // Deletion can shift pagination too, so resync from the server.
                        refreshTable(window.location.href);
                    },
                    // onError is intentionally left unhandled here, submitAjaxAction
                    // already shows the server's specific message (e.g. "can't delete,
                    // it has recipe or stock movement history") via the toast itself.
                });
            }

            function attachRowListeners(scope) {
                scope.querySelectorAll('.edit-ingredient-btn').forEach((btn) => {
                    btn.addEventListener('click', () => openModal('edit', btn.dataset));
                });
                scope.querySelectorAll('.delete-ingredient-btn').forEach((btn) => {
                    btn.addEventListener('click', () => deleteIngredient(btn));
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