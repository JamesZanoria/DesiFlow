<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

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
}
