<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ClampsPaginationPage;
use App\Http\Controllers\Admin\Concerns\HandlesProtectedImageUploads;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\StockMovement;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InventoryController extends Controller{
    use ClampsPaginationPage;
    use HandlesProtectedImageUploads;

    protected $notificationService;

    public function __construct(\App\Services\NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public const MOVEMENT_TYPES = [
        'delivery' => 'Supplier Delivery / Purchase',
        'opening_balance' => 'Opening Balance',
        'manual_adjustment' => 'Physical Count Adjustment',
        'spoilage' => 'Waste / Spillage / Damage',
        'internal_use' => 'Internal Consumption / Testing',
        'sale_deduction' => 'Sales Deduction',
        'void' => 'Voided Entry',
    ];

    public const RECORDABLE_MOVEMENT_TYPES = ['opening_balance', 'delivery', 'manual_adjustment', 'spoilage', 'internal_use'];

    // Placeholder/test values rejected outright wherever a human-entered label
    // has no other structural way to catch nonsense, ingredient name and
    // category. Once saved, "Test" is indistinguishable from a real entry
    // anywhere else in the system (recipes, reports, Smart Assistant), so
    // this is checked at the one point of entry rather than downstream.
    private const BLOCKED_PLACEHOLDER_VALUES = [
        'test', 'testing', 'tests', 'sample', 'samples', 'sample data',
        'asdf', 'asdfgh', 'qwerty', 'foo', 'bar', 'foobar', 'xxx', 'xyz',
        'n/a', 'na', 'none', 'null', 'undefined', 'todo', 'tbd', 'temp',
        'temporary', 'example', 'demo', 'dummy', 'placeholder', 'lorem ipsum',
        'asd', 'abc', '123', 'zzz',
    ];

    public function index(Request $request){
        $ingredients = $this->paginateClamped(function () use ($request) {
            return $this->getFilteredIngredientsQuery($request);
        }, $request, 4);

        $units = collect(Ingredient::UNITS)
            ->merge(Ingredient::query()->pluck('unit_of_measurement'))
            ->filter()->unique()->sort()->values();
        $stats = $this->currentStockStats();
        $allIngredientNames = Ingredient::query()->orderBy('ingredient_name')->pluck('ingredient_name');
        $menuItems = MenuItem::query()->orderBy('item_name')->get(['menu_item_id', 'item_name']);

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('admin.inventory._table', ['ingredients' => $ingredients])->render(),
                'statsHtml' => view('admin.inventory._current_stock_stats', ['stats' => $stats])->render(),
                'ingredientSuggestionsHtml' => view('admin.partials._search_suggestions', [
                    'suggestions' => $allIngredientNames,
                ])->render(),
            ]);
        }

        return view('admin.inventory.index', [
            'ingredients' => $ingredients,
            'units' => $units,
            'stats' => $stats,
            'allIngredientNames' => $allIngredientNames,
            'menuItems' => $menuItems,
        ]);
    }

    private function getFilteredIngredientsQuery(Request $request) {
        $query = Ingredient::withCurrentStock()->withLastDeliveryAt();

        if ($request->filled('search')) {
            $query->where('ingredient_name', 'ILIKE', '%' . $request->input('search') . '%');
        }

        if ($request->filled('status') && in_array($request->input('status'), Ingredient::STATUSES, true)) {
            $query->whereIn('ingredient_id', $this->ingredientIdsMatchingStatus($request->input('status')));
        }

        if ($request->input('sort') === 'critical') {
            $query->orderByRaw('
                CASE
                    WHEN (SELECT SUM(qty_changed) FROM stock_movements WHERE stock_movements.ingredient_id = ingredients.ingredient_id) <= 0 THEN 0
                    WHEN (SELECT SUM(qty_changed) FROM stock_movements WHERE stock_movements.ingredient_id = ingredients.ingredient_id) <= ingredients.restock_threshold THEN 1
                    ELSE 2
                END ASC
            ');
        }

        return $query->orderByDesc('ingredient_id');
    }

    public function export(Request $request) {
        $ingredients = $this->getFilteredIngredientsQuery($request)->get();

        $filename = "desiflow-inventory-" . now()->toDateString() . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($ingredients) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Ingredient', 'Linked Menu Items', 'Unit', 'Current Stock', 'Unit Cost', 'Threshold', 'Status']);

            foreach ($ingredients as $ingredient) {
                $linkedItems = $ingredient->recipeIngredients()
                    ->where('is_active', true)
                    ->with('menuItem')
                    ->get()
                    ->map(fn($ri) => $ri->menuItem->item_name)
                    ->implode(', ');

                fputcsv($file, [
                    '#ITM-' . str_pad($ingredient->ingredient_id, 3, '0', STR_PAD_LEFT),
                    $ingredient->ingredient_name,
                    $linkedItems ?: 'None',
                    $ingredient->unit_of_measurement,
                    number_format($ingredient->current_stock ?? 0, 2),
                    '₱' . number_format($ingredient->unit_cost, 2),
                    number_format($ingredient->restock_threshold, 2),
                    $ingredient->stockStatus(),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    // Every ingredient_id currently classified under the given status,
    // computed via Ingredient::stockStatus() so this can never drift from
    // the table badge / stat card definitions of each status.
    private function ingredientIdsMatchingStatus(string $status): array{
        return Ingredient::withCurrentStock()->withLastDeliveryAt()->get()
            ->filter(fn ($ingredient) => $ingredient->stockStatus() === $status)
            ->pluck('ingredient_id')
            ->all();
    }

    private function currentStockStats(): array{
        $ingredients = Ingredient::withCurrentStock()->get(['ingredient_id', 'unit_cost', 'restock_threshold']);
        $lowStock = 0; $outOfStock = 0; $totalValue = 0;

        foreach ($ingredients as $ingredient) {
            $status = $ingredient->stockStatus();
            if ($status === 'low_stock') $lowStock++;
            if ($status === 'out_of_stock') $outOfStock++;
            $totalValue += ($ingredient->current_stock ?? 0) * $ingredient->unit_cost;
        }

        return [
            'totalItems' => $ingredients->count(),
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'totalValue' => $totalValue,
        ];
    }

    public function create(){
        return view('admin.inventory.create');
    }

    private function validationMessages(): array{
        return [
            'image.mimes' => 'Please upload a JPG, JPEG, or PNG image.',
            'unit_of_measurement.in' => 'Please select a valid unit of measurement from the list.',
        ];
    }

    private function shelfLifeDaysFromExpirationDate(?string $expirationDate, ?Carbon $anchor = null): ?int{
        if (! $expirationDate) return null;
        return (int) ($anchor ?? now())->copy()->startOfDay()
            ->diffInDays(Carbon::parse($expirationDate)->startOfDay(), false);
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
            if (! preg_match('/[a-zA-Z]/', $normalized)) {
                $fail('Please enter a value that includes letters.');
            }
        };
    }

    // Unit of measurement is locked to a closed list (curated starters +
    // whatever's already legitimately in use), not free text, "Test" or
    // "Joules" have no meaning as a food ingredient's unit.
    private function allowedUnits(): array{
        return collect(Ingredient::UNITS)
            ->merge(Ingredient::query()->pluck('unit_of_measurement'))
            ->filter()->map(fn ($u) => trim($u))->unique()->values()->all();
    }

    private function duplicateNameRule(?int $ignoreId = null): \Closure{
        return function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId) {
            $exists = Ingredient::whereRaw('LOWER(TRIM(ingredient_name)) = ?', [strtolower(trim($value))])
                ->when($ignoreId, fn ($query) => $query->where('ingredient_id', '!=', $ignoreId))
                ->exists();
            if ($exists) {
                $fail('This ingredient already exists in your inventory. Please check the name or edit the existing ingredient.');
            }
        };
    }

    private function isDuplicateNameViolation(QueryException $e): bool{
        return $e->getCode() === '23505' && str_contains($e->getMessage(), 'ingredients_ingredient_name_unique');
    }

    private function duplicateNameResponse(Request $request){
        $message = 'This ingredient already exists in your inventory. Please check the name or edit the existing ingredient.';
        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'errors' => ['ingredient_name' => [$message]]], 422);
        }
        return redirect()->back()->withInput()->withErrors(['ingredient_name' => $message]);
    }

    private function validationFailedResponse(Request $request, $validator){
        if ($request->wantsJson()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        return redirect()->back()->withErrors($validator)->withInput();
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'ingredient_name' => ['required', 'string', 'max:100', $this->notPlaceholderRule(), $this->duplicateNameRule()],
            'menu_items' => ['nullable', 'array', 'max:50'],
            'menu_items.*' => ['integer', 'exists:menu_items,menu_item_id'],
            'unit_of_measurement' => ['required', 'string', 'max:20', Rule::in($this->allowedUnits())],
            'unit_cost' => 'required|numeric|min:0',
            'restock_threshold' => 'required|numeric|min:0',
            'expiration_date' => 'nullable|date|after_or_equal:today',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();
        $validated['ingredient_name'] = trim($validated['ingredient_name']);
        $validated['unit_of_measurement'] = trim($validated['unit_of_measurement']);

        $validated['shelf_life_days'] = $this->shelfLifeDaysFromExpirationDate($validated['expiration_date'] ?? null);
        unset($validated['expiration_date']);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $this->storeProtectedImage($request->file('image'), 'ingredients');
        }
        unset($validated['image']);

        try {
            $ingredient = Ingredient::create($validated);

            // Sync Linked Menu Items
            if (!empty($validated['menu_items'])) {
                foreach ($validated['menu_items'] as $menuItemId) {
                    \App\Models\RecipeIngredient::create([
                        'menu_item_id' => $menuItemId,
                        'ingredient_id' => $ingredient->ingredient_id,
                        'qty_consumed' => 1.00,
                        'is_active' => true,
                    ]);
                }
            }
        } catch (QueryException $e) {
            if ($this->isDuplicateNameViolation($e)) return $this->duplicateNameResponse($request);
            throw $e;
        }

        $ingredient->current_stock = 0;

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'ingredient' => $ingredient, 'message' => 'Ingredient added successfully.']);
        }
        return redirect()->route('admin.inventory.index')->with('success', 'Ingredient added successfully.');
    }

    public function edit(Ingredient $ingredient){
        return view('admin.inventory.edit', ['ingredient' => $ingredient]);
    }

    public function update(Request $request, Ingredient $ingredient){
        $validator = Validator::make($request->all(), [
            'ingredient_name' => ['required', 'string', 'max:100', $this->notPlaceholderRule(), $this->duplicateNameRule($ingredient->ingredient_id)],
            'menu_items' => ['nullable', 'array', 'max:50'],
            'menu_items.*' => ['integer', 'exists:menu_items,menu_item_id'],
            'unit_of_measurement' => ['required', 'string', 'max:20', Rule::in($this->allowedUnits())],
            'unit_cost' => 'required|numeric|min:0',
            'restock_threshold' => 'required|numeric|min:0',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();
        $validated['ingredient_name'] = trim($validated['ingredient_name']);
        $validated['unit_of_measurement'] = trim($validated['unit_of_measurement']);

        try {
            $ingredient->update($validated);

            // Sync Linked Menu Items
            $selectedItems = $validated['menu_items'] ?? [];

            // 1. Deactivate links not in the current selection
            \App\Models\RecipeIngredient::where('ingredient_id', $ingredient->ingredient_id)
                ->whereNotIn('menu_item_id', $selectedItems)
                ->update(['is_active' => false]);

            // 2. Activate or create links for selected items
            foreach ($selectedItems as $menuItemId) {
                \App\Models\RecipeIngredient::updateOrCreate(
                    ['menu_item_id' => $menuItemId, 'ingredient_id' => $ingredient->ingredient_id],
                    ['is_active' => true]
                );
            }

            $this->notificationService->checkAndNotifyInventoryStatus($ingredient->ingredient_id);
        } catch (QueryException $e) {
            if ($this->isDuplicateNameViolation($e)) return $this->duplicateNameResponse($request);
            throw $e;
        }

        $ingredient = Ingredient::withCurrentStock()->find($ingredient->ingredient_id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'ingredient' => $ingredient, 'message' => 'Ingredient updated successfully.']);
        }
        return redirect()->route('admin.inventory.index')->with('success', 'Ingredient updated successfully.');
    }

    public function destroy(Request $request, Ingredient $ingredient){
        try {
            $ingredient->delete();
        } catch (QueryException $e) {
            $message = "Can't delete \"{$ingredient->ingredient_name}\" — it has recipe or stock movement history tied to it.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 409);
            }
            return redirect()->route('admin.inventory.index')->with('error', $message);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Ingredient deleted successfully.']);
        }
        return redirect()->route('admin.inventory.index')->with('success', 'Ingredient deleted successfully.');
    }

    public function wasteExpired(Request $request, Ingredient $ingredient){
        try {
            // 1. Calculate Total Expired Supply (FIFO: everything delivered by today, ignoring voids)
            $totalExpiredSupply = \App\Models\StockMovement::where('ingredient_id', $ingredient->ingredient_id)
                ->whereNull('voided_at')
                ->whereIn('movement_type', ['delivery', 'opening_balance'])
                ->where('expiration_date', '<=', now()->toDateString())
                ->sum('qty_changed');

            // 2. Calculate Total Consumption (All non-voided outflows)
            // Since we use FIFO, we assume the oldest (and thus most likely expired) stock was used first.
            $totalConsumption = \App\Models\StockMovement::where('ingredient_id', $ingredient->ingredient_id)
                ->whereNull('voided_at')
                ->where(function($q) {
                    $q->whereIn('movement_type', ['sale_deduction', 'spoilage', 'internal_use'])
                      ->orWhere(function($sq) {
                          $sq->where('movement_type', 'manual_adjustment')
                             ->where('qty_changed', '<', 0);
                      });
                })
                ->sum('qty_changed');

            $totalConsumption = abs($totalConsumption);

            // 3. The remaining expired stock is the supply that hasn't been consumed yet.
            $expiredQty = $totalExpiredSupply - $totalConsumption;

            // We can't waste more than what we actually have in stock.
            $currentStock = $ingredient->stockMovements()->sum('qty_changed');
            $wasteAmount = min($currentStock, max(0, $expiredQty));

            if ($wasteAmount <= 0) {
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => 'No expired stock available to mark as waste.'], 422)
                    : redirect()->back()->with('error', 'No expired stock available to mark as waste.');
            }

            \App\Models\StockMovement::create([
                'ingredient_id' => $ingredient->ingredient_id,
                'user_id' => auth()->id(),
                'movement_type' => 'spoilage',
                'qty_changed' => -$wasteAmount,
                'recorded_at' => now(),
                'notes' => 'Automatic waste deduction of expired stock.',
            ]);

            $this->notificationService->checkAndNotifyInventoryStatus($ingredient->ingredient_id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Marked " . number_format($wasteAmount, 2) . " as waste.",
                    'newTotal' => $currentStock - $wasteAmount
                ]);
            }
            return redirect()->route('admin.inventory.index')->with('success', 'Expired stock marked as waste.');
        } catch (\Throwable $e) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Internal Server Error'], 500)
                : redirect()->back()->with('error', 'Internal Server Error');
        }
    }

    public function recordMovement(Request $request, Ingredient $ingredient){
        try {
            $validated = $request->validate([
                'movement_type' => 'required|in:' . implode(',', self::RECORDABLE_MOVEMENT_TYPES),
                'qty_changed' => 'required|numeric',
                'delivery_date' => 'nullable|date',
                'expiration_date' => 'nullable|date',
                'notes' => 'nullable|string|max:255',
                'reference_number' => 'nullable|string|max:100',
            ]);

            $type = $validated['movement_type'];
            $qty = (float) $validated['qty_changed'];

            if ($type !== 'delivery' && (($validated['delivery_date'] ?? null) || ($validated['reference_number'] ?? null))) {
                \Log::info('DEBUG: delivery date/ref number check failed');
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => 'Delivery date and reference number are only permitted for Supplier Deliveries.'], 422)
                    : redirect()->back()->with('error', 'Delivery date and reference number are only permitted for Supplier Deliveries.')->withInput();
            }

            $expRequired = ($type === 'delivery' || $type === 'opening_balance' || ($type === 'manual_adjustment' && $qty > 0));
            if ($expRequired && empty($validated['expiration_date'])) {
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => 'Expiration date is required for this movement type.'], 422)
                    : redirect()->back()->with('error', 'Expiration date is required for this movement type.')->withInput();
            }
            if (!$expRequired && !empty($validated['expiration_date'])) {
                $validated['expiration_date'] = null;
            }

            if ($type === 'opening_balance' && \App\Models\StockMovement::where('ingredient_id', $ingredient->ingredient_id)->where('movement_type', 'opening_balance')->exists()) {
                \Log::info('DEBUG: opening balance check failed');
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => 'An opening balance already exists for this ingredient.'], 422)
                    : redirect()->back()->with('error', 'An opening balance already exists for this ingredient.')->withInput();
            }

            if (in_array($validated['movement_type'], ['delivery', 'opening_balance'], true)) {
                $current = Ingredient::withCurrentStock()->withLastDeliveryAt()->find($ingredient->ingredient_id);
                if ($current && $current->stockStatus() === 'expired') {
                    $message = "Expired stock. Mark as Waste before delivery.";
                    if ($request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $message], 422);
                    }
                    return redirect()->back()->with('error', $message);
                }
            }

            $validated['qty_changed'] = $this->normalizeQtySign($validated['movement_type'], (float) $validated['qty_changed']);

            \Log::info('DEBUG: creating stock movement');
            $movement = StockMovement::create([
                'ingredient_id' => $ingredient->ingredient_id,
                'user_id' => auth()->id(),
                'movement_type' => $validated['movement_type'],
                'qty_changed' => $validated['qty_changed'],
                'recorded_at' => now(),
                'delivery_date' => $validated['delivery_date'] ?? null,
                'expiration_date' => $validated['expiration_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
            ])->load(['recordedBy', 'ingredient']);

            $this->notificationService->checkAndNotifyInventoryStatus($ingredient->ingredient_id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'movement' => $movement,
                    'newTotal' => $ingredient->stockMovements()->sum('qty_changed'),
                    'message' => 'Stock movement recorded.',
                ]);
            }
            return redirect()->route('admin.inventory.index')->with('success', 'Stock movement recorded.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Internal Server Error: ' . $e->getMessage())->withInput();
        }
    }

    public function updateMovement(Request $request, StockMovement $movement){
        if ($this->isMovementLocked($movement)) {
            return $this->movementLockedResponse($request);
        }
        if ($this->isIngredientExpired($movement->ingredient_id)) {
            return $this->expiredIngredientLockedResponse($request);
        }

        $validated = $request->validate([
            'qty_changed' => 'required|numeric',
            'delivery_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
            'edit_reason' => 'required|string|max:150',
            'recorded_at' => 'nullable|date',
        ]);

        $movement->qty_changed = $this->normalizeQtySign($movement->movement_type, (float) $validated['qty_changed']);
        $movement->edit_reason = $validated['edit_reason'];
        if (! empty($validated['recorded_at'])) {
            $movement->recorded_at = $validated['recorded_at'];
        }
        $movement->delivery_date = $validated['delivery_date'] ?? $movement->delivery_date;
        $movement->expiration_date = $validated['expiration_date'] ?? $movement->expiration_date;
        $movement->reference_number = $validated['reference_number'] ?? $movement->reference_number;
        $movement->notes = $validated['notes'] ?? $movement->notes;
        $movement->save();
        $movement->load(['recordedBy', 'ingredient']);

        $this->notificationService->checkAndNotifyLowStock($movement->ingredient_id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'movement' => $movement,
                'newTotal' => $movement->ingredient->stockMovements()->sum('qty_changed'),
                'message' => 'Stock movement updated.',
            ]);
        }
        return redirect()->route('admin.inventory.ledger')->with('success', 'Stock movement updated.');
    }

    public function destroyMovement(Request $request, StockMovement $movement){
        if ($this->isMovementLocked($movement)) {
            return $this->movementLockedResponse($request);
        }
        if ($this->isIngredientExpired($movement->ingredient_id)) {
            return $this->expiredIngredientLockedResponse($request);
        }

        $voidEntry = StockMovement::create([
            'ingredient_id' => $movement->ingredient_id,
            'user_id' => auth()->id(),
            'movement_type' => 'void',
            'qty_changed' => -$movement->qty_changed,
            'recorded_at' => now(),
            'notes' => "Voided Entry #{$movement->movement_id}",
            'voids_movement_id' => $movement->movement_id,
        ]);

        $movement->voided_at = now();
        $movement->save();

        $ingredient = $movement->ingredient;

        $this->notificationService->checkAndNotifyLowStock($ingredient->ingredient_id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'voidEntry' => $voidEntry->load(['recordedBy', 'ingredient']),
                'newTotal' => $ingredient->stockMovements()->sum('qty_changed'),
                'message' => 'Movement voided — a reversing entry was recorded.',
            ]);
        }
        return redirect()->route('admin.inventory.ledger')->with('success', 'Movement voided.');
    }

    private function isMovementLocked(StockMovement $movement): bool{
        return $movement->movement_type === 'void' || $movement->voided_at !== null;
    }

    private function movementLockedResponse(Request $request){
        $message = "This entry can't be edited or voided again — it's already voided, or is itself a voiding entry.";
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 409);
        }
        return redirect()->route('admin.inventory.ledger')->with('error', $message);
    }

    // True when the ingredient this movement belongs to is CURRENTLY
    // expired (not at the time the movement was recorded). Drives the full
    // Edit/Void lockout on every entry tied to an expired ingredient: once
    // expired, nothing in that ingredient's history can be altered until
    // the expired stock is written off as waste, which lowers current_stock
    // to 0 and naturally releases the lock (out_of_stock outranks expired
    // in Ingredient::stockStatus()'s priority order).
    private function isIngredientExpired(int $ingredientId): bool{
        $ingredient = Ingredient::withCurrentStock()->withLastDeliveryAt()->find($ingredientId);
        return $ingredient && $ingredient->stockStatus() === 'expired';
    }

    private function expiredIngredientLockedResponse(Request $request){
        $message = "This ingredient is currently expired. Its stock movement history is locked for food-safety and audit reasons — write off the expired stock as waste first.";
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 409);
        }
        return redirect()->route('admin.inventory.ledger')->with('error', $message);
    }

    private function normalizeQtySign(string $type, float $qty): float{
        return match ($type) {
            'delivery', 'opening_balance' => abs($qty),
            'spoilage', 'internal_use' => -abs($qty),
            default => $qty,
        };
    }

    // Per-ingredient history is no longer a standalone view, redirects into
    // the consolidated ledger pre-filtered to this ingredient, so old links
    // (notification bell, ledger table) keep working.
    public function history(Ingredient $ingredient){
        return redirect()->route('admin.inventory.ledger', ['ingredient_id' => $ingredient->ingredient_id]);
    }

    public function hasOpeningBalance(Ingredient $ingredient){
        $exists = \App\Models\StockMovement::where('ingredient_id', $ingredient->ingredient_id)
            ->where('movement_type', 'opening_balance')
            ->exists();
        return response()->json(['hasOpeningBalance' => $exists]);
    }

    public function ledgerIndex(Request $request){
        $movements = $this->paginateClamped(function () use ($request) {
            $query = StockMovement::with(['ingredient', 'recordedBy']);

            if ($request->filled('ingredient_id')) {
                $query->where('ingredient_id', $request->input('ingredient_id'));
            }
            if ($request->filled('type') && array_key_exists($request->input('type'), self::MOVEMENT_TYPES)) {
                $query->where('movement_type', $request->input('type'));
            }
            if ($request->filled('date_from')) {
                $query->whereDate('recorded_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('recorded_at', '<=', $request->input('date_to'));
            }
            // Matches the movement RECORD's own data only, ingredient name,
            // reference number, notes, not who recorded it.
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('recordedBy', fn ($q) => $q->where('name', 'ILIKE', "%{$search}%"));
            }

            return $query->orderByDesc('recorded_at');
        }, $request, 4);

        $ingredients = Ingredient::orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name', 'unit_of_measurement']);

        $searchSuggestions = \App\Models\User::orderBy('name')->pluck('name');

        $expiredIngredientIds = Ingredient::withCurrentStock()->withLastDeliveryAt()->get()
            ->filter(fn ($i) => $i->stockStatus() === 'expired')
            ->pluck('ingredient_id');

        return view('admin.inventory.ledger', [
            'movements' => $movements,
            'ingredients' => $ingredients,
            'selectedIngredientId' => $request->input('ingredient_id'),
            'selectedType' => $request->input('type'),
            'stats' => $this->ledgerStats(),
            'searchSuggestions' => $searchSuggestions,
            'expiredIngredientIds' => $expiredIngredientIds,
        ]);
    }

    private function ledgerStats(): array{
        $totalMovementsToday = StockMovement::whereDate('recorded_at', today())->count();
        $restocksCompleted = StockMovement::where('movement_type', 'delivery')->count();
        $wasteItemCount = StockMovement::where('movement_type', 'spoilage')
            ->distinct('ingredient_id')->count('ingredient_id');

        $valueNow = 0; $valueWeekAgo = 0;
        $weekAgoCutoff = now()->subDays(7);

        Ingredient::with(['stockMovements' => function ($q) {
            $q->select('ingredient_id', 'qty_changed', 'recorded_at');
        }])->get(['ingredient_id', 'unit_cost'])->each(function ($ingredient) use (&$valueNow, &$valueWeekAgo, $weekAgoCutoff) {
            $stockNow = $ingredient->stockMovements->sum('qty_changed');
            $stockWeekAgo = $ingredient->stockMovements
                ->filter(fn ($m) => $m->recorded_at->lte($weekAgoCutoff))
                ->sum('qty_changed');
            $valueNow += $stockNow * $ingredient->unit_cost;
            $valueWeekAgo += $stockWeekAgo * $ingredient->unit_cost;
        });

        $netStockChangePercent = match (true) {
            $valueWeekAgo > 0 => (($valueNow - $valueWeekAgo) / $valueWeekAgo) * 100,
            $valueNow > 0 => 100.0,
            default => 0.0,
        };

        return [
            'totalMovementsToday' => $totalMovementsToday,
            'restocksCompleted' => $restocksCompleted,
            'wasteItemCount' => $wasteItemCount,
            'netStockChangePercent' => $netStockChangePercent,
        ];
    }
}