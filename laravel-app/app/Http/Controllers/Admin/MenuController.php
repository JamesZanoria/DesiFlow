<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ClampsPaginationPage;
use App\Http\Controllers\Admin\Concerns\HandlesProtectedImageUploads;
use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Services\InputValidationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class MenuController extends Controller{
    use ClampsPaginationPage;
    use HandlesProtectedImageUploads;

    private const BLOCKED_PLACEHOLDER_VALUES = [
        'test', 'testing', 'tests', 'sample', 'samples', 'sample data',
        'asdf', 'asdfgh', 'qwerty', 'foo', 'bar', 'foobar', 'xxx', 'xyz',
        'n/a', 'na', 'none', 'null', 'undefined', 'todo', 'tbd', 'temp',
        'temporary', 'example', 'demo', 'dummy', 'placeholder', 'lorem ipsum',
        'asd', 'abc', '123', 'zzz',
    ];

    // Display a paginated, filterable listing of all menu items.
    public function index(Request $request){
        $menuItems = $this->paginateClamped(function () use ($request) {
            $query = MenuItem::query();

            if ($request->filled('search')) {
                $query->where('item_name', 'ILIKE', '%' . $request->input('search') . '%');
            }

            if ($request->filled('category')) {
                $query->where('category', $request->input('category'));
            }

            return $query->orderByDesc('menu_item_id');
        }, $request, 8);

        // Distinct list of categories currently in use, to populate the filter dropdown
        // and the Add/Edit modal's category suggestions.
        $categories = MenuItem::query()->select('category')->distinct()->orderBy('category')->pluck('category');

        // Full, unpaginated list of item names for the search box's suggestions
        // dropdown, deliberately separate from $menuItems (which is only the
        // current page of up to 8 results) since suggestions need to cover
        // every item, not just whichever page happens to be showing.
        $allItemNames = MenuItem::query()->orderBy('item_name')->pluck('item_name');

        // AJAX refresh: after the modal creates/updates/deletes an item, or when the
        // admin clicks a Prev/Next pagination link, the frontend re-requests this same
        // endpoint asking for JSON instead of a full page. This returns just the
        // table + pagination fragment (see admin.menu._table) so the page can be kept
        // in sync with the real, server-computed pagination state without a manual
        // browser reload.

        // categoryOptionsHtml and itemSuggestionsHtml ride along in the same response
        // for the same reason: without them, a category or item name introduced (or
        // removed) by the change that just happened wouldn't show up in the Category
        // filter or the search suggestions until a real page reload, since both were
        // previously only ever rendered/fetched once, on initial page load.
        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('admin.menu._table', [
                    'menuItems' => $menuItems,
                ])->render(),
                'categoryOptionsHtml' => view('admin.partials._filter_select_popover', [
                    'name' => 'category',
                    'label' => 'Category',
                    'value' => $request->input('category'),
                    'options' => $categories->mapWithKeys(fn ($c) => [$c => $c]),
                ])->render(),
                'itemSuggestionsHtml' => view('admin.partials._search_suggestions', [
                    'suggestions' => $allItemNames,
                ])->render(),
            ]);
        }

        return view('admin.menu.index', [
            'menuItems' => $menuItems,
            'categories' => $categories,
            'allItemNames' => $allItemNames,
        ]);
    }

    // Kept for direct-link / no-JS access
    // Add Menu Item now opens as a popup on the index page instead.
    public function create(){
        return view('admin.menu.create');
    }

    // Description is no longer used in the current system requirements.
    private function descriptionRule(): array{
        return [];
    }

    // Rejects placeholder/test-style values in a free-text field: exact
    // matches against a known junk list, a single character repeated
    // ("aaaa"), or a value with no letters at all.
    private function notPlaceholderRule(): \Closure{
        return function (string $attribute, mixed $value, \Closure $fail) {
            $normalized = strtolower(trim((string) $value));
            if ($normalized === '') return;

            if (in_array($normalized, self::BLOCKED_PLACEHOLDER_VALUES, true)) {
                $fail('Invalid input');
                return;
            }
            if (preg_match('/^(.)\1*$/', $normalized)) {
                $fail('Please enter a valid value.');
                return;
            }
            if (!preg_match('/[a-zA-Z]/', $normalized)) {
                $fail('Value must contain at least one letter.');
                return;
            }
        };
    }

    // Human-readable messages for validation rules that need a friendlier
    // message than Laravel's generic default.
    private function validationMessages(): array{
        return [
            'image.mimes' => 'Please upload a JPG, JPEG, or PNG image.',
        ];
    }

    // Rejects a menu item name that already exists (case-insensitive, whitespace-
    // trimmed), so the same dish can't accidentally be added twice under slightly
    // different casing/spacing. $ignoreId excludes the record currently being
    // edited from the check.
    private function duplicateNameRule(?int $ignoreId = null): \Closure{
        return function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId) {
            $exists = MenuItem::whereRaw('LOWER(TRIM(item_name)) = ?', [strtolower(trim($value))])
                ->when($ignoreId, fn ($query) => $query->where('menu_item_id', '!=', $ignoreId))
                ->exists();

            if ($exists) {
                $fail('This item already exists in the menu. Please check the name or edit the existing item.');
            }
        };
    }

    // Postgres unique_violation is SQLSTATE 23505. This is the last line of
    // defense: the application-level duplicateNameRule() above should already
    // catch a duplicate before this is ever reached, but this exists so that a
    // duplicate can NEVER actually be written to the table, not if the two
    // checks ever fall out of sync, not under a race between two simultaneous
    // submissions, and not if this validation is ever bypassed some other way.
    private function isDuplicateNameViolation(QueryException $e): bool{
        return $e->getCode() === '23505'
            && str_contains($e->getMessage(), 'menu_items_item_name_unique');
    }

    // Same JSON shape (and same message) as duplicateNameRule()'s $fail() above,
    // so the frontend's onValidationError handler treats this identically to a
    // normal validation failure, still a 422, still lands in the modal with the
    // icon + red-outlined field, never a success toast, never closes the modal.
    private function duplicateNameResponse(Request $request){
        $message = 'This item already exists in the menu. Please check the name or edit the existing item.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => ['item_name' => [$message]],
            ], 422);
        }

        return redirect()->back()->withInput()->withErrors(['item_name' => $message]);
    }

    // Formats a failed validation attempt ourselves rather than letting it fall
    // through to Laravel's automatic ValidationException handling. That
    // automatic path decides redirect-vs-422 using $request->expectsJson(), which,
    // in this app, was returning false even when the request's Accept header
    // was confirmed to be 'application/json', causing every validation failure
    // (including a caught duplicate name) to silently redirect back to the index
    // page instead of returning 422 JSON. The AJAX request then auto-follows
    // that redirect, lands on a real 200 JSON response with no error info, and
    // the frontend, seeing a 200, reports it as a success even though nothing
    // was actually saved. Handling this explicitly with $request->wantsJson()
    // (the same simpler Accept-header check already used successfully elsewhere
    // in this controller) guarantees a duplicate can never be misreported as
    // "saved successfully" again.
    private function validationFailedResponse(Request $request, $validator){
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Please check the form and try again.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Store a newly created menu item. Accepts an optional uploaded image file
    // (stored on the public disk) instead of a raw image URL.
    public function store(Request $request){
        // Guardrail: Validate input strings via Ollama to prevent gibberish/malicious data.
        $guard = app(InputValidationService::class);
        $inputToValidate = trim($request->input('item_name', '') . ' ' . $request->input('category', ''));

        if (!empty($inputToValidate)) {
            $result = $guard->validateInput($inputToValidate);
            if (!$result['isValid']) {
                return $this->validationFailedResponse($request, (object)[
                    'errors' => ['item_name' => [$result['cleanReason']]]
                ]);
            }
        }

        $validator = Validator::make($request->all(), [
            'category' => ['required', 'string', 'max:50', $this->notPlaceholderRule()],
            'item_name' => ['required', 'string', 'max:100', $this->duplicateNameRule(), $this->notPlaceholderRule()],
            'price' => 'required|numeric|min:0',
            'is_available' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();

        $validated['is_available'] = (bool) $validated['is_available'];
        $validated['item_name'] = trim($validated['item_name']);
        $validated['category'] = trim($validated['category']);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $this->storeProtectedImage($request->file('image'), 'menu-items');
        }
        // 'image' isn't a real column, it was only used above to derive image_url.
        unset($validated['image']);

        try {
            $menuItem = MenuItem::create($validated);
        } catch (QueryException $e) {
            if ($this->isDuplicateNameViolation($e)) {
                return $this->duplicateNameResponse($request);
            }
            throw $e;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'menuItem' => $menuItem,
                'message' => 'Menu item added successfully.',
            ]);
        }

        return redirect()
            ->route('admin.menu.index')
            ->with('success', 'Menu item added successfully.');
    }

    // Kept for direct-link / no-JS access
    // Edit now opens as a popup pre-filled from the row's data attributes.
    public function edit(MenuItem $menuItem){
        return view('admin.menu.edit', [
            'menuItem' => $menuItem,
        ]);
    }

    // Update an existing menu item. Also used by the Status dropdown quick-action,
    // which doesn't know about description/image, so those are only overwritten
    // when actually present in the request, never silently wiped to null/blank.
    public function update(Request $request, MenuItem $menuItem){
        // Guardrail: Validate input strings via Ollama to prevent gibberish/malicious data.
        $guard = app(InputValidationService::class);
        $inputToValidate = trim($request->input('item_name', '') . ' ' . $request->input('category', ''));

        if (!empty($inputToValidate)) {
            $result = $guard->validateInput($inputToValidate);
            if (!$result['isValid']) {
                return $this->validationFailedResponse($request, (object)[
                    'errors' => ['item_name' => [$result['cleanReason']]]
                ]);
            }
        }

        $validator = Validator::make($request->all(), [
            'category' => ['required', 'string', 'max:50', $this->notPlaceholderRule()],
            'item_name' => ['required', 'string', 'max:100', $this->duplicateNameRule($menuItem->menu_item_id), $this->notPlaceholderRule()],
            'price' => 'required|numeric|min:0',
            'is_available' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();

        $validated['is_available'] = (bool) $validated['is_available'];
        $validated['item_name'] = trim($validated['item_name']);
        $validated['category'] = trim($validated['category']);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $this->storeProtectedImage($request->file('image'), 'menu-items');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteProtectedImage($menuItem->image_url, 'menu-items');
            $validated['image_url'] = null;
        }
        unset($validated['image']);

        try {
            $menuItem->update($validated);
        } catch (QueryException $e) {
            if ($this->isDuplicateNameViolation($e)) {
                return $this->duplicateNameResponse($request);
            }
            throw $e;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'menuItem' => $menuItem->fresh(),
                'message' => 'Menu item updated successfully.',
            ]);
        }

        return redirect()
            ->route('admin.menu.index')
            ->with('success', 'Menu item updated successfully.');
    }

    // Permanently remove a menu item.
    public function destroy(Request $request, MenuItem $menuItem){
        $menuItem->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Menu item deleted successfully.',
            ]);
        }

        return redirect()
            ->route('admin.menu.index')
            ->with('success', 'Menu item deleted successfully.');
    }
}