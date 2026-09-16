@extends('layouts.app')

@section('title', 'Menu Management')

@section('content')

    <div class="flex items-start justify-between mb-6 gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Menu Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage your restaurant's items, categories, and availability.</p>
        </div>

        <form method="GET" action="{{ route('admin.menu.index') }}" class="flex items-center gap-2">
            @include('admin.partials._filter_search', [
                'name' => 'search',
                'placeholder' => 'Search menu items...',
                'value' => request('search'),
                'suggestions' => $allItemNames,
            ])

            @include('admin.partials._filter_select', [
                'name' => 'category',
                'label' => 'Category',
                'value' => request('category'),
                'options' => $categories->mapWithKeys(fn ($c) => [$c => $c]),
            ])

            <button type="button" id="add-menu-item-btn"
                class="flex items-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add New Item
            </button>
        </form>
    </div>

    <div id="menu-table-container">
        @include('admin.menu._table')
    </div>

    {{-- Availability Details Popover --}}
    <div id="availability-popover" class="hidden fixed z-[60] bg-white border shadow-xl rounded-lg p-4 w-72 max-w-[calc(100vw-32px)] max-h-[80vh] overflow-y-auto pointer-events-auto">
        <div class="flex items-center justify-between mb-3 pb-2 border-b sticky top-0 bg-white z-10">
            <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wide">Why unavailable?</h4>
            <button type="button" id="availability-popover-close" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div id="availability-popover-content" class="space-y-3">
            {{-- Content injected by JS --}}
        </div>
        <div class="mt-4 pt-2 border-t sticky bottom-0 bg-white">
            <p class="text-[10px] text-gray-400 italic">Reasons: Expired, out of stock, or insufficient usable stock.</p>
        </div>
    </div>

    {{-- Add / Edit popup --}}
    <div id="menu-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md my-8 max-h-[90vh] flex flex-col">
            <div class="bg-indigo-50 rounded-t-xl px-6 py-4 flex items-center justify-between flex-none">
                <h2 id="menu-modal-title" class="text-lg font-semibold text-gray-800">Add New Menu Item</h2>
                <button type="button" id="menu-modal-close" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto p-6">
                <form id="menu-form">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
                        <input type="text" name="item_name" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="e.g. Butter Chicken">
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <input type="text" name="category" list="menu-category-options" required
                                class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Select or type new">
                            <datalist id="menu-category-options">
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (₱)</label>
                            <input type="number" step="0.01" min="0" name="price" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Ingredients / Recipe</label>
                            <button type="button" id="add-recipe-line-btn" class="text-xs font-medium text-amber-600 hover:text-amber-700 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Add Ingredient
                            </button>
                        </div>

                        <div id="recipe-lines-container" class="space-y-2 mb-4 max-h-60 overflow-y-auto pr-2">
                            {{-- Recipe lines will be injected here by JS --}}
                        </div>

                        {{-- Ingredient Selector (hidden by default) --}}
                        <div id="recipe-ingredient-selector" class="hidden mb-4 p-3 bg-gray-50 border rounded-lg">
                            <div class="relative" data-combo-select="recipe-ing-select" data-allow-custom="false" data-placeholder="Search ingredients...">
                                <input type="hidden" name="recipe_temp_ing" id="recipe-temp-ing">
                                <button type="button" class="combo-select-trigger w-full flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm text-left hover:border-gray-400 focus:outline-none">
                                    <span class="combo-select-value min-w-0 truncate text-gray-400">Select an ingredient...</span>
                                    <svg class="combo-select-chevron w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                </button>
                                <div class="combo-select-popover hidden absolute left-0 right-0 mt-2 bg-white border rounded-lg shadow-lg z-30 overflow-hidden">
                                    <div class="p-2 border-b">
                                        <input type="text" class="combo-select-search w-full border rounded-md px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-amber-400" placeholder="Search ingredients...">
                                    </div>
                                    <div class="combo-select-list max-h-48 overflow-y-auto py-1">
                                        @foreach (\App\Models\Ingredient::all() as $ing)
                                            <button type="button" class="combo-select-option w-full flex items-center justify-between gap-2 px-3 py-2 text-xs text-left hover:bg-gray-50 text-gray-600" data-value="{{ $ing->ingredient_id }}" data-label="{{ $ing->ingredient_name }} ({{ $ing->unit_of_measurement }})" data-unit="{{ $ing->unit_of_measurement }}">
                                                <span class="truncate">{{ $ing->ingredient_name }} ({{ $ing->unit_of_measurement }})</span>
                                                <svg class="combo-select-check hidden w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            </button>
                                        @endforeach
                                    </div>
                                    <p class="combo-select-empty hidden px-3 py-4 text-center text-xs text-gray-400">No matching ingredients.</p>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 mt-2">
                                <button type="button" id="cancel-recipe-select" class="px-2 py-1 text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                                <button type="button" id="confirm-recipe-select" class="px-2 py-1 text-xs bg-amber-500 text-white rounded hover:bg-amber-600">Add to Recipe</button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="is_available" id="menu-status-select">


                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Image</label>
                        <div id="menu-image-dropzone" class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer transition-colors hover:border-amber-400">
                            <input type="file" id="menu-image-input" name="image" accept="image/jpeg,image/png,.jpg,.jpeg,.png" class="hidden">
                            <input type="hidden" id="menu-remove-image" name="remove_image" value="0">

                            <div id="menu-image-placeholder" class="pointer-events-none">
                                <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3.75 3.75M12 9.75l3.75 3.75M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                                <p class="text-sm text-gray-600">Click or drag to upload image</p>
                            </div>

                            <div id="menu-image-dragover-state" class="hidden pointer-events-none">
                                <svg class="w-8 h-8 text-amber-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                                <p class="text-sm font-medium text-amber-600">Release to upload</p>
                            </div>

                            <div id="menu-image-preview-wrap" class="hidden pointer-events-none">
                                <img id="menu-image-preview" class="mx-auto h-20 w-20 object-cover rounded-lg mb-2" alt="">
                                <p id="menu-image-filename" class="text-xs text-gray-500"></p>
                                <button type="button" id="menu-image-clear" class="mt-2 text-xs text-red-600 hover:underline pointer-events-auto">Remove</button>
                            </div>

                            <div id="menu-image-uploading" class="hidden pointer-events-none">
                                <p id="menu-image-upload-text" class="text-xs text-gray-600 mb-2">Uploading... 0%</p>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div id="menu-image-upload-bar" class="bg-amber-500 h-2 rounded-full transition-all duration-150" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">Accepted formats: JPG, JPEG, PNG only. Max 2MB.</p>
                        <p id="menu-image-error" class="hidden text-xs text-red-600 mt-1"></p>
                    </div>
                </form>
            </div>

            <div class="px-6 py-4 border-t bg-gray-50 rounded-b-xl flex justify-end gap-3 flex-none">
                <button type="button" id="menu-modal-cancel"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit" id="menu-modal-save" form="menu-form"
                    class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600">
                    Save Item
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('menu-modal');
            const form = document.getElementById('menu-form');
            const title = document.getElementById('menu-modal-title');
            const storeUrl = '{{ route('admin.menu.store') }}';
            const baseUrl = '{{ url('admin/menu') }}';
            const indexUrl = '{{ route('admin.menu.index') }}';

            const errorableFields = ['item_name', 'category'];

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

            function showDuplicateError(mode) {
                showToast('Item already exists', 'error');

                if (mode === 'create') {
                    form.reset();
                    clearFieldErrors();
                    resetImageDropzone(null);
                    form.is_available.value = '1';
                    uploadStartedAt = null;
                }

                form.item_name.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                form.item_name.focus();
                if (mode !== 'create') form.item_name.select();
            }

            errorableFields.forEach((name) => {
                const field = form.elements[name];
                if (!field) return;

                field.addEventListener('input', () => {
                    field.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                });
            });

            function tableContainer() {
                return document.getElementById('menu-table-container');
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

                    if (data.categoryOptionsHtml) {
                        const categoryPopover = document.querySelector('[data-filter-select][data-name="category"] .filter-select-popover');
                        if (categoryPopover) categoryPopover.innerHTML = data.categoryOptionsHtml;
                    }

                    if (data.itemSuggestionsHtml) {
                        const suggestList = document.querySelector('[data-search-suggest] .search-suggest-list');
                        if (suggestList) suggestList.innerHTML = data.itemSuggestionsHtml;
                    }

                    history.replaceState(null, '', targetUrl);
                } catch (err) {
                    console.error('Failed to refresh menu table', err);
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

            const recipeLinesContainer = document.getElementById('recipe-lines-container');
            const recipeSelector = document.getElementById('recipe-ingredient-selector');
            const recipeIngSelect = document.getElementById('recipe-temp-ing');

            function addRecipeLine(ingredientId, ingredientLabel, unit, qty = 1.00) {
                const rowId = `recipe-row-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
                const row = document.createElement('div');
                row.className = 'flex items-center gap-2 bg-white border rounded-lg p-2 shadow-sm';
                row.id = rowId;
                row.innerHTML = `
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-medium text-gray-700 truncate block">${ingredientLabel}</span>
                        <input type="hidden" name="recipe[${rowId}][ingredient_id]" value="${ingredientId}">
                    </div>
                    <div class="flex items-center gap-1 w-32">
                        <input type="number" step="0.01" min="0.001" name="recipe[${rowId}][qty_consumed]"
                            value="${qty}" class="w-full border rounded px-2 py-1 text-xs text-right focus:ring-1 focus:ring-amber-400 outline-none"
                            placeholder="0.00">
                        <span class="text-[10px] text-gray-400 w-8 text-center">${unit}</span>
                    </div>
                    <button type="button" class="remove-recipe-line-btn text-gray-300 hover:text-red-500 p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                `;

                row.querySelector('.remove-recipe-line-btn').addEventListener('click', () => {
                    row.remove();
                    updateSaveButtonState();
                });

                row.querySelector('input').addEventListener('input', updateSaveButtonState);

                recipeLinesContainer.appendChild(row);
                updateSaveButtonState();
            }

            document.getElementById('add-recipe-line-btn').addEventListener('click', () => {
                recipeSelector.classList.remove('hidden');
                setComboSelectValue(document.querySelector('[data-combo-select="recipe-ing-select"]'), '', 'Select an ingredient...');
            });

            document.getElementById('cancel-recipe-select').addEventListener('click', () => {
                recipeSelector.classList.add('hidden');
            });

            document.getElementById('confirm-recipe-select').addEventListener('click', () => {
                const val = recipeIngSelect.value;
                if (!val) return;

                const selectedOption = document.querySelector(`[data-combo-select="recipe-ing-select"] .combo-select-option[data-value="${val}"]`);
                const label = selectedOption?.dataset.label || '';
                const unit = selectedOption?.dataset.unit || '';
                const name = label.replace(` (${unit})`, '');

                addRecipeLine(val, name, unit);
                recipeSelector.classList.add('hidden');
            });

            async function openModal(mode, data) {

                form.reset();
                uploadStartedAt = null;
                clearFieldErrors();
                recipeLinesContainer.innerHTML = '';

                if (mode === 'create') {
                    title.textContent = 'Add New Menu Item';
                    form.dataset.mode = 'create';
                    form.dataset.itemId = '';
                    form.is_available.value = '1';
                    resetImageDropzone(null);
                } else {
                    title.textContent = 'Edit Menu Item';
                    form.dataset.mode = 'edit';
                    form.dataset.itemId = data.id;

                    try {
                        const response = await fetch(`${baseUrl}/${data.id}`);
                        if (!response.ok) throw new Error('Failed to fetch item details');
                        const item = await response.json();

                        form.item_name.value = item.item_name;
                        form.category.value = item.category;
                        form.price.value = item.price;
                        form.is_available.value = item.is_available ? '1' : '0';
                        resetImageDropzone(item.image_url || null);

                        if (item.recipe && item.recipe.length > 0) {
                            item.recipe.forEach(line => {
                                addRecipeLine(line.ingredient_id, line.ingredient_name, line.unit, line.qty_consumed);
                            });
                        }
                    } catch (err) {
                        console.error('Error loading item:', err);
                        showToast('Could not load item details.', 'error');
                        return;
                    }
                }

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                captureSnapshot();
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            const dropzone = document.getElementById('menu-image-dropzone');
            const fileInput = document.getElementById('menu-image-input');
            const removeImageField = document.getElementById('menu-remove-image');

            const saveButton = document.getElementById('menu-modal-save');
            let savedSnapshot = '';

            function getFormSnapshot() {
                const fields = ['item_name', 'category', 'price', 'is_available', 'remove_image']
                    .map((name) => form.elements[name]?.value ?? '');

                const file = fileInput.files[0];
                fields.push(file ? `${file.name}:${file.size}:${file.lastModified}` : '');

                return fields.join('|');
            }

            function updateSaveButtonState() {
                const currentSnapshot = getFormSnapshot();
                saveButton.disabled = (currentSnapshot === savedSnapshot);
            }

            function captureSnapshot() {
                savedSnapshot = getFormSnapshot();
                updateSaveButtonState();
            }


            form.addEventListener('input', updateSaveButtonState);
            const imagePlaceholder = document.getElementById('menu-image-placeholder');
            const dragoverState = document.getElementById('menu-image-dragover-state');
            const previewWrap = document.getElementById('menu-image-preview-wrap');
            const previewImg = document.getElementById('menu-image-preview');
            const previewName = document.getElementById('menu-image-filename');
            const uploadingState = document.getElementById('menu-image-uploading');
            const uploadText = document.getElementById('menu-image-upload-text');
            const uploadBar = document.getElementById('menu-image-upload-bar');
            const imageErrorMsg = document.getElementById('menu-image-error');

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
                    updateSaveButtonState();
                };
                reader.readAsDataURL(file);
            }

            function resetImageDropzone(existingImageUrl) {
                fileInput.value = '';
                removeImageField.value = '0';
                if (existingImageUrl) {
                    previewImg.src = existingImageUrl;
                    previewName.textContent = 'Current image (click to replace)';
                    setDropzoneState('preview');
                } else {
                    setDropzoneState('idle');
                }
            }

            let uploadStartedAt = null;

            function showUploadProgress(percent, loadedBytes, totalBytes) {
                if (!uploadStartedAt) uploadStartedAt = Date.now();
                setDropzoneState('uploading');
                uploadBar.style.width = `${percent}%`;

                const elapsedSeconds = (Date.now() - uploadStartedAt) / 1000;
                const bytesPerSecond = elapsedSeconds > 0 ? loadedBytes / elapsedSeconds : 0;
                const remainingBytes = totalBytes - loadedBytes;
                const etaSeconds = bytesPerSecond > 0 ? Math.ceil(remainingBytes / bytesPerSecond) : null;

                uploadText.textContent = (etaSeconds && percent < 100)
                    ? `Uploading... ${percent}% (about ${etaSeconds}s left)`
                    : `Uploading... ${percent}%`;
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
                if (e.target.id !== 'menu-image-clear') fileInput.click();
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
            document.getElementById('menu-image-clear').addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.value = '';
                removeImageField.value = '1';
                setDropzoneState('idle');
                updateSaveButtonState();
            });

            async function submitForm(e) {
                e.preventDefault();
                const mode = form.dataset.mode;
                const itemId = form.dataset.itemId;
                const saveBtn = document.getElementById('menu-modal-save');
                const hasNewFile = fileInput.files.length > 0;

                uploadStartedAt = null;
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';

                await submitAjaxForm({
                    form,
                    url: mode === 'create' ? storeUrl : `${baseUrl}/${itemId}`,
                    method: mode === 'create' ? 'POST' : 'PUT',
                    toastKind: mode === 'create' ? 'success' : 'edit',
                    onUploadProgress: hasNewFile ? showUploadProgress : undefined,
                    onSuccess: async () => {
                        await refreshTable(mode === 'create' ? indexUrl : window.location.href);
                        closeModal();
                    },
                    onValidationError: (data) => {
                        const errors = data?.errors || {};

                        const duplicateMsg = errors.item_name?.find((m) => m.toLowerCase().includes('already'));
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

                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Item';
            }

            async function handleStatusChange(select) {
                const isAvailable = select.value === '1';
                const previousValue = isAvailable ? '0' : '1';

                await submitAjaxAction({
                    url: `${baseUrl}/${select.dataset.id}`,
                    method: 'PUT',
                    extraFields: {
                        item_name: select.dataset.itemName,
                        category: select.dataset.category,
                        price: select.dataset.price,
                        is_available: isAvailable ? '1' : '0',
                    },
                    toastKind: isAvailable ? 'available' : 'unavailable',
                    toastMessage: isAvailable ? 'Marked available.' : 'Marked unavailable.',
                    onSuccess: () => {
                        select.classList.remove('bg-green-50', 'text-green-700', 'border-green-200', 'bg-red-50', 'text-red-700', 'border-red-200');
                        select.classList.add(...(isAvailable
                            ? ['bg-green-50', 'text-green-700', 'border-green-200']
                            : ['bg-red-50', 'text-red-700', 'border-red-200']));
                        select.value = isAvailable ? '1' : '0';
                        const chevron = select.parentElement.querySelector('.status-chevron');
                        if (chevron) {
                            chevron.classList.remove('text-green-700', 'text-red-700');
                            chevron.classList.add(isAvailable ? 'text-green-700' : 'text-red-700');
                        }
                        const row = select.closest('tr');
                        const editBtn = row.querySelector('.edit-menu-item-btn');
                        if (editBtn) editBtn.dataset.isAvailable = isAvailable ? '1' : '0';
                    },
                    onError: () => {
                        select.value = previousValue;
                    },
                });
            }

            function deleteItem(btn) {
                submitAjaxAction({
                    url: `${baseUrl}/${btn.dataset.id}`,
                    method: 'DELETE',
                    confirmMessage: {
                        title: 'Delete Menu Item?',
                        message: `Are you sure you want to delete "${btn.dataset.itemName}"? This action cannot be undone.`,
                        actionLabel: 'Delete',
                        type: 'danger'
                    },
                    toastKind: 'delete',
                    onSuccess: () => {
                        refreshTable(window.location.href);
                    },
                });
            }

            function attachRowListeners(scope) {
                scope.querySelectorAll('.edit-menu-item-btn').forEach((btn) => {
                    btn.addEventListener('click', () => openModal('edit', btn.dataset));
                });
                scope.querySelectorAll('.delete-menu-item-btn').forEach((btn) => {
                    btn.addEventListener('click', () => deleteItem(btn));
                });
            }

            window.showAvailabilityDetails = function(event, itemId, details) {
                event.stopPropagation();
                const popover = document.getElementById('availability-popover');
                const content = document.getElementById('availability-popover-content');

                let html = '';
                if (details.failing_ingredients && details.failing_ingredients.length > 0) {
                    details.failing_ingredients.forEach(ing => {
                        const isExpired = ing.status === 'expired';
                        html += `
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-semibold text-gray-800">• ${ing.name}</span>
                                ${isExpired
                                    ? `<span class="text-[11px] text-red-600 font-medium ml-2">Expired</span>`
                                    : `<div class="flex justify-between text-[11px] text-gray-500 ml-2">
                                        <span>Required: ${ing.required} ${ing.unit || ''}</span>
                                        <span>Available: ${ing.available} ${ing.unit || ''}</span>
                                    </div>`
                                }
                            </div>
                        `;
                    });
                } else {
                    html = `<p class="text-xs text-gray-600">${details.reason || 'Unknown issue.'}</p>`;
                }

                content.innerHTML = html;
                popover.classList.remove('hidden');

                // Position popover relative to the clicked icon
                const trigger = event.target.closest('button');
                const triggerRect = trigger.getBoundingClientRect();
                const popoverRect = popover.getBoundingClientRect();
                const popoverHeight = popoverRect.height;
                const popoverWidth = popoverRect.width;

                // Vertical positioning: prefer above, fallback to below
                let top = triggerRect.bottom + 8;
                if (top + popoverHeight > window.innerHeight) {
                    top = triggerRect.top - popoverHeight - 8;
                }
                popover.style.top = `${Math.max(8, top)}px`;

                // Horizontal positioning: anchor to left, but keep in viewport
                let left = triggerRect.left;
                if (left + popoverWidth > window.innerWidth) {
                    left = window.innerWidth - popoverWidth - 16;
                }
                if (left < 16) {
                    left = 16;
                }
                popover.style.left = `${left}px`;
            };

            document.getElementById('availability-popover-close')?.addEventListener('click', () => {
                document.getElementById('availability-popover').classList.add('hidden');
            });

            document.addEventListener('click', (e) => {
                const popover = document.getElementById('availability-popover');
                if (!popover.contains(e.target) && !e.target.closest('[onclick="showAvailabilityDetails(...)"]')) {
                    popover.classList.add('hidden');
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.getElementById('availability-popover').classList.add('hidden');
                }
            });

            document.getElementById('add-menu-item-btn').addEventListener('click', () => openModal('create'));
            document.getElementById('menu-modal-close').addEventListener('click', closeModal);
            document.getElementById('menu-modal-cancel').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            form.addEventListener('submit', submitForm);

            attachRowListeners(tableContainer());
            attachPaginationListeners(tableContainer());
        })();
    </script>

@endsection