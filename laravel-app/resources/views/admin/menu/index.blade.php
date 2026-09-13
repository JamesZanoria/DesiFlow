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

    {{-- Table + pagination now live in a shared partial, wrapped in this container so
         the JS can swap the whole thing out after Add/Edit/Delete or a Prev/Next click
         without a full browser reload, see refreshTable() below. --}}
    <div id="menu-table-container">
        @include('admin.menu._table')
    </div>

    {{-- Add / Edit popup. overflow-y-auto on the backdrop + max-h-[90vh] on the panel is what
         stops the modal from overflowing past the viewport ("jumping out of the page") once
         it has enough content, Description, Category, Price and the image uploader
         all stacked together are taller than the old version. --}}
    <div id="menu-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md my-8 max-h-[90vh] overflow-y-auto">
            <div class="bg-indigo-50 rounded-t-xl px-6 py-4 flex items-center justify-between sticky top-0">
                <h2 id="menu-modal-title" class="text-lg font-semibold text-gray-800">Add New Menu Item</h2>
                <button type="button" id="menu-modal-close" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>

            <div class="p-6">
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

                    {{-- Status is intentionally not an editable field here, it's
                         managed exclusively through the quick-toggle dropdown on
                         each table row (see _table.blade.php / handleStatusChange
                         below), which is faster for the common case of just
                         flipping an item available/unavailable. This hidden field
                         still carries the value the server expects on every
                         save: defaulted to Active on create, and set to the
                         item's current status on edit so saving other fields
                         (price, description, image) never accidentally changes
                         availability as a side effect. --}}
                    <input type="hidden" name="is_available" id="menu-status-select">


                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Image</label>
                        <div id="menu-image-dropzone" class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer transition-colors hover:border-amber-400">
                            <input type="file" id="menu-image-input" name="image" accept="image/jpeg,image/png,.jpg,.jpeg,.png" class="hidden">
                            <input type="hidden" id="menu-remove-image" name="remove_image" value="0">

                            {{-- All inner content below is pointer-events-none (except the Remove
                                 button) so drag/hover events only ever fire on this outer container,
                                 not on the icon/text/image inside it. Without this, moving the cursor
                                 across those child elements fires dragleave/dragenter repeatedly as
                                 the browser crosses their boundaries, causing the state to flicker. --}}

                            {{-- Idle state: nothing selected yet --}}
                            <div id="menu-image-placeholder" class="pointer-events-none">
                                <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3.75 3.75M12 9.75l3.75 3.75M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                                <p class="text-sm text-gray-600">Click or drag to upload image</p>
                            </div>

                            {{-- Dragging a file over the dropzone --}}
                            <div id="menu-image-dragover-state" class="hidden pointer-events-none">
                                <svg class="w-8 h-8 text-amber-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                                <p class="text-sm font-medium text-amber-600">Release to upload</p>
                            </div>

                            {{-- A file has been picked/dropped and is ready (or currently uploading) --}}
                            <div id="menu-image-preview-wrap" class="hidden pointer-events-none">
                                <img id="menu-image-preview" class="mx-auto h-20 w-20 object-cover rounded-lg mb-2" alt="">
                                <p id="menu-image-filename" class="text-xs text-gray-500"></p>
                                <button type="button" id="menu-image-clear" class="mt-2 text-xs text-red-600 hover:underline pointer-events-auto">Remove</button>
                            </div>

                            {{-- Shown only while the XHR upload is actively in progress --}}
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

                    <div class="flex justify-end gap-3">
                        <button type="button" id="menu-modal-cancel"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-amber-500">
                            Save Item
                        </button>
                    </div>
                </form>
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

            // ---------------------------------------------------------------
            // Field error display, shown only after the admin clicks Save and
            // the server rejects the submission (duplicate name, numbers in a
            // text field, etc.). Nothing is checked or blocked while typing;
            // this mirrors how the duplicate-name error already works.
            // ---------------------------------------------------------------
            const errorableFields = ['item_name', 'category'];

            function clearFieldErrors() {
                errorableFields.forEach((name) => {
                    form.elements[name]?.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                });
            }

            // Filled red circle + white exclamation mark, matches the reference
            // design exactly. Shared by every error message in this modal so the
            // visual language stays consistent.
            function errorIconSvg() {
                return `
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <circle cx="10" cy="10" r="10" />
                        <rect x="9" y="4.5" width="2" height="7" rx="1" fill="white" />
                        <circle cx="10" cy="14" r="1.15" fill="white" />
                    </svg>
                `;
            }

            // Duplicate-name errors get their own handler rather than reusing
            // a generic field error function, because the desired behavior differs by mode:
            //   - Add New Item: clear the whole form (the error message stays
            //     visible) so the admin can immediately type a completely
            //     different item, but the Item Name field keeps its red outline
            //     even once emptied, exactly as shown in the reference design,
            //     as a persistent visual cue that this is the field to fix.
            //   - Edit: the admin is mid-change on a specific existing item, so
            //     wiping the form would lose legitimate edits, just highlight
            //     the conflicting field instead, same as any other field error.
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

            // Once the admin starts editing any field that previously errored,
            // clear that field's red outline so it doesn't linger after they've
            // clearly started correcting it. The message stays until they submit
            // again, only re-submitting (successfully or not) refreshes it.
            errorableFields.forEach((name) => {
                const field = form.elements[name];
                if (!field) return;

                field.addEventListener('input', () => {
                    field.classList.remove('border-red-400', 'ring-1', 'ring-red-300');
                });
            });

            // ---------------------------------------------------------------
            // Table refresh (fixes pagination going stale after Add/Edit/Delete)
            //
            // Instead of hand-patching the DOM (appending a row, decrementing a
            // counter, etc.), which drifts out of sync with the real, sorted,
            // server-side pagination the moment a page fills up or an item's
            // alphabetical position changes, every mutation just re-asks the
            // server for the current page's table+pagination fragment and swaps
            // it in. This is what makes pagination "automatic": no manual
            // reload is ever required to see correct page counts/links again.
            // ---------------------------------------------------------------
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

                    // Keeps the Category filter chip's option list current,
                    // without this, a category introduced (or removed) by the
                    // add/edit/delete that just happened wouldn't show up
                    // until a real page reload, since this popover was
                    // previously only ever rendered once on initial page
                    // load. No re-binding needed for the swapped-in content:
                    // its click/search handling is delegated globally in
                    // admin.js, not attached per-element.
                    if (data.categoryOptionsHtml) {
                        const categoryPopover = document.querySelector('[data-filter-select][data-name="category"] .filter-select-popover');
                        if (categoryPopover) categoryPopover.innerHTML = data.categoryOptionsHtml;
                    }

                    // Same idea for the search box's suggestion list, an
                    // item just added/renamed/deleted should be reflected in
                    // suggestions immediately, not just in the table.
                    if (data.itemSuggestionsHtml) {
                        const suggestList = document.querySelector('[data-search-suggest] .search-suggest-list');
                        if (suggestList) suggestList.innerHTML = data.itemSuggestionsHtml;
                    }

                    // Keep the address bar (and back/refresh behavior) in sync with
                    // whichever page/search/category is now actually being shown.
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

            function openModal(mode, data) {
                form.reset();
                uploadStartedAt = null;
                clearFieldErrors();

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
                    form.item_name.value = data.itemName;
                    form.category.value = data.category;
                    form.price.value = data.price;
                    form.is_available.value = data.isAvailable;
                    resetImageDropzone(data.imageUrl || null);
                }

                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                captureSnapshot();
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            // Image dropzone elements
            const dropzone = document.getElementById('menu-image-dropzone');
            const fileInput = document.getElementById('menu-image-input');
            const removeImageField = document.getElementById('menu-remove-image');

            // ---------------------------------------------------------------
            // Disable Save until something has actually changed from what the
            // modal opened with, applies to both Add (a still-blank form
            // shouldn't be submittable) and Edit (no point re-saving
            // unmodified data). A snapshot of every field Save would actually
            // submit is captured the moment the modal opens; Save stays
            // disabled until the live form diverges from that snapshot.
            // ---------------------------------------------------------------
            const saveButton = form.querySelector('button[type="submit"]');
            let savedSnapshot = '';

            function getFormSnapshot() {
                const fields = ['item_name', 'category', 'price', 'is_available', 'remove_image']
                    .map((name) => form.elements[name]?.value ?? '');

                // A File object can't be meaningfully diffed by value, so a
                // newly-selected file is represented by its name+size+modified
                // timestamp instead, enough to detect "a different file was
                // chosen" without needing to read the file's actual bytes.
                const file = fileInput.files[0];
                fields.push(file ? `${file.name}:${file.size}:${file.lastModified}` : '');

                return fields.join('|');
            }

            function captureSnapshot() {
                savedSnapshot = getFormSnapshot();
                updateSaveButtonState();
            }

            function updateSaveButtonState() {
                saveButton.disabled = getFormSnapshot() === savedSnapshot;
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

            // Single source of truth for which of the dropzone's states is
            // showing. Previously each handler (dragover/dragleave/drop/etc.)
            // hid-then-showed states with its own separate add/remove calls,
            // which was how "Click or drag to upload image" and "Release to
            // upload" ended up rendering on top of each other, nothing
            // actually enforced that only one could be visible at a time.
            // Routing every state change through this one function makes that
            // combination structurally impossible: it always hides all five
            // states first, then shows exactly one.
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
                    // Falls back to the idle icon/text underneath the red
                    // border + message, rather than a blank dropzone.
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

            // accept="..." on the <input> only filters what's offered in the
            // file picker dialog, it does nothing to police a drag-and-drop,
            // so both paths need this same real check before a file is
            // accepted. The server (MenuController's `mimes:jpeg,png,jpg`
            // rule) is still the actual enforcement, since this can be
            // bypassed by anyone not using the browser UI at all; this is
            // purely so a mistaken drop gets instant feedback instead of a
            // round trip to the server.
            const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png'];
            const ACCEPTED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

            function isValidImageFile(file) {
                if (ACCEPTED_IMAGE_TYPES.includes(file.type)) return true;
                // Fallback to extension when the browser/OS didn't set a
                // MIME type at all (some file managers leave file.type blank).
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
                // Restore whatever state was showing before the drag started.
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
                const saveBtn = form.querySelector('button[type="submit"]');
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
                        // Re-fetch the current page from the server instead of patching
                        // the DOM by hand, guarantees the row count, sort order, and
                        // Prev/Next state are all correct immediately, no reload needed.
                        await refreshTable(mode === 'create' ? indexUrl : window.location.href);
                        closeModal();
                    },
                    // A 422 lands here instead of the generic bullet-list. This branch
                    // never calls showToast and never calls closeModal, the admin stays
                    // on the form, sees exactly what's wrong (with the same icon + red
                    // outline treatment for every field), and can immediately fix just
                    // that field without losing anything else they'd filled in.
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

                        // Server-side image errors (e.g. a file over 2MB, which
                        // the client-side check above never catches) get the
                        // same under-dropzone treatment as a client-rejected
                        // file, not the generic top-of-form error box.
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
                        // Availability doesn't change total item count or sort order,
                        // so a lightweight in-place style swap is enough here, no need
                        // to round-trip the whole table.
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
                    confirmMessage: `Delete "${btn.dataset.itemName}"? This cannot be undone.`,
                    toastKind: 'delete',
                    onSuccess: () => {
                        // Deleting can also shift pagination (e.g. removing the last item
                        // on a page), so resync from the server here too.
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
                scope.querySelectorAll('.status-select').forEach((select) => {
                    select.addEventListener('change', () => handleStatusChange(select));
                });
            }

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