<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model{
    // The table associated with the model.
    protected $table = 'ingredients';

    // The primary key associated with the table.
    protected $primaryKey = 'ingredient_id';

    // Indicates if the model should be timestamped.
    public $timestamps = false;

    // The attrbutes that are mass assignable.
    protected $fillable = [
        'ingredient_name',
        'category',
        'unit_of_measurement',
        'unit_cost',
        'restock_threshold',
        'shelf_life_days',
        'image_url',
    ];

    // The attributes that should be cast.
    protected function casts(): array{
        return [
            'unit_cost' => 'decimal:2',
            'restock_threshold' => 'decimal:2',
        ];
    }

    // The stock ledger entries for this ingredient.
    public function stockMovements(){
        return $this->hasMany(StockMovement::class, 'ingredient_id', 'ingredient_id');
    }

    // The recipe lines that use this ingredient.
    public function recipeIngredients(){
        return $this->hasMany(RecipeIngredient::class, 'ingredient_id', 'ingredient_id');
    }

    // Scope: attach each ingredient's current stock as a single aggregated query
    // (SUM of qty_changed across its ledger) instead of running one SUM query per
    // ingredient in a loop. Use this anywhere current_stock is needed on a list —
    // Ingredient::withCurrentStock()->get(), and read it via $ingredient->current_stock.
    public function scopeWithCurrentStock($query){
        return $query->withSum('stockMovements as current_stock', 'qty_changed');
    }

    // True when current stock is at or below the restock threshold. Relies on
    // current_stock being present, i.e. the model was loaded via withCurrentStock().
    // Kept alongside stockStatus() below (rather than replaced by it) since
    // several places, the notification bell, the Smart Assistant — only care
    // about the binary "needs attention?" question, not which of the two
    // low/out-of-stock tiers it falls into.
    public function isLowStock(): bool{
        return ($this->current_stock ?? 0) <= $this->restock_threshold;
    }

    // Three-tier status ('active' | 'low_stock' | 'out_of_stock') for the
    // redesigned Inventory table's status badge. Single source of truth so
    // this classification can't drift between the table badge, the stat
    // cards (Low Stock Alerts / Out of Stock counts), and the "Critical
    // First" sort chip, all three read from this one method rather than
    // re-implementing the same three-way comparison independently.
    public function stockStatus(): string{
        $stock = $this->current_stock ?? 0;

        if ($stock <= 0) {
            return 'out_of_stock';
        }

        if ($stock <= $this->restock_threshold) {
            return 'low_stock';
        }

        return 'active';
    }
}