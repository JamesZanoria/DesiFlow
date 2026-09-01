<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller{
    // Movement types the ledger understands. 'sale_deduction' has no writer
    // yet, it's meant to be created automatically by the Kitchen Display's
    // Serve action (backflushing), which doesn't exist as a built
    // module yet. It's included here so the ledger's type filter is already
    // correct and future-proofed; it will simply show zero results until
    // that module is built, rather than being retrofitted in later.
    public const MOVEMENT_TYPES = [
        'delivery' => 'Restock',
        'sale_deduction' => 'Sales Deduction',
        'manual_adjustment' => 'Manual Adj',
        'spoilage' => 'Waste',
    ];

    // Display a paginated, searchable, filterable listing of all ingredients
    // with current stock level.
    public function index(Request $request){
        $query = Ingredient::withCurrentStock();

        if ($request->filled('search')) {
            $query->where('ingredient_name', 'ILIKE', '%' . $request->input('search') . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // "Critical First" sorts by stock severity (out of stock, then low
        // stock, then active), each tier alphabetical within itself,
        // otherwise plain alphabetical by name. Sorting has to happen in the
        // database, not in PHP after fetching, since the result is paginated;
        // `current_stock` here refers to the SELECT-list alias withCurrentStock()
        // creates via withSum(), which Postgres allows ordering by directly.
        if ($request->input('sort') === 'critical') {
            $query->orderByRaw('
                CASE
                    WHEN COALESCE(current_stock, 0) <= 0 THEN 0
                    WHEN COALESCE(current_stock, 0) <= restock_threshold THEN 1
                    ELSE 2
                END ASC
            ');
        }

        $ingredients = $query->orderBy('ingredient_name')
            ->paginate(5)
            ->appends($request->query());

        $units = Ingredient::query()->select('unit_of_measurement')->distinct()->orderBy('unit_of_measurement')->pluck('unit_of_measurement');
        $categories = Ingredient::query()->whereNotNull('category')->select('category')->distinct()->orderBy('category')->pluck('category');
        $stats = $this->currentStockStats();

        // AJAX refresh: after Add/Edit/Delete, or a Prev/Next/numbered-page click,
        // the frontend re-requests this same endpoint asking for JSON instead of a
        // full page, and swaps in just the table+pagination fragment
        // (admin.inventory._table) plus the stat cards and category filter, both
        // of which can go stale the same way Menu Management's did — an ingredient
        // added with a new category, or one that crosses a stock threshold,
        // otherwise wouldn't show up until a real page reload.
        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('admin.inventory._table', [
                    'ingredients' => $ingredients,
                ])->render(),
                'statsHtml' => view('admin.inventory._current_stock_stats', [
                    'stats' => $stats,
                ])->render(),
                'categoryOptionsHtml' => view('admin.partials._filter_select_popover', [
                    'name' => 'category',
                    'label' => 'Category',
                    'value' => $request->input('category'),
                    'options' => $categories->mapWithKeys(fn ($c) => [$c => $c]),
                ])->render(),
            ]);
        }

        return view('admin.inventory.index', [
            'ingredients' => $ingredients,
            'units' => $units,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    // Total Items / Low Stock / Out of Stock / Total Value stat cards.
    // Low Stock and Out of Stock are deliberately non-overlapping counts
    // (each ingredient's stockStatus() puts it in exactly one tier) rather
    // than Low Stock including everything at-or-below threshold the way
    // isLowStock() does elsewhere, with a dedicated Out of Stock card now
    // existing, double-counting the same ingredients in both would be
    // confusing rather than just redundant.
    private function currentStockStats(): array{
        $ingredients = Ingredient::withCurrentStock()->get(['ingredient_id', 'unit_cost', 'restock_threshold']);

        $lowStock = 0;
        $outOfStock = 0;
        $totalValue = 0;

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

    // Kept for direct-link / no-JS access
    // Add Ingredient now opens as a popup on the index page instead.
    public function create(){
        return view('admin.inventory.create');
    }

    // Shared validation rules: required text that must not contain any digits
    // (0-9), matches ingredient_name and unit_of_measurement, both of which the
    // frontend already blocks digits on while typing.
    private function textNoDigitsRule(): array{
        return ['required', 'string', 'regex:/^[^0-9]+$/u'];
    }

    private function validationMessages(): array{
        return [
            'ingredient_name.regex' => 'Ingredient name must not contain any numbers.',
            'unit_of_measurement.regex' => 'Unit of measurement must not contain any numbers.',
            'image.mimes' => 'Please upload a JPG, JPEG, or PNG image.',
        ];
    }

    // Rejects an ingredient name that already exists (case-insensitive,
    // whitespace-trimmed), mirroring the same rule used for menu items.
    // $ignoreId excludes the record currently being edited from the check.
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

    // Postgres unique_violation is SQLSTATE 23505 — the same last-line-of-defense
    // pattern as MenuController, for the ingredients_ingredient_name_unique index.
    private function isDuplicateNameViolation(QueryException $e): bool{
        return $e->getCode() === '23505'
            && str_contains($e->getMessage(), 'ingredients_ingredient_name_unique');
    }

    private function duplicateNameResponse(Request $request){
        $message = 'This ingredient already exists in your inventory. Please check the name or edit the existing ingredient.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => ['ingredient_name' => [$message]],
            ], 422);
        }

        return redirect()->back()->withInput()->withErrors(['ingredient_name' => $message]);
    }

    // Validated manually (rather than $request->validate()) so this controller
    // decides redirect-vs-422 itself via $request->wantsJson(), the same fix
    // applied to MenuController after discovering Laravel's automatic
    // expectsJson()-based exception handling was returning a 302 redirect for
    // AJAX validation failures even with a correct Accept header, which caused
    // the frontend to auto-follow the redirect and misreport failures as
    // successes.
    private function validationFailedResponse(Request $request, $validator){
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Please check the form and try again.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Store a newly created ingredient. Accepts an optional initial_stock value,
    // if provided, this creates a real 'delivery' entry in the stock ledger rather
    // than a raw stock number, so the append-only audit trail stays accurate.
    // Also accepts an optional image, handled the same way MenuController does.
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'ingredient_name' => [...$this->textNoDigitsRule(), 'max:100', $this->duplicateNameRule()],
            'category' => ['nullable', 'string', 'max:50', 'regex:/^[^0-9]*$/u'],
            'unit_of_measurement' => [...$this->textNoDigitsRule(), 'max:20'],
            'unit_cost' => 'required|numeric|min:0',
            'restock_threshold' => 'required|numeric|min:0',
            'shelf_life_days' => 'nullable|integer|min:0',
            'initial_stock' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();
        $validated['ingredient_name'] = trim($validated['ingredient_name']);
        $validated['unit_of_measurement'] = trim($validated['unit_of_measurement']);
        if (isset($validated['category'])) {
            $validated['category'] = trim($validated['category']) ?: null;
        }

        $initialStock = $validated['initial_stock'] ?? 0;
        unset($validated['initial_stock']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ingredients', 'public');
            $validated['image_url'] = Storage::url($path);
        }
        unset($validated['image']);

        try {
            $ingredient = Ingredient::create($validated);
        } catch (QueryException $e) {
            if ($this->isDuplicateNameViolation($e)) {
                return $this->duplicateNameResponse($request);
            }
            throw $e;
        }

        if ($initialStock > 0) {
            StockMovement::create([
                'ingredient_id' => $ingredient->ingredient_id,
                'user_id' => auth()->id(),
                'movement_type' => 'delivery',
                'qty_changed' => $initialStock,
                'recorded_at' => now(),
                'waste_reason' => 'Initial stock recorded on ingredient creation',
            ]);
        }

        $ingredient->current_stock = $initialStock;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'ingredient' => $ingredient,
                'message' => 'Ingredient added successfully.',
            ]);
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', 'Ingredient added successfully.');
    }

    public function edit(Ingredient $ingredient){
        return view('admin.inventory.edit', [
            'ingredient' => $ingredient,
        ]);
    }

    // Update an existing ingredient's details. Does not touch current stock,
    // that's only ever changed through recordMovement().
    public function update(Request $request, Ingredient $ingredient){
        $validator = Validator::make($request->all(), [
            'ingredient_name' => [...$this->textNoDigitsRule(), 'max:100', $this->duplicateNameRule($ingredient->ingredient_id)],
            'category' => ['nullable', 'string', 'max:50', 'regex:/^[^0-9]*$/u'],
            'unit_of_measurement' => [...$this->textNoDigitsRule(), 'max:20'],
            'unit_cost' => 'required|numeric|min:0',
            'restock_threshold' => 'required|numeric|min:0',
            'shelf_life_days' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();
        $validated['ingredient_name'] = trim($validated['ingredient_name']);
        $validated['unit_of_measurement'] = trim($validated['unit_of_measurement']);
        if (isset($validated['category'])) {
            $validated['category'] = trim($validated['category']) ?: null;
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ingredients', 'public');
            $validated['image_url'] = Storage::url($path);
        } elseif ($request->boolean('remove_image')) {
            if ($ingredient->image_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $ingredient->image_url));
            }
            $validated['image_url'] = null;
        }
        // Neither a new file nor an explicit removal -> image_url left out of
        // $validated entirely, so the existing image is preserved.
        unset($validated['image']);

        try {
            $ingredient->update($validated);
        } catch (QueryException $e) {
            if ($this->isDuplicateNameViolation($e)) {
                return $this->duplicateNameResponse($request);
            }
            throw $e;
        }

        $ingredient = Ingredient::withCurrentStock()->find($ingredient->ingredient_id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'ingredient' => $ingredient,
                'message' => 'Ingredient updated successfully.',
            ]);
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', 'Ingredient updated successfully.');
    }

    // Permanently remove an ingredient. The database's foreign keys deliberately
    // reject this (ON DELETE NO ACTION) if the ingredient has any recipe lines or
    // stock movement history, to protect the audit trail, so rather than
    // let that surface as a raw 500 error, it's caught here and turned into a clear,
    // specific message the admin can actually act on.
    public function destroy(Request $request, Ingredient $ingredient){
        try {
            $ingredient->delete();
        } catch (QueryException $e) {
            $message = "Can't delete \"{$ingredient->ingredient_name}\" — it has recipe or stock movement history tied to it.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 409);
            }

            return redirect()->route('admin.inventory.index')->with('error', $message);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ingredient deleted successfully.',
            ]);
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', 'Ingredient deleted successfully.');
    }

    // Record a stock-in, waste, or manual adjustment entry against an ingredient's
    // ledger. 'sale_deduction' is deliberately not offered here, a human admin
    // manually picking that type would defeat the point of it being automated;
    // see the MOVEMENT_TYPES docblock at the top of this class.
    public function recordMovement(Request $request, Ingredient $ingredient){
        $validated = $request->validate([
            'movement_type' => 'required|in:delivery,spoilage,manual_adjustment',
            'qty_changed' => 'required|numeric',
            'waste_reason' => 'nullable|string|max:100',
        ]);

        $validated['qty_changed'] = $this->normalizeQtySign($validated['movement_type'], (float) $validated['qty_changed']);

        $movement = StockMovement::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'user_id' => auth()->id(),
            'movement_type' => $validated['movement_type'],
            'qty_changed' => $validated['qty_changed'],
            'recorded_at' => now(),
            'waste_reason' => $validated['waste_reason'] ?? null,
        ])->load(['recordedBy', 'ingredient']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'movement' => $movement,
                'newTotal' => $ingredient->stockMovements()->sum('qty_changed'),
                'message' => 'Stock movement recorded.',
            ]);
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', 'Stock movement recorded.');
    }

    // Directly correct a previously recorded stock movement. This is a deliberate
    // exception to the append-only pattern used elsewhere (orders/payments remain
    // immutable), ingredient stock entries may be corrected by an admin to fix
    // data-entry mistakes, since a wrong quantity here otherwise permanently skews
    // current_stock (which is derived by summing this table) until a manual
    // adjustment is layered on top to compensate.
    public function updateMovement(Request $request, StockMovement $movement){
        $validated = $request->validate([
            'movement_type' => 'required|in:delivery,spoilage,manual_adjustment',
            'qty_changed' => 'required|numeric',
            'waste_reason' => 'nullable|string|max:100',
        ]);

        $validated['qty_changed'] = $this->normalizeQtySign($validated['movement_type'], (float) $validated['qty_changed']);

        $movement->update($validated);
        $movement->load(['recordedBy', 'ingredient']);

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

    // Permanently remove a stock movement entry. Same exception as updateMovement() above.
    public function destroyMovement(Request $request, StockMovement $movement){
        $ingredient = $movement->ingredient;
        $movement->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'newTotal' => $ingredient->stockMovements()->sum('qty_changed'),
                'message' => 'Stock movement deleted.',
            ]);
        }

        return redirect()->route('admin.inventory.ledger')->with('success', 'Stock movement deleted.');
    }

    // Determines the sign of a movement from its type rather than trusting the
    // sign the admin happened to type in. Delivery always adds, spoilage always
    // subtracts; manual_adjustment is the one type where the admin explicitly
    // controls direction (e.g. correcting a miscount either up or down).
    private function normalizeQtySign(string $type, float $qty): float{
        return match ($type) {
            'delivery' => abs($qty),
            'spoilage' => -abs($qty),
            default => $qty,
        };
    }

    // Per-ingredient movement history, still reachable directly, but no longer the
    // primary way to view ledger data (see ledgerIndex() below).
    public function history(Ingredient $ingredient){
        $movements = $ingredient->stockMovements()
            ->with('recordedBy')
            ->orderByDesc('recorded_at')
            ->paginate(8);

        return view('admin.inventory.history', [
            'ingredient' => $ingredient,
            'movements' => $movements,
        ]);
    }

    // Consolidated Stock Movement Ledger across every ingredient,
    // the primary, discoverable way to reach ledger data via a tab, rather than a
    // small action icon buried in a specific ingredient's row.
    public function ledgerIndex(Request $request){
        $query = StockMovement::with(['ingredient', 'recordedBy']);

        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->input('ingredient_id'));
        }

        if ($request->filled('type') && array_key_exists($request->input('type'), self::MOVEMENT_TYPES)) {
            $query->where('movement_type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('ingredient', fn ($iq) => $iq->where('ingredient_name', 'ILIKE', "%{$search}%"))
                    ->orWhereHas('recordedBy', fn ($uq) => $uq->where('name', 'ILIKE', "%{$search}%"));
            });
        }

        $movements = $query->orderByDesc('recorded_at')
            ->paginate(5)
            ->appends($request->query());

        $ingredients = Ingredient::orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name', 'unit_of_measurement']);

        return view('admin.inventory.ledger', [
            'movements' => $movements,
            'ingredients' => $ingredients,
            'selectedIngredientId' => $request->input('ingredient_id'),
            'selectedType' => $request->input('type'),
            'stats' => $this->ledgerStats(),
        ]);
    }

    // Total Movements (today) / Restocks (all-time completed deliveries) /
    // Waste Recorded (distinct ingredients with at least one waste entry) /
    // Net Stock (% change in total inventory value vs. 7 days ago).
    // "Net Stock" specifically doesn't correspond to anything in the current
    // SRS or schema as a named metric, it's defined here concretely as the
    // percentage change in total inventory value (current_stock × unit_cost,
    // summed across every ingredient) between right now and 7 days ago,
    // reconstructed from the append-only ledger rather than any stored
    // snapshot, since none exists for this.
    private function ledgerStats(): array{
        $totalMovementsToday = StockMovement::whereDate('recorded_at', today())->count();
        $restocksCompleted = StockMovement::where('movement_type', 'delivery')->count();
        $wasteItemCount = StockMovement::where('movement_type', 'spoilage')
            ->distinct('ingredient_id')
            ->count('ingredient_id');

        $valueNow = 0;
        $valueWeekAgo = 0;
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