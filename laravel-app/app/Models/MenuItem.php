<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model{
    // The table associated with the model.
    protected $table = 'menu_items';

    // The primary key associated with the table.
    protected $primaryKey = 'menu_item_id';

    // Indicates if the model should be timestamped.
    public $timestamps = false;

    // The attrbutes that are mass assignable.
    protected $fillable = [
        'category',
        'item_name',
        'price',
        'image_url',
        'is_available',
    ];

    // The attributes that should be cast.
    protected function casts(): array{
        return [
            'is_available' => 'boolean',
        ];
    }

    // The recipe lines (ingredients + quantities) that make up this menu item.
    public function recipeIngredients(){
        return $this->hasMany(RecipeIngredient::class, 'menu_item_id', 'menu_item_id');
    }

    /**
     * Determines if the menu item is available based on current ingredient stock.
     *
     * Returns:
     * - 'active' if all required active ingredients have sufficient usable stock.
     * - 'unavailable' if any active ingredient is insufficient/expired, or if no recipe exists.
     */
    public function inventoryAvailable(): string {
        $details = $this->getAvailabilityDetails();
        if (!$details['is_available']) {
            return 'unavailable';
        }

        // If it's available, check if any of its ingredients are in a warning state (low stock or expiring soon)
        $activeIngredients = $this->recipeIngredients()
            ->where('is_active', true)
            ->with(['ingredient' => function($q) {
                $q->withCurrentStock();
            }])
            ->get();

        foreach ($activeIngredients as $recipeLine) {
            $ing = $recipeLine->ingredient;
            if ($ing) {
                $status = $ing->stockStatus();
                if ($status === 'low_stock' || $status === 'expiring_soon') {
                    return 'warning';
                }
            }
        }

        return 'available';
    }

    /**
     * Provides a detailed breakdown of why a menu item is available or unavailable.
     *
     * Returns:
     * [
     *   'is_available' => bool,
     *   'reason' => string|null,
     *   'failing_ingredient' => array|null // ['name' => string, 'required' => float, 'available' => float, 'status' => string]
     * ]
     */
    public function getAvailabilityDetails(): array {
        $activeIngredients = $this->recipeIngredients()
            ->where('is_active', true)
            ->with(['ingredient' => function($q) {
                $q->withCurrentStock();
            }])
            ->get();

        if ($activeIngredients->isEmpty()) {
            return [
                'is_available' => false,
                'reason' => 'No recipe defined.',
                'failing_ingredients' => []
            ];
        }

        $failing = [];
        foreach ($activeIngredients as $recipeLine) {
            $ing = $recipeLine->ingredient;
            if (!$ing) {
                $failing[] = [
                    'name' => 'Missing Ingredient',
                    'reason' => 'Recipe contains a missing ingredient.',
                    'required' => 0,
                    'available' => 0,
                    'status' => 'missing'
                ];
                continue;
            }

            $usableStock = $ing->usableStock();
            $required = $recipeLine->qty_consumed;

            if ($usableStock < $required) {
                $status = $ing->stockStatus();
                $failing[] = [
                    'name' => $ing->ingredient_name,
                    'reason' => ($status === 'expired') ? 'Ingredient has expired.' : 'Insufficient usable stock.',
                    'required' => $required,
                    'available' => $usableStock,
                    'unit' => $ing->unit_of_measurement,
                    'status' => $status
                ];
            }
        }

        if (!empty($failing)) {
            return [
                'is_available' => false,
                'reason' => 'One or more ingredients are unavailable.',
                'failing_ingredients' => $failing
            ];
        }

        return [
            'is_available' => true,
            'reason' => 'All ingredients available.',
            'failing_ingredients' => []
        ];
    }

    /**
     * Updates the is_available column in the database based on current ingredient stock.
     */
    public function syncAvailabilityStatus(): bool {
        $details = $this->getAvailabilityDetails();
        return $this->update(['is_available' => $details['is_available']]);
    }
}