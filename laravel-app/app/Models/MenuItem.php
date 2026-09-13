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
}