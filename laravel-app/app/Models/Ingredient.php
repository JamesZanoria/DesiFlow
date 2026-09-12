<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model{
    // How many days before the computed expiry date an ingredient is flagged
    // "Expiring Soon" rather than just "Active". Centralized here since it's
    // read by both stockStatus() (PHP) and InventoryController's raw-SQL
    // status filter (kept in sync manually, see the comment there).
    public const EXPIRY_WARNING_DAYS = 3;

    // Every value stockStatus() can return, in priority order (a row can only
    // ever be in one at a time). Used to whitelist the ?status= filter and to
    // build the filter dropdown, so the two can never drift out of sync.
    public const STATUSES = ['out_of_stock', 'expired', 'expiring_soon', 'low_stock', 'active'];

    // Curated starting suggestions for the Add/Edit Ingredient form's Category
    // and Unit of Measurement fields — offered as <datalist> autocomplete
    // options (same combo-box pattern as Menu Management's Category field:
    // a real text input, not a <select>), not an enforced closed list. An
    // admin can still type something not in either list. InventoryController
    // merges these with whatever distinct values already exist in the table,
    // so a category/unit introduced by typing a new one shows up as a
    // suggestion for the next entry too, exactly like Menu Management's does.
    public const CATEGORIES = ['Dairy', 'Meat', 'Produce', 'Dry Goods', 'Packaging'];
    public const UNITS = ['g' ,'kg', 'mL', 'L', 'pcs', 'packs', 'boxes'];

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

    // Delivery-only subset of the ledger, used to determine when this
    // ingredient's current stock was last replenished, the anchor point
    // expiryDate() counts shelf_life_days forward from. A defined relation
    // (rather than an inline ->where() at the call site) so withLastDeliveryAt()
    // below can constrain a withMax() aggregate to it automatically.
    public function deliveryMovements(){
        return $this->stockMovements()->where('movement_type', 'delivery');
    }

    // The recipe lines that use this ingredient.
    public function recipeIngredients(){
        return $this->hasMany(RecipeIngredient::class, 'ingredient_id', 'ingredient_id');
    }

    // Scope: attach each ingredient's current stock as a single aggregated query
    // (SUM of qty_changed across its ledger) instead of running one SUM query per
    // ingredient in a loop. Use this anywhere current_stock is needed on a list,
    // Ingredient::withCurrentStock()->get(), and read it via $ingredient->current_stock.
    public function scopeWithCurrentStock($query){
        return $query->withSum('stockMovements as current_stock', 'qty_changed');
    }

    // Companion to withCurrentStock(): attaches the timestamp of this
    // ingredient's most recent delivery as last_delivery_at, the same
    // single-aggregated-query-instead-of-N-queries approach. Needed by
    // expiryDate()/stockStatus() below; call both scopes together wherever an
    // ingredient's expiry status needs to be accurate; Ingredient::withCurrentStock()->withLastDeliveryAt()->get().
    public function scopeWithLastDeliveryAt($query){
        return $query->withMax('deliveryMovements as last_delivery_at', 'recorded_at');
    }

    // True when current stock is at or below the restock threshold. Relies on
    // current_stock being present, i.e. the model was loaded via withCurrentStock().
    // Kept alongside stockStatus() below (rather than replaced by it) since
    // several places, the notification bell, the Smart Assistant, only care
    // about the binary "needs attention?" question, not which of the two
    // low/out-of-stock tiers it falls into.
    public function isLowStock(): bool{
        return ($this->current_stock ?? 0) <= $this->restock_threshold;
    }

    // The point in time this ingredient's current stock is considered
    // expired: shelf_life_days counted forward from its last delivery.
    // Returns null when either piece is missing, no shelf life set (shouldn't
    // happen now that it's required on new/edited ingredients, but existing
    // rows from before that change may still have one), or it's never been
    // delivered (last_delivery_at requires withLastDeliveryAt() to have been
    // applied to the query this model came from).
    public function expiryDate(): ?\Illuminate\Support\Carbon{
        if (! $this->shelf_life_days || ! $this->last_delivery_at) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($this->last_delivery_at)->addDays($this->shelf_life_days);
    }

    // Three-tier status ('active' | 'low_stock' | 'out_of_stock') for the
    // redesigned Inventory table's status badge. Single source of truth so
    // this classification can't drift between the table badge, the stat
    // cards (Low Stock Alerts / Out of Stock counts), and the "Critical
    // First" sort chip, all three read from this one method rather than
    // re-implementing the same three-way comparison independently.
    //
    // Extended with two expiry-aware tiers ('expired', 'expiring_soon'),
    // checked ahead of low_stock/active: an ingredient sitting on shelves
    // past its shelf life is a more urgent problem than merely running low,
    // regardless of how much of it is left. out_of_stock still wins over
    // everything, there's nothing left to expire.
    //
    // InventoryController's status filter re-implements this same priority
    // order as a raw SQL CASE expression (a WHERE clause can't call a PHP
    // method), keep the two in sync if this logic ever changes.
    public function stockStatus(): string{
        $stock = $this->current_stock ?? 0;

        if ($stock <= 0) {
            return 'out_of_stock';
        }

        $expiry = $this->expiryDate();
        if ($expiry) {
            if (now()->greaterThanOrEqualTo($expiry)) {
                return 'expired';
            }
            if (now()->greaterThanOrEqualTo($expiry->copy()->subDays(self::EXPIRY_WARNING_DAYS))) {
                return 'expiring_soon';
            }
        }

        if ($stock <= $this->restock_threshold) {
            return 'low_stock';
        }

        return 'active';
    }
}