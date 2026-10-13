<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model{
    // The table associated with the model.
    protected $table = 'recipe_ingredients';

    // The primary key associated with the table.
    protected $primaryKey = 'recipe_ingredient_id';

    // Indicates if the model should be timestamped.
    public $timestamps = false;

    // The attributes that are mass assignable.
    protected $fillable = [
        'menu_item_id',
        'ingredient_id',
        'qty_consumed',
        'is_active',
    ];

    // The attributes that should be cast.
    protected function casts(): array{
        return [
            'qty_consumed' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // The menu item this recipe line belongs to.
    public function menuItem(){
        return $this->belongsTo(MenuItem::class, 'menu_item_id', 'menu_item_id');
    }

    // The ingredient this recipe line uses.
    public function ingredient(){
        return $this->belongsTo(Ingredient::class, 'ingredient_id', 'ingredient_id');
    }

    // Scope to only the currently active recipe lines, use this wherever a recipe
    // is read for costing or backflushing, so retired/edited recipe lines that were
    // soft-disabled via is_active don't get counted.
    public function scopeActive($query){
        return $query->where('is_active', true);
    }
}