<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ClampsPaginationPage;
use App\Http\Controllers\Admin\Concerns\HandlesProtectedImageUploads;
use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Ingredient;
use App\Services\InputValidationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller{
    use ClampsPaginationPage;
    use HandlesProtectedImageUploads;

    protected $notificationService;

    public function __construct(\App\Services\NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

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
        }, $request, 10);

        $categories = MenuItem::query()->select('category')->distinct()->orderBy('category')->pluck('category');
        $allItemNames = MenuItem::query()->orderBy('item_name')->pluck('item_name');
        $ingredients = Ingredient::orderBy('ingredient_name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('admin.menu._table', [
                    'menuItems' => $menuItems,
                    'ingredients' => $ingredients,
                    'categories' => $categories,
                    'allItemNames' => $allItemNames,
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
            'ingredients' => $ingredients,
        ]);
    }

    public function create(){
        $categories = MenuItem::query()->select('category')->distinct()->orderBy('category')->pluck('category');
        $ingredients = Ingredient::withCurrentStock()->withLastDeliveryAt()->orderBy('ingredient_name')->get();
        $draft = session('menu_item_draft');

        return view('admin.menu.create', [
            'categories' => $categories,
            'ingredients' => $ingredients,
            'draft' => $draft,
        ]);
    }

    public function saveDraft(Request $request){
        if ($request->has('clear')) {
            session()->forget('menu_item_draft');
            return response()->json(['success' => true]);
        }

        $data = $request->all();

        // Extract item_name regardless of whether it's at the top level or inside 'inputs'
        $itemName = $request->input('item_name') ?? $request->input('inputs.item_name');
        $menuItemId = $request->input('menu_item_id') ?? $request->input('inputs.menu_item_id');

        // Run duplicate name check even for drafts to provide immediate feedback in Studio
        if ($itemName) {
            $name = trim($itemName);
            $exists = MenuItem::whereRaw('LOWER(TRIM(item_name)) = ?', [strtolower($name)])
                ->when($menuItemId, fn ($query) => $query->where('menu_item_id', '!=', $menuItemId))
                ->exists();

            if ($exists) {
                // We still save the draft so the user doesn't lose other progress,
                // but we return the error for the UI to highlight.
                if (!$menuItemId) {
                    session(['menu_item_draft' => $data]);
                } else {
                    session(["menu_item_edit_draft_{$menuItemId}" => $data]);
                }
                return response()->json([
                    'success' => true,
                    'errors' => ['item_name' => ['This item already exists in the menu.']]
                ], 200);
            }
        }

        // Save the draft to the session.
        // Use a specific key for edits to prevent leaking into the "Create New" flow.
        if (!$menuItemId) {
            session(['menu_item_draft' => $data]);
        } else {
            session(["menu_item_edit_draft_{$menuItemId}" => $data]);
        }

        return response()->json(['success' => true]);
    }

    private function descriptionRule(): array{
        return [];
    }

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

    private function validationMessages(): array{
        return [
            'image.mimes' => 'Please upload a JPG, JPEG, or PNG image.',
        ];
    }

    private function duplicateNameRule(?int $ignoreId = null): \Closure{
        return function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId) {
            $exists = MenuItem::whereRaw('LOWER(TRIM(item_name)) = ?', [strtolower(trim($value))])
                ->when($ignoreId, fn ($query) => $query->where('menu_item_id', '!=', (int)$ignoreId))
                ->exists();

            if ($exists) {
                $fail('This item already exists in the menu. Please check the name or edit the existing item.');
            }
        };
    }

    private function isDuplicateNameViolation(QueryException $e): bool{
        return $e->getCode() === '23505'
            && str_contains($e->getMessage(), 'menu_items_item_name_unique');
    }

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

    private function validationFailedResponse(Request $request, $validator){
        if ($request->wantsJson()) {
            return response()->json([
                'errors' => $validator instanceof \Illuminate\Contracts\Validation\Validator
                    ? $validator->errors()
                    : $validator->errors,
            ], 422);
        }

        return redirect()->back()->withErrors($validator)->withInput();
    }

    public function show(MenuItem $menuItem){
        return response()->json([
            'id' => $menuItem->menu_item_id,
            'item_name' => $menuItem->item_name,
            'category' => $menuItem->category,
            'price' => $menuItem->price,
            'image_url' => $menuItem->image_url,
            'is_available' => $menuItem->is_available,
            'recipe' => $menuItem->recipeIngredients()
                ->where('is_active', true)
                ->with('ingredient')
                ->get()
                ->map(fn($ri) => [
                    'ingredient_id' => $ri->ingredient_id,
                    'ingredient_name' => $ri->ingredient->ingredient_name ?? 'Unknown',
                    'unit' => $ri->ingredient->unit_of_measurement ?? '',
                    'qty_consumed' => $ri->qty_consumed,
                ]),
        ]);
    }

    public function store(Request $request){
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
            'description' => 'nullable|string|max:1000',
            'is_available' => 'nullable|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'recipe' => 'nullable|array',
            'recipe.*.ingredient_id' => 'nullable|integer|exists:ingredients,ingredient_id',
            'recipe.*.qty_consumed' => 'nullable|numeric|min:0.001',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();
        $validated['is_available'] = (bool) ($validated['is_available'] ?? 1);
        $validated['item_name'] = trim($validated['item_name']);
        $validated['category'] = trim($validated['category']);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $this->storeProtectedImage($request->file('image'), 'menu-items');
        }
        unset($validated['image']);

        try {
            $menuItem = MenuItem::create($validated);

            if ($request->has('recipe') && is_array($request->input('recipe'))) {
                foreach ($request->input('recipe') as $line) {
                    if (!empty($line['ingredient_id'])) {
                        \App\Models\RecipeIngredient::updateOrCreate(
                            ['menu_item_id' => $menuItem->menu_item_id, 'ingredient_id' => $line['ingredient_id']],
                            ['qty_consumed' => $line['qty_consumed'] ?? 0, 'is_active' => true]
                        );
                    }
                }
            }
            $isAvailable = $menuItem->syncAvailabilityStatus();
            $this->notificationService->notifyMenuAvailability($menuItem, $isAvailable);
        } catch (QueryException $e) {
            if ($this->isDuplicateNameViolation($e)) {
                return $this->duplicateNameResponse($request);
            }
            throw $e;
        }

        if ($request->wantsJson()) {
            session()->put('menu_success', 'Menu item added successfully.');
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

    public function edit(MenuItem $menuItem){
        $categories = MenuItem::query()->select('category')->distinct()->orderBy('category')->pluck('category');
        $ingredients = Ingredient::withCurrentStock()->withLastDeliveryAt()->orderBy('ingredient_name')->get();

        // Priority: 1. Session Edit Draft -> 2. Actual DB Item Data
        $draft = session("menu_item_edit_draft_{$menuItem->menu_item_id}");

        if (!$draft) {
            // Prepare the item data in the format the Studio expects
            $draft = [
                'item_name' => $menuItem->item_name,
                'category' => $menuItem->category,
                'price' => $menuItem->price,
                'description' => $menuItem->description,
                'is_available' => $menuItem->is_available ? '1' : '0',
                'imagePreview' => $menuItem->image_url,
                'recipe' => $menuItem->recipeIngredients()
                    ->where('is_active', true)
                    ->with('ingredient')
                    ->get()
                    ->map(fn($ri) => [
                        'id' => $ri->ingredient_id,
                        'name' => $ri->ingredient->ingredient_name ?? 'Unknown',
                        'unit' => $ri->ingredient->unit_of_measurement ?? '',
                        'cost' => $ri->ingredient->unit_cost ?? 0,
                        'qty' => $ri->qty_consumed,
                    ])->toArray()
            ];
        }

        return view('admin.menu.create', [
            'categories' => $categories,
            'ingredients' => $ingredients,
            'draft' => $draft, // Pass item data (or draft) to pre-populate the studio
            'editingItem' => $menuItem, // Pass the model to identify we are in edit mode
        ]);
    }

    public function update(Request $request, MenuItem $menuItem){
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
            'description' => 'nullable|string|max:1000',
            'is_available' => 'nullable|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'recipe' => 'nullable|array',
            'recipe.*.ingredient_id' => 'nullable|integer|exists:ingredients,ingredient_id',
            'recipe.*.qty_consumed' => 'nullable|numeric|min:0.001',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();
        $validated['is_available'] = (bool) ($validated['is_available'] ?? 1);
        $validated['item_name'] = trim($validated['item_name']);
        $validated['category'] = trim($validated['category']);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $this->storeProtectedImage($request->file('image'), 'menu-items');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteProtectedImage($menuItem->image_url, 'menu-items');
            $validated['image_url'] = null;
        } else {
            unset($validated['image_url']);
        }
        unset($validated['image']);

        try {
            $oldAvailability = $menuItem->is_available;
            $menuItem->update($validated);

            if ($request->has('recipe') && is_array($request->input('recipe'))) {
                \App\Models\RecipeIngredient::where('menu_item_id', $menuItem->menu_item_id)
                    ->update(['is_active' => false]);

                foreach ($request->input('recipe') as $line) {
                    if (!empty($line['ingredient_id'])) {
                        \App\Models\RecipeIngredient::updateOrCreate(
                            ['menu_item_id' => $menuItem->menu_item_id, 'ingredient_id' => $line['ingredient_id']],
                            ['qty_consumed' => $line['qty_consumed'] ?? 0, 'is_active' => true]
                        );
                    }
                }
            }
            $newAvailability = $menuItem->syncAvailabilityStatus();

            if ($oldAvailability !== $newAvailability) {
                $this->notificationService->notifyMenuAvailability($menuItem, $newAvailability);
            }
        } catch (QueryException $e) {
            if ($this->isDuplicateNameViolation($e)) {
                return $this->duplicateNameResponse($request);
            }
            throw $e;
        }

        if ($request->wantsJson()) {
            session()->put('menu_success', 'Menu item updated successfully.');
            session()->forget("menu_item_edit_draft_{$menuItem->menu_item_id}");
            return response()->json([
                'success' => true,
                'menuItem' => $menuItem->fresh(),
                'message' => 'Menu item updated successfully.',
            ]);
        }

        session()->forget("menu_item_edit_draft_{$menuItem->menu_item_id}");

        return redirect()
            ->route('admin.menu.index')
            ->with('success', 'Menu item updated successfully.');
    }

    public function destroy(Request $request, MenuItem $menuItem){
        // Delete associated recipe ingredients first to avoid foreign key violation
        \App\Models\RecipeIngredient::where('menu_item_id', $menuItem->menu_item_id)->delete();

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
