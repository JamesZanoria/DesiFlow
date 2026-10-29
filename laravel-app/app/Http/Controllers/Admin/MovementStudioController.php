<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class MovementStudioController extends Controller
{
    /**
     * Show the creation studio for a stock movement.
     */
    public function create(Request $request)
    {
        $ingredients = Ingredient::all();
        $movementTypes = InventoryController::MOVEMENT_TYPES;
        $recordableTypes = InventoryController::RECORDABLE_MOVEMENT_TYPES;

        $draft = Session::get('movement_draft', []);

        return view('admin.inventory.movement.create', compact('ingredients', 'movementTypes', 'recordableTypes', 'draft'));
    }

    /**
     * Show the edit studio for an existing stock movement.
     */
    public function edit(StockMovement $movement)
    {
        // 1. Check if movement is locked (Voided)
        if ($movement->movement_type === 'void' || $movement->voided_at !== null) {
            return redirect()->route('admin.inventory.ledger')
                ->with('error', "This entry can't be edited — it's already voided, or is itself a voiding entry.");
        }

        // 2. Check if ingredient is currently expired
        $ingredient = $movement->ingredient;
        if ($ingredient && $ingredient->stockStatus() === 'expired') {
            return redirect()->route('admin.inventory.ledger')
                ->with('error', "This ingredient is currently expired. Its stock movement history is locked — write off the expired stock as waste first.");
        }

        $draft = Session::get("movement_draft_{$movement->movement_id}", []);

        return view('admin.inventory.movement.edit', compact('movement', 'draft'));
    }

    /**
     * Save a draft of a new movement.
     */
    public function saveDraft(Request $request)
    {
        $data = $request->validate([
            'currentStep' => 'required|integer',
            'inputs' => 'required|array',
        ]);

        Session::put('movement_draft', $data);

        return response()->json(['status' => 'draft saved']);
    }

    /**
     * Save a draft of an edited movement.
     */
    public function saveEditDraft(Request $request, StockMovement $movement)
    {
        $data = $request->validate([
            'currentStep' => 'required|integer',
            'inputs' => 'required|array',
        ]);

        Session::put("movement_draft_{$movement->movement_id}", $data);

        return response()->json(['status' => 'draft saved']);
    }

    /**
     * Finalize and store a new stock movement.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ingredient_id' => 'required|integer|exists:ingredients,ingredient_id',
            'movement_type' => 'required|in:' . implode(',', InventoryController::RECORDABLE_MOVEMENT_TYPES),
            'qty_changed' => 'required|numeric',
            'delivery_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $type = $validated['movement_type'];
        $qty = (float) $validated['qty_changed'];

        // 1. Business Rules: Delivery info only for Deliveries
        if ($type !== 'delivery' && (($validated['delivery_date'] ?? null) || ($validated['reference_number'] ?? null))) {
            return response()->json(['success' => false, 'message' => 'Delivery date and reference number are only permitted for Supplier Deliveries.'], 422);
        }

        // 2. Business Rules: Expiration required for inflow
        $expRequired = ($type === 'delivery' || $type === 'opening_balance' || ($type === 'manual_adjustment' && $qty > 0));
        if ($expRequired && empty($validated['expiration_date'])) {
            return response()->json(['success' => false, 'message' => 'Expiration date is required for this movement type.'], 422);
        }
        if (!$expRequired && !empty($validated['expiration_date'])) {
            $validated['expiration_date'] = null;
        }

        // 3. Business Rules: Unique Opening Balance
        if ($type === 'opening_balance' && \App\Models\StockMovement::where('ingredient_id', $validated['ingredient_id'])->where('movement_type', 'opening_balance')->exists()) {
            return response()->json(['success' => false, 'message' => 'An opening balance already exists for this ingredient.'], 422);
        }

        // 4. Business Rules: Lock if ingredient currently expired
        $ingredient = Ingredient::find($validated['ingredient_id']);
        if ($ingredient && $ingredient->stockStatus() === 'expired') {
            return response()->json(['success' => false, 'message' => 'Expired stock. Mark as Waste before delivery.'], 422);
        }

        // Normalize Qty Sign
        $qty_changed = match ($type) {
            'delivery', 'opening_balance' => abs($qty),
            'spoilage', 'internal_use' => -abs($qty),
            default => $qty,
        };

        $movement = StockMovement::create([
            'ingredient_id' => $validated['ingredient_id'],
            'user_id' => auth()->id(),
            'movement_type' => $type,
            'qty_changed' => $qty_changed,
            'recorded_at' => now(),
            'delivery_date' => $validated['delivery_date'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
        ]);

        app(\App\Services\NotificationService::class)->checkAndNotifyInventoryStatus($validated['ingredient_id']);
        Session::forget('movement_draft');

        return response()->json([
            'success' => true,
            'message' => 'Stock movement recorded successfully.',
            'redirect' => route('admin.inventory.ledger')
        ]);
    }

    /**
     * Finalize and update an existing stock movement.
     */
    public function update(Request $request, StockMovement $movement)
    {
        $validator = Validator::make($request->all(), [
            'qty_changed' => 'required|numeric',
            'delivery_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
            'edit_reason' => 'required|string|max:150',
            'recorded_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Lock checks (Redundant but safe as Edit view already checks)
        if ($movement->movement_type === 'void' || $movement->voided_at !== null) {
            return response()->json(['success' => false, 'message' => 'Movement is voided and cannot be edited.'], 409);
        }

        $qty_changed = match ($movement->movement_type) {
            'delivery', 'opening_balance' => abs((float)$validated['qty_changed']),
            'spoilage', 'internal_use' => -abs((float)$validated['qty_changed']),
            default => (float)$validated['qty_changed'],
        };

        $movement->update([
            'qty_changed' => $qty_changed,
            'edit_reason' => $validated['edit_reason'],
            'recorded_at' => $validated['recorded_at'] ?? $movement->recorded_at,
            'delivery_date' => $validated['delivery_date'] ?? $movement->delivery_date,
            'expiration_date' => $validated['expiration_date'] ?? $movement->expiration_date,
            'reference_number' => $validated['reference_number'] ?? $movement->reference_number,
            'notes' => $validated['notes'] ?? $movement->notes,
        ]);

        app(\App\Services\NotificationService::class)->checkAndNotifyLowStock($movement->ingredient_id);
        Session::forget("movement_draft_{$movement->movement_id}");

        return response()->json([
            'success' => true,
            'message' => 'Stock movement updated successfully.',
            'redirect' => route('admin.inventory.ledger')
        ]);
    }
}
