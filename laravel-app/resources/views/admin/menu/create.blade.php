@extends('layouts.app')

@section('title', isset($editingItem) ? 'Edit Menu Item' : 'New Menu Item')

@section('content')
    <div class="h-screen overflow-hidden flex flex-col p-6 bg-slate-50/50">
        {{-- Breadcrumb Navigation --}}
        <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400 mb-6">
            <a href="{{ route('admin.menu.index') }}" class="hover:text-amber-600 transition-colors flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                Menu Management
            </a>
            <span class="text-slate-300">
                <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </span>
            <span class="text-slate-900">{{ isset($editingItem) ? 'Edit Studio' : 'Creation Studio' }}</span>
        </nav>

        <div class="flex-1 min-h-0 overflow-hidden flex flex-col lg:flex-row gap-8">
            {{-- LEFT: Configuration Area --}}
            <div class="flex-1 min-h-0 overflow-hidden flex flex-col pr-2">
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-8 md:p-12 relative flex flex-col h-full overflow-hidden">
                    {{-- Stepper Progress --}}
                    <div class="mb-16 relative px-2 flex-none">
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-slate-100 w-full -z-10"></div>
                        <div id="studio-progress-bar" class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-amber-500 transition-all duration-500 -z-10" style="width: 0%"></div>

                        <div class="flex items-center justify-between relative">
                            @php $steps = ['Identity', 'Composition', 'Visuals', 'Review']; @endphp
                            @foreach($steps as $index => $step)
                                <div class="step-indicator flex flex-col items-center gap-3 z-10" data-step="{{ $index + 1 }}">
                                    <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center text-xs font-bold text-slate-400 transition-all duration-300 shadow-sm">{{ $index + 1 }}</div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $step }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto relative custom-scrollbar pb-12">
                        <form id="menu-form" class="h-full space-y-12" action="{{ isset($editingItem) ? route('admin.menu.update', $editingItem->menu_item_id) : route('admin.menu.store') }}" method="POST">
                            @csrf
                            @if(isset($editingItem))
                                @method('PUT')
                            @endif
                            @if(isset($editingItem))
                                <input type="hidden" id="menu-item-id" name="menu_item_id" value="{{ $editingItem->menu_item_id }}">
                            @endif
                            {{-- STEP 1: IDENTITY --}}
                            <div id="step-1" class="studio-step h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Item Identity</h3>
                                    <p class="text-sm text-slate-500">Define the core details of your menu item.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-8">
                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Item Name <span class="text-red-400">*</span>
                                        </label>
                                        <input type="text" name="item_name" id="studio-item-name" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300" placeholder="e.g. Butter Chicken">
                                        <p id="item-name-error" class="hidden text-[10px] font-bold text-red-500 mt-1.5 ml-1"></p>
                                    </div>

                                    <div class="group">
                                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                            Description
                                        </label>
                                        <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300" placeholder="Describe the flavors, ingredients, and experience..."></textarea>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                                        <div class="group">
                                            <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                                Category <span class="text-red-400">*</span>
                                            </label>
                                            <input type="text" name="category" list="menu-category-options" required
                                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300" placeholder="Select or type new">
                                            <datalist id="menu-category-options">
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category }}"></option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                        <div class="group">
                                            <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-widest group-focus-within:text-amber-600 transition-colors">
                                                Price (₱) <span class="text-red-400">*</span>
                                            </label>
                                            <input type="number" step="0.01" min="0" name="price" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- STEP 2: COMPOSITION --}}
                            <div id="step-2" class="studio-step hidden h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Recipe Composition</h3>
                                    <p class="text-sm text-slate-500">Our culinary AI suggests ingredients based on your item name.</p>
                                </div>

                                <div class="bg-slate-50 rounded-3xl p-6 border border-slate-100 space-y-8 flex flex-col h-auto min-h-0">
                                    <div class="space-y-4">
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Search All Ingredients</h4>
                                        <div class="flex flex-col sm:flex-row items-center gap-3">
                                            <div class="relative w-full sm:w-auto flex-1 group">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-amber-500 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                </div>
                                                <input type="text" id="ing-search-input"
                                                       class="w-full rounded-xl border border-slate-200 pl-11 pr-4 py-3 text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-white hover:border-slate-300"
                                                       placeholder="Search and add ingredients..." autocomplete="off">

                                                <div id="ing-dropdown-list" class="hidden absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-2xl max-h-80 overflow-y-auto custom-scrollbar py-2">
                                                    {{-- Sections injected by JS --}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-8 border-t border-slate-200 flex-1 min-h-0 flex flex-col">
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Active Recipe <span class="text-red-400">*</span></h4>
                                            <span id="recipe-count" class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">0 Items</span>
                                        </div>

                                        {{-- Spec Sheet Header --}}
                                        <div class="grid grid-cols-12 gap-4 px-2 mb-2 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">
                                            <div class="col-span-6">Ingredient</div>
                                            <div class="col-span-3 text-right">Quantity</div>
                                            <div class="col-span-2 text-right">Line Total</div>
                                            <div class="col-span-1"></div>
                                        </div>

                                        <div id="recipe-lines-container" class="space-y-0 divide-y divide-slate-100 border-t border-slate-200 flex-1 min-h-0">
                                            {{-- Dynamic lines injected here --}}
                                        </div>
                                        <div id="recipe-empty-state" class="border border-dashed border-slate-300 rounded-2xl p-10 text-center bg-white mt-2">
                                            <p class="text-sm text-slate-400 italic">No ingredients added. Use the search above.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- STEP 3: VISUALS --}}
                            <div id="step-3" class="studio-step hidden h-full space-y-6">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Visual Identity</h3>
                                    <p class="text-sm text-slate-500">Upload a high-resolution photo to attract customers.</p>
                                </div>

                                <div class="bg-slate-50 rounded-3xl p-6 border border-slate-100 flex flex-col items-center justify-center text-center space-y-6 h-full max-h-[300px]">
                                    <div id="menu-image-dropzone" class="relative w-full max-w-[180px] aspect-square rounded-[1.5rem] border-2 border-slate-200 hover:border-amber-500 transition-all cursor-pointer group overflow-hidden bg-white shadow-sm">
                                        <div id="menu-image-placeholder" class="absolute inset-0 flex flex-col items-center justify-center p-4 text-slate-400 group-hover:text-slate-500 transition-colors">
                                            <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300 border border-slate-100">
                                                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            </div>
                                            <p class="text-xs font-bold text-slate-600">Add Photo</p>
                                            <p class="text-[9px] mt-0.5 opacity-60">JPG, PNG up to 2MB</p>
                                        </div>

                                        <div id="menu-image-dragover-state" class="hidden absolute inset-0 bg-amber-500/10 backdrop-blur-sm flex flex-col items-center justify-center p-4 text-amber-600 animate-pulse">
                                            <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                            <p class="text-xs font-bold">Drop image here</p>
                                        </div>

                                        <div id="menu-image-preview-wrap" class="hidden absolute inset-0 group">
                                            <div id="image-crop-container" class="relative w-full h-full overflow-hidden">
                                                <img id="menu-image-preview" src="" class="absolute cursor-pointer" style="max-width: none; max-height: none;" alt="Preview">
                                                <div id="crop-overlay" class="absolute inset-0 pointer-events-none border-[15px] border-black/40 flex items-center justify-center">
                                                    <div class="w-full h-full border-2 border-white shadow-lg"></div>
                                                </div>
                                            </div>
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                                            </div>
                                        </div>

                                        <div id="menu-image-uploading" class="hidden absolute inset-0 bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center z-20">
                                            <div class="relative w-12 h-12 flex items-center justify-center">
                                                <svg class="w-12 h-12 transform -rotate-90">
                                                    <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="4" fill="transparent" class="text-slate-100" />
                                                    <circle id="upload-progress-circle" cx="24" cy="24" r="20" stroke="currentColor" stroke-width="4" fill="transparent"
                                                        stroke-dasharray="125.66" stroke-dashoffset="125.66"
                                                        class="text-amber-500 transition-all duration-100 ease-linear"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <span id="upload-progress-text" class="absolute text-[9px] font-black text-slate-700">0%</span>
                                            </div>
                                            <p class="text-[10px] font-bold text-slate-600 mt-2">Uploading...</p>
                                        </div>
                                    </div>
                                    <button type="button" id="menu-image-clear-btn" class="hidden text-xs font-bold text-red-500 hover:text-red-700 transition-colors flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        Remove Photo
                                    </button>
                                    <input type="file" id="menu-image-input" name="image" class="hidden" accept="image/*">
                                    <input type="hidden" name="remove_image" id="menu-remove-image" value="0">
                                </div>
                            </div>

                            {{-- STEP 4: REVIEW --}}
                            <div id="step-4" class="studio-step hidden h-full space-y-10">
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Final Review</h3>
                                    <p class="text-sm text-slate-500">Verify all configuration details before making the item live.</p>
                                </div>

                                <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Configuration Summary</p>
                                    </div>
                                    <div class="p-8 space-y-5">
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Item Name</span>
                                            <span id="review-name" class="text-sm font-bold text-slate-900">-</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Category</span>
                                            <span id="review-category" class="text-sm font-bold text-slate-900">-</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Sale Price</span>
                                            <span id="review-price" class="text-sm font-bold text-amber-600">₱0.00</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                            <span class="text-sm font-medium text-slate-500">Estimated Cost</span>
                                            <span id="review-cost" class="text-sm font-bold text-slate-900">₱0.00</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2">
                                            <span class="text-sm font-medium text-slate-500">Profit Margin</span>
                                            <span id="review-margin" class="text-sm font-bold text-slate-900">0%</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-center text-xs text-slate-400 italic px-6">Double check all values. Item availability depends on the current stock status and quantity of its required ingredients.</p>
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
                            <button type="submit" id="menu-modal-save" form="menu-form" class="hidden px-8 py-3 bg-slate-900 hover:bg-black text-white text-sm font-bold rounded-xl shadow-lg shadow-slate-900/20 transition-all active:scale-95 flex items-center justify-center gap-2">
                                {{ isset($editingItem) ? 'Update Item' : 'Save Item' }}
                                <span id="save-spinner" class="hidden w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: High-Fidelity Component Preview --}}
            <div class="w-full lg:w-[480px] flex flex-col items-center justify-center bg-slate-200/60 p-12 rounded-[3rem] border border-slate-200 relative">
                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center mb-12">Customer View Preview</h4>

                {{-- The Actual Component Card --}}
                <div class="w-full max-w-[340px] bg-white rounded-xl shadow-[0_30px_60px_-12px_rgba(0,0,0,0.12)] border border-white transition-all duration-500 group hover:shadow-amber-500/10 relative">
                    <div class="relative h-72 bg-slate-100 overflow-hidden rounded-t-xl">
                        <img id="preview-card-image" src="https://placehold.co/300x200?text=No+Image" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="">

                        {{-- Category Badge: Floating Glassmorphism --}}
                        <div id="preview-category-badge" class="absolute top-4 left-4 px-3 py-1 rounded-full bg-white/70 backdrop-blur-md text-[9px] font-black text-slate-800 uppercase tracking-wider shadow-sm border border-white/50">
                            Category
                        </div>

                        {{-- Subtle Gradient Overlay for Text Contrast --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent pointer-events-none"></div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-start gap-3">
                            <div class="space-y-1 overflow-hidden">
                                <h3 id="preview-card-name" class="text-2xl font-black text-slate-900 truncate leading-tight">Item Name</h3>
                                <div class="flex items-center gap-1.5">
                                    <div class="flex items-center gap-0.5">
                                        <svg class="w-3 h-3 text-amber-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.902 8.72c-.791-.57-1.175-1.81-.588-2.58l2.8-2.034a1 1 0 00.364-1.118L4.5 2.927z"/></svg>
                                        <span id="preview-margin-badge" class="text-xs font-bold text-slate-600">0.0</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-medium">• Popular Choice</span>
                                </div>
                            </div>
                            <span id="preview-card-price" class="text-2xl font-black text-amber-600 whitespace-nowrap">₱0.00</span>
                        </div>

                        <p id="preview-card-desc" class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                            No description provided. Describe the flavors and experience of this dish to entice customers.
                        </p>

                        </div>
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

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

        .premium-ingredient-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .premium-ingredient-item:last-child { border-bottom: none; }
        .premium-ingredient-item:hover {
            padding-left: 0.25rem;
            background: rgba(249, 115, 22, 0.03);
        }
    </style>

    <script>
        (function () {
            const form = document.getElementById('menu-form');
            const storeUrl = '{{ route('admin.menu.store') }}';
            const updateUrl = '{{ isset($editingItem) ? route('admin.menu.update', $editingItem->menu_item_id) : '' }}';
            const saveDraftUrl = '{{ route('admin.menu.save-draft') }}';
            const isEditMode = {{ isset($editingItem) ? 'true' : 'false' }};
            const updateMethod = '{{ isset($editingItem) ? 'PUT' : 'POST' }}';

            let currentStep = 1;
            const totalSteps = 4;
            let saveTimeout; // For debouncing auto-saves


            const studioState = {
                totalCost: 0,
                price: 0,
                imagePreview: null,
                recipe: []
            };

            const steps = document.querySelectorAll('.studio-step');
            const nextBtn = document.getElementById('studio-next-btn');
            const prevBtn = document.getElementById('studio-prev-btn');
            const saveBtn = document.getElementById('menu-modal-save');
            const progressBar = document.getElementById('studio-progress-bar');
            const indicators = document.querySelectorAll('.step-indicator');

            const prevName = document.getElementById('preview-card-name');
            const prevCat = document.getElementById('preview-category-badge');
            const prevPrice = document.getElementById('preview-card-price');
            const prevMargin = document.getElementById('preview-margin-badge');
            const prevImg = document.getElementById('preview-card-image');
            const prevDesc = document.getElementById('preview-card-desc');

            const revName = document.getElementById('review-name');
            const revCat = document.getElementById('review-category');
            const revPrice = document.getElementById('review-price');
            const revCost = document.getElementById('review-cost');
            const revMargin = document.getElementById('review-margin');

            const PANTRY_STAPLES = ['Salt', 'Pepper', 'Oil', 'Water', 'Sugar'];
            const ALL_INGREDIENTS = @json($ingredients);

            async function saveDraftToServer() {
                const formData = new FormData(form);
                const data = {
                    currentStep,
                    inputs: {},
                    recipe: studioState.recipe,
                    imagePreview: document.getElementById('menu-image-preview')?.getAttribute('src') || null
                };

                formData.forEach((value, key) => {
                    if (!key.includes('recipe')) {
                        data.inputs[key] = value;
                    }
                });

                const menuItemId = document.getElementById('menu-item-id')?.value;
                if (menuItemId) {
                    data.menu_item_id = menuItemId;
                }

                try {
                    const response = await fetch(saveDraftUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(data)
                    });

                    if (!response.ok) {
                        console.error('Server responded with error status:', response.status);
                        return;
                    }

                    const result = await response.json();
                    console.log('DUPLICATE CHECK RESPONSE:', result);

                    const hasNameError = result.errors && result.errors.item_name && result.errors.item_name.length > 0;

                    if (hasNameError) {
                        const errorEl = document.getElementById('item-name-error');
                        const inputEl = document.getElementById('studio-item-name');
                        if (errorEl) {
                            errorEl.textContent = result.errors.item_name[0];
                            errorEl.style.display = 'block';
                            errorEl.classList.remove('hidden');
                        }
                        if (inputEl) {
                            inputEl.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/10');
                            inputEl.classList.remove('border-slate-200', 'focus:border-amber-500', 'focus:ring-amber-500/10');
                        }
                    } else {
                        const errorEl = document.getElementById('item-name-error');
                        const inputEl = document.getElementById('studio-item-name');
                        if (errorEl) {
                            errorEl.style.display = 'none';
                            errorEl.classList.add('hidden');
                        }
                        if (inputEl) {
                            inputEl.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/10');
                            inputEl.classList.add('border-slate-200', 'focus:border-amber-500', 'focus:ring-amber-500/10');
                        }
                    }
                    validateCurrentStep();
                } catch (e) {
                    console.error('Draft auto-save network failure:', e);
                }
            }

            function loadDraftFromServer() {
                const draft = @json($draft);
                if (!draft) return;

                try {
                    currentStep = draft.currentStep || 1;
                    const inputs = draft.inputs || draft;
                    if (inputs) {
                        Object.entries(inputs).forEach(([key, value]) => {
                            const input = form.elements[key];
                            if (input) {
                                input.value = value;
                                input.dispatchEvent(new Event('input'));
                            }
                        });
                    }

                    if (!draft.currentStep && !isEditMode) {
                        currentStep = 1;
                    } else if (draft.currentStep) {
                        currentStep = draft.currentStep;
                    } else {
                        currentStep = 1;
                    }

                    const recipe = draft.recipe;
                    if (recipe && Array.isArray(recipe)) {
                        studioState.recipe = recipe;
                        recipeLinesContainer.innerHTML = '';

                        recipe.forEach(item => {
                            addRecipeLine(item.id, item.name, item.unit, item.cost, item.qty, true);
                        });

                        document.getElementById('recipe-count').textContent = `${studioState.recipe.length} Items`;
                        if (studioState.recipe.length > 0) {
                            document.getElementById('recipe-empty-state').classList.add('hidden');
                        }
                    } else {
                        // Reset recipe state if no recipe in draft
                        studioState.recipe = [];
                        recipeLinesContainer.innerHTML = '';
                        document.getElementById('recipe-count').textContent = '0 Items';
                        document.getElementById('recipe-empty-state').classList.remove('hidden');
                    }

                    const imagePreview = draft.imagePreview;
                    if (imagePreview) {
                        const removeImageValue = (draft.inputs ? draft.inputs.remove_image : draft.remove_image) || '0';
                        if (removeImageValue === '1') {
                            prevImg.src = 'https://placehold.co/300x200?text=No+Image';
                            const mainPreview = document.getElementById('menu-image-preview');
                            if (mainPreview) mainPreview.src = 'https://placehold.co/300x200?text=No+Image';
                            setDropzoneState('idle');
                        } else {
                            const img = document.getElementById('menu-image-preview');
                            if (img) {
                                img.src = imagePreview;
                                const container = document.getElementById('image-crop-container');
                                if (container) {
                                    img.onload = () => {
                                        const ratio = Math.max(container.offsetWidth / img.naturalWidth, container.offsetHeight / img.naturalHeight);
                                        img.style.width = (img.naturalWidth * ratio) + 'px';
                                        img.style.height = (img.naturalHeight * ratio) + 'px';
                                    };
                                    if (img.complete) img.onload();
                                }
                            }
                            prevImg.src = imagePreview;
                            setDropzoneState('preview');
                        }
                    }

                    updateStepper();
                    updateLivePreview();
                    calculateTotalCost();
                    validateCurrentStep();
                } catch (e) {
                    console.error('Error restoring draft/item data:', e);
                }
            }

            function updateStepper() {
                steps.forEach((s, i) => {
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
                        circle.classList.remove('bg-amber-500', 'text-white', 'border-amber-500', 'border-amber-500', 'ring-2', 'ring-amber-500/20');
                        circle.innerHTML = (i + 1);
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

                // Block progression if there is a duplicate name error visible
                const nameError = document.getElementById('item-name-error');
                if (nameError && (nameError.style.display === 'block' || !nameError.classList.contains('hidden'))) {
                    allFilled = false;
                }

                if (currentStep === 2) {
                    const recipeLines = document.querySelectorAll('#recipe-lines-container > div');
                    if (recipeLines.length === 0) {
                        allFilled = false;
                    }
                }

                    if (currentStep === 3) {
                        const imagePreview = document.getElementById('menu-image-preview');
                        const imageSrc = imagePreview ? imagePreview.getAttribute('src') : '';
                        const removeImageValue = document.getElementById('menu-remove-image')?.value;

                        // In Edit mode, we only block if they explicitly removed the image AND haven't added a new one
                        // Otherwise, if there's an existing image (not placeholder), it's valid.
                        const hasValidImage = imageSrc && !imageSrc.includes('placehold.co') && removeImageValue !== '1';

                        if (!hasValidImage) {
                            allFilled = false;
                        }
                    }

                nextBtn.disabled = !allFilled;
            }

            function updateLivePreview() {
                const name = form.elements['item_name']?.value || '';
                const cat = form.elements['category']?.value || '';
                const price = parseFloat(form.elements['price']?.value) || 0;
                const desc = form.elements['description']?.value || '';

                prevName.textContent = name || 'Item Name';
                prevCat.textContent = cat || 'Category';
                prevPrice.textContent = `₱${price.toFixed(2)}`;
                prevDesc.textContent = desc || 'No description provided. Describe the flavors and experience of this dish to entice customers.';

                studioState.price = price;

                const prevPriceVal = document.getElementById('preview-card-price-val');
                if (prevPriceVal) prevPriceVal.textContent = `₱${price.toFixed(2)}`;

                const grossProfit = price - studioState.totalCost;
                const margin = price > 0 ? (grossProfit / price) * 100 : 0;
                prevMargin.textContent = `${margin.toFixed(1)}%`;

                validateCurrentStep();

                // Debounced auto-save to prevent server hammering and race conditions
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(saveDraftToServer, 500);

                updateSmartRecommendations(name);
                updatePreviewIngredients();
            }

            function updateReviewSummary() {
                const name = form.elements['item_name']?.value || '';
                const cat = form.elements['category']?.value || '';
                const price = parseFloat(form.elements['price']?.value) || 0;

                revName.textContent = name || '-';
                revCat.textContent = cat || '-';
                revPrice.textContent = `₱${price.toFixed(2)}`;
                revCost.textContent = `₱${studioState.totalCost.toFixed(2)}`;

                const grossProfit = price - studioState.totalCost;
                const margin = price > 0 ? (grossProfit / price) * 100 : 0;
                revMargin.textContent = `${Math.round(margin)}%`;
            }

            function calculateTotalCost() {
                let total = 0;
                const lines = document.querySelectorAll('#recipe-lines-container > div');
                lines.forEach(line => {
                    const qty = parseFloat(line.querySelector('input[type="number"]')?.value) || 0;
                    const cost = parseFloat(line.dataset.unitCost || 0);
                    total += (qty * cost);
                });
                studioState.totalCost = total;
                updateLivePreview();
                updateReviewSummary();
            }

            const recipeLinesContainer = document.getElementById('recipe-lines-container');

            function addRecipeLine(ingredientId, ingredientLabel, unit, cost = 0, qty = 1.00, skipStateUpdate = false) {
                const existing = document.querySelector(`input[value="${ingredientId}"][name*="ingredient_id"]`);
                if (existing) {
                    alert('Ingredient already added to recipe.');
                    return;
                }

                const rowId = `recipe-row-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
                const row = document.createElement('div');
                row.className = 'grid grid-cols-12 gap-4 py-3 px-2 items-center group hover:bg-slate-50/50 transition-colors';
                row.id = rowId;
                row.dataset.unitCost = cost;
                row.innerHTML = `
                    <div class="col-span-6 flex items-center gap-3 min-w-0">
                        <div class="w-1 h-1 rounded-full bg-slate-300 group-hover:bg-amber-500 transition-colors flex-shrink-0"></div>
                        <span class="text-sm font-bold text-slate-900 truncate block">${ingredientLabel}</span>
                        <input type="hidden" name="recipe[${rowId}][ingredient_id]" value="${ingredientId}">
                    </div>
                    <div class="col-span-3 flex items-center justify-end gap-2">
                        <input type="number" step="0.01" name="recipe[${rowId}][qty_consumed]"
                               value="${qty}" class="w-16 text-right font-mono text-sm outline-none bg-transparent border-b border-transparent group-hover:border-slate-200 focus:border-amber-500 transition-colors"
                               placeholder="0.00">
                        <span class="recipe-unit text-[10px] font-bold text-slate-400 uppercase w-8 text-left">${unit}</span>
                    </div>
                    <div class="col-span-2 text-right font-mono text-sm font-medium text-slate-600">
                        ₱<span class="line-total">${(qty * cost).toFixed(2)}</span>
                    </div>
                    <div class="col-span-1 text-right">
                        <button type="button" class="remove-recipe-line-btn p-1 text-slate-300 hover:text-red-500 transition-colors text-lg leading-none">
                            &times;
                        </button>
                    </div>
                `;

                row.querySelector('.remove-recipe-line-btn').addEventListener('click', () => {
                    row.remove();
                    calculateTotalCost();
                    if (recipeLinesContainer.children.length === 0) {
                        document.getElementById('recipe-empty-state').classList.remove('hidden');
                        document.getElementById('recipe-count').textContent = '0 Items';
                    } else {
                        document.getElementById('recipe-count').textContent = `${recipeLinesContainer.children.length} Items`;
                    }
                    updateRecipeState();
                    renderDropdown(searchInput.value);
                });

                row.querySelector('input[type="number"]').addEventListener('input', (e) => {
                    const val = parseFloat(e.target.value) || 0;
                    const lineTotal = val * cost;
                    row.querySelector('.line-total').textContent = lineTotal.toFixed(2);
                    calculateTotalCost();
                    updateRecipeState();
                });
                recipeLinesContainer.appendChild(row);
                document.getElementById('recipe-empty-state').classList.add('hidden');
                document.getElementById('recipe-count').textContent = `${recipeLinesContainer.children.length} Items`;
                calculateTotalCost();
                if (!skipStateUpdate) {
                    updateRecipeState();
                }
            }

            function updateRecipeState() {
                const lines = document.querySelectorAll('#recipe-lines-container > div');
                studioState.recipe = Array.from(lines).map(line => {
                    const idInput = line.querySelector('input[type="hidden"]');
                    const nameSpan = line.querySelector('span');

                    return {
                        id: idInput ? idInput.value : '',
                        name: nameSpan ? nameSpan.textContent : '',
                        unit: line.querySelector('.recipe-unit')?.textContent || '',
                        cost: parseFloat(line.dataset.unitCost) || 0,
                        qty: parseFloat(line.querySelector('input[type="number"]')?.value) || 0
                    };
                });
                saveDraftToServer();
            }

            function updatePreviewIngredients() {
                const content = document.getElementById('preview-ingredients-content');
                if (!content) return;

                if (studioState.recipe.length === 0) {
                    content.innerHTML = `
                        <div class="flex flex-col items-center justify-center py-4 text-center">
                            <svg class="w-6 h-6 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 000 4h2a2 2 0 000-4M9 5a2 2 0 010 4h2a2 2 0 010-4" />
                            </svg>
                            <p class="italic text-slate-400 text-[10px]">No ingredients defined yet.</p>
                        </div>`;
                    return;
                }

                content.innerHTML = studioState.recipe.map(item => `
                    <div class="premium-ingredient-item group">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-1 rounded-full bg-amber-400 opacity-0 group-hover:opacity-100 transition-all"></div>
                            <span class="font-semibold text-slate-700 tracking-tight">${item.name}</span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded uppercase tracking-tighter">
                            ${item.qty} ${item.unit}
                        </span>
                    </div>
                `).join('');
            }

            function updateSmartRecommendations(itemName) {
                const container = document.getElementById('smart-chips-container');
                const status = document.getElementById('rec-status');
                const emptyText = document.getElementById('rec-empty-text');

                if (!itemName || itemName.trim().length < 3) {
                    if (container) container.innerHTML = '';
                    if (emptyText) emptyText.classList.remove('hidden');
                    if (status) status.classList.add('hidden');
                    return;
                }

                if (status) status.classList.remove('hidden');

                const keywords = itemName.toLowerCase().split(' ');
                const matched = ALL_INGREDIENTS.filter(ing =>
                    keywords.some(kw => ing.ingredient_name.toLowerCase().includes(kw))
                );

                const staples = ALL_INGREDIENTS.filter(ing =>
                    PANTRY_STAPLES.some(s => ing.ingredient_name.toLowerCase().includes(s.toLowerCase()))
                );

                const recommendations = [...matched, ...staples];

                const uniqueRecs = [];
                const seenIds = new Set();
                recommendations.forEach(r => {
                    if (!seenIds.has(r.ingredient_id)) {
                        uniqueRecs.push(r);
                        seenIds.add(r.ingredient_id);
                    }
                });

                if (!container) return;

                if (uniqueRecs.length === 0) {
                    container.innerHTML = '';
                    if (emptyText) emptyText.classList.remove('hidden');
                } else {
                    if (emptyText) emptyText.classList.add('hidden');
                    container.innerHTML = uniqueRecs.map(ing => `
                        <button type="button"
                                onclick="window.studioAddIngredient('${ing.ingredient_id}', '${ing.ingredient_name.replace(/'/g, "\\'")}', '${ing.unit_of_measurement}', ${ing.unit_cost || 0})"
                                class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-[11px] font-bold text-slate-600 hover:border-amber-500 hover:text-amber-600 transition-all shadow-sm flex items-center gap-1.5 active:scale-95 group">
                            <svg class="w-3 h-3 text-slate-400 group-hover:text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            ${ing.ingredient_name}
                        </button>
                    `).join('');
                }

                if (status) setTimeout(() => status.classList.add('hidden'), 1000);
            }

            window.studioAddIngredient = function(id, name, unit, cost) {
                addRecipeLine(id, name, unit, cost);
            };

            const searchInput = document.getElementById('ing-search-input');
            const dropdownList = document.getElementById('ing-dropdown-list');
            const noResults = document.getElementById('ing-no-results');

            searchInput.addEventListener('focus', () => {
                dropdownList.classList.remove('hidden');
                renderDropdown();
            });

            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !dropdownList.contains(e.target)) {
                    dropdownList.classList.add('hidden');
                }
            });

            function renderDropdown(term = '') {
                const lowTerm = term.toLowerCase();
                const matched = ALL_INGREDIENTS.filter(ing => ing.ingredient_name.toLowerCase().includes(lowTerm));
                const unmatched = ALL_INGREDIENTS.filter(ing => !ing.ingredient_name.toLowerCase().includes(lowTerm));

                let html = '';

                const item_name = form.elements['item_name']?.value || '';
                const keywords = item_name.toLowerCase().split(' ');
                const recommended = matched.filter(ing =>
                    keywords.some(kw => kw.length > 2 && ing.ingredient_name.toLowerCase().includes(kw))
                );

                if (recommended.length > 0) {
                    html += `<div class="px-4 py-2 flex items-center gap-2 text-[10px] font-black text-amber-600 uppercase tracking-widest bg-amber-50/50">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.902 8.72c-.791-.57-1.175-1.81-.588-2.58l2.8-2.034a1 1 0 00.364-1.118L4.5 2.927z"/></svg>
                                Recommended
                            </div>`;
                    recommended.forEach(ing => {
                        const isAdded = studioState.recipe.some(r => r.id == ing.ingredient_id);
                        html += `
                            <div class="ing-option px-4 py-3 text-sm text-slate-700 hover:bg-amber-50 hover:text-amber-700 cursor-pointer transition-all flex items-center justify-between group border-b border-slate-50 last:border-none ${isAdded ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}"
                                 data-id="${ing.ingredient_id}"
                                 data-name="${ing.ingredient_name}"
                                 data-unit="${ing.unit_of_measurement}"
                                 data-cost="${ing.unit_cost ?? 0}">
                                <div class="flex items-center gap-3 truncate">
                                    <div class="relative flex items-center justify-center">
                                        <input type="checkbox" ${isAdded ? 'checked' : ''} class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500 pointer-events-none" disabled>
                                        ${isAdded ? `
                                            <svg class="absolute w-3 h-3 text-white pointer-events-none" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        ` : ''}
                                    </div>
                                    <div class="flex flex-col truncate">
                                        <span class="font-semibold truncate ${isAdded ? 'text-slate-400' : 'text-slate-700'}">${ing.ingredient_name}</span>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">${ing.unit_of_measurement}</span>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 text-amber-500 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            </div>`;
                    });
                }

                const others = matched.length > 0 ? matched.filter(ing => !recommended.includes(ing)) : matched;
                if (others.length > 0 || (term && matched.length === 0)) {
                    if (others.length > 0) {
                        html += `<div class="px-4 py-2 text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50">All Ingredients</div>`;
                        others.forEach(ing => {
                            const isAdded = studioState.recipe.some(r => r.id == ing.ingredient_id);
                            html += `
                                <div class="ing-option px-4 py-3 text-sm text-slate-700 hover:bg-amber-50 hover:text-amber-700 cursor-pointer transition-all flex items-center justify-between group border-b border-slate-50 last:border-none ${isAdded ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}"
                                     data-id="${ing.ingredient_id}"
                                     data-name="${ing.ingredient_name}"
                                     data-unit="${ing.unit_of_measurement}"
                                     data-cost="${ing.unit_cost ?? 0}">
                                <div class="flex items-center gap-3 truncate">
                                    <div class="relative flex items-center justify-center">
                                        <input type="checkbox" ${isAdded ? 'checked' : ''} class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500 pointer-events-none" disabled>
                                        ${isAdded ? `
                                            <svg class="absolute w-3 h-3 text-white pointer-events-none" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        ` : ''}
                                    </div>
                                    <div class="flex flex-col truncate">
                                        <span class="font-semibold truncate ${isAdded ? 'text-slate-400' : 'text-slate-700'}">${ing.ingredient_name}</span>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">${ing.unit_of_measurement}</span>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 text-amber-500 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            </div>`;
                        });
                    }
                }

                if (html === '') {
                    html = `<div class="px-4 py-3 text-xs text-slate-400 text-center italic">No ingredients found</div>`;
                }

                dropdownList.innerHTML = html;
            }

            searchInput.addEventListener('input', (e) => {
                renderDropdown(e.target.value);
                dropdownList.classList.remove('hidden');
            });

            dropdownList.addEventListener('click', (e) => {
                e.stopPropagation();
                const option = e.target.closest('.ing-option');
                if (!option) return;

                const { id, name, unit, cost } = option.dataset;
                addRecipeLine(id, name, unit, cost);

                renderDropdown(searchInput.value);
            });

            const dropzone = document.getElementById('menu-image-dropzone');
            const fileInput = document.getElementById('menu-image-input');
            const removeImageField = document.getElementById('menu-remove-image');
            const clearBtn = document.getElementById('menu-image-clear-btn');

            function setDropzoneState(state) {
                document.getElementById('menu-image-placeholder').classList.toggle('hidden', state !== 'idle');
                document.getElementById('menu-image-dragover-state').classList.toggle('hidden', state !== 'dragover');
                document.getElementById('menu-image-preview-wrap').classList.toggle('hidden', state !== 'preview');
                document.getElementById('menu-image-uploading').classList.toggle('hidden', state !== 'uploading');

                if (clearBtn) {
                    clearBtn.classList.toggle('hidden', state !== 'preview');
                }
            }

            function showImagePreview(file) {
                removeImageField.value = '0';
                setDropzoneState('uploading');
                let progress = 0;
                const progressCircle = document.getElementById('upload-progress-circle');
                const progressText = document.getElementById('upload-progress-text');

                const interval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress > 95) progress = 95;
                    const offset = 175.93 - (175.93 * (progress / 100));
                    progressCircle.style.strokeDashoffset = offset;
                    progressText.textContent = Math.round(progress) + '%';
                }, 150);

                const reader = new FileReader();
                reader.onload = (e) => {
                    clearInterval(interval);
                    progressCircle.style.strokeDashoffset = 0;
                    progressText.textContent = '100%';

                    setTimeout(() => {
                        const url = e.target.result;
                        const previewImg = document.getElementById('menu-image-preview');
                        previewImg.src = url;
                        previewImg.style.left = '0px';
                        previewImg.style.top = '0px';
                        previewImg.style.width = 'auto';
                        previewImg.style.height = 'auto';

                        previewImg.onload = () => {
                            const container = document.getElementById('image-crop-container');
                            const ratio = Math.max(container.offsetWidth / previewImg.naturalWidth, container.offsetHeight / previewImg.naturalHeight);
                            previewImg.style.width = (previewImg.naturalWidth * ratio) + 'px';
                            previewImg.style.height = (previewImg.naturalHeight * ratio) + 'px';
                        };

                        prevImg.src = url;
                        setDropzoneState('preview');
                        validateCurrentStep();
                        saveDraftToServer();
                    }, 400);
                };
                reader.readAsDataURL(file);
            }

            const modal = document.getElementById('image-adjustment-modal');
            const modalImg = document.getElementById('modal-image-preview');
            const modalCropContainer = document.getElementById('modal-crop-container');
            const modalSaveBtn = document.getElementById('modal-save-btn');
            const modalKeepEditingBtn = document.getElementById('modal-cancel-btn');
            const modalCloseBtn = document.getElementById('modal-close-btn');
            const modalChangeBtn = document.getElementById('modal-change-photo-btn');

            if (modal) {
                const closeModal = () => modal.classList.add('hidden');
                if (modalKeepEditingBtn) modalKeepEditingBtn.addEventListener('click', closeModal);
                if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
                if (modalChangeBtn) {
                    modalChangeBtn.addEventListener('click', () => {
                        closeModal();
                        if (fileInput) fileInput.click();
                    });
                }
                if (modalSaveBtn) {
                    modalSaveBtn.addEventListener('click', () => {
                        const mainImg = document.getElementById('menu-image-preview');
                        if (mainImg && modalImg) {
                            mainImg.style.left = modalImg.style.left;
                            mainImg.style.top = modalImg.style.top;
                            mainImg.style.width = modalImg.style.width;
                            mainImg.style.height = modalImg.style.height;
                        }
                        modal.classList.add('hidden');
                        saveDraftToServer();
                    });
                }
            }

            const previewImg = document.getElementById('menu-image-preview');
            if (previewImg) {
                previewImg.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openAdjustmentModal();
                });
            }

            dropzone.addEventListener('click', (e) => { if (e.target.id !== 'menu-image-clear-btn') fileInput.click(); });
            fileInput.addEventListener('change', () => {
                const file = fileInput.files[0];
                if (file) showImagePreview(file);
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    fileInput.value = '';
                    removeImageField.value = '1';
                    setDropzoneState('idle');
                    prevImg.src = 'https://placehold.co/300x200?text=No+Image';
                    const mainPreview = document.getElementById('menu-image-preview');
                    if (mainPreview) mainPreview.src = 'https://placehold.co/300x200?text=No+Image';
                    validateCurrentStep();
                    saveDraftToServer();
                });
            }

            async function submitForm(e) {
                e.preventDefault();
                const saveBtn = document.getElementById('menu-modal-save');
                const spinner = document.getElementById('save-spinner');
                saveBtn.disabled = true;
                if (spinner) spinner.classList.remove('hidden');
                saveBtn.classList.add('opacity-80', 'cursor-not-allowed');

                // Log payload for debugging 422 errors
                const formData = new FormData(form);
                console.log('Submitting Menu Item Payload:');
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}: ${value}`);
                }

                // Cleaning payload: transform alphanumeric recipe keys to indexed array
                const cleanedFormData = new FormData();
                const recipeLines = document.querySelectorAll('#recipe-lines-container > div');
                const recipeData = [];

                recipeLines.forEach(line => {
                    const id = line.querySelector('input[name*="ingredient_id"]')?.value;
                    const qty = line.querySelector('input[name*="qty_consumed"]')?.value;
                    if (id) {
                        recipeData.push({ ingredient_id: id, qty_consumed: qty });
                    }
                });

                // Append standard fields
                for (let [key, value] of formData.entries()) {
                    if (!key.includes('recipe')) {
                        cleanedFormData.append(key, value);
                    }
                }

                // Append cleaned recipe
                recipeData.forEach((item, index) => {
                    cleanedFormData.append(`recipe[${index}][ingredient_id]`, item.ingredient_id);
                    cleanedFormData.append(`recipe[${index}][qty_consumed]`, item.qty_consumed);
                });

                // Ensure an empty recipe doesn't send as a string "null" or similar
                if (recipeData.length === 0) {
                    cleanedFormData.set('recipe', '');
                }

                await submitAjaxForm({
                    form: cleanedFormData,
                    url: isEditMode ? updateUrl : storeUrl,
                    method: isEditMode ? updateMethod : 'POST',
                    toastKind: null,
                    onSuccess: async (data) => {
                        await fetch(saveDraftUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ clear: true })
                        });
                        window.location.href = '{{ route('admin.menu.index') }}';
                    },
                    onError: (errors) => {
                        console.error('Validation Errors:', errors);
                        saveBtn.disabled = false;
                        saveBtn.textContent = isEditMode ? 'Update Item' : 'Save Item';
                    }
                });
            }

            document.getElementById('studio-discard-btn').addEventListener('click', () => {
                document.getElementById('confirm-modal').classList.remove('hidden');
            });

            document.getElementById('confirm-cancel').addEventListener('click', () => {
                document.getElementById('confirm-modal').classList.add('hidden');
            });

            document.getElementById('confirm-discard').addEventListener('click', async () => {
                await fetch(saveDraftUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ clear: true })
                });
                window.location.href = '{{ route('admin.menu.index') }}';
            });
            // Logic to prevent automatic discard popup when navigating back to Menu Management
            document.querySelector('nav a[href="{{ route('admin.menu.index') }}"]').addEventListener('click', async (e) => {
                // Only show discard popup if the form has actually been modified
                const isModified = form.getAttribute('data-modified') === 'true';
                if (!isModified) return;

                e.preventDefault();
                document.getElementById('confirm-modal').classList.remove('hidden');
            });

            // Track if form is modified
            form.addEventListener('input', () => {
                form.setAttribute('data-modified', 'true');
            });

            // Reset modified state when saving
            form.addEventListener('submit', () => {
                form.setAttribute('data-modified', 'false');
            });


            nextBtn.addEventListener('click', () => {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateStepper();
                    saveDraftToServer();
                }
            });

            prevBtn.addEventListener('click', () => {
                if (currentStep > 1) {
                    currentStep--;
                    updateStepper();
                    saveDraftToServer();
                }
            });

            form.addEventListener('input', updateLivePreview);
            form.addEventListener('submit', submitForm);

            loadDraftFromServer();
            updateStepper();
            updateLivePreview();
        })();
    </script>
@endsection
