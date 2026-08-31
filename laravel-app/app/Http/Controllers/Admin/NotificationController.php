<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;

class NotificationController extends Controller{
    // Returns all current system alerts as JSON, for the notification bell dropdown
    // in the shared layout. Currently only surfaces low-stock ingredients — structured
    // so additional alert types can be appended here later without changing how the
    // bell consumes this endpoint.
    //
    // NOTE: these are computed live from current data state (is this ingredient low right now?), 
    // not stored, discrete notification records. There's no "notifications"
    // table, so there's no true creation timestamp for "when did this start." The
    // timestamp shown is the most recent stock movement recorded for that ingredient,
    // labeled as "Updated X ago" rather than falsely implying "became low X ago."
    public function index(){
        $lowStock = Ingredient::withCurrentStock()
            ->get()
            ->filter(fn ($ingredient) => $ingredient->isLowStock())
            ->map(function ($ingredient) {
                $lastMovement = $ingredient->stockMovements()->orderByDesc('recorded_at')->first();

                return [
                    'key' => "low_stock_{$ingredient->ingredient_id}",
                    'type' => 'low_stock',
                    'title' => "Low Stock: {$ingredient->ingredient_name}",
                    'message' => number_format($ingredient->current_stock ?? 0, 2) . ' ' . $ingredient->unit_of_measurement . ' left',
                    'timestamp' => optional($lastMovement)->recorded_at,
                    'url' => route('admin.inventory.history', ['ingredient' => $ingredient]),
                ];
            })
            ->values();

        return response()->json([
            'count' => $lowStock->count(),
            'alerts' => $lowStock,
        ]);
    }
}