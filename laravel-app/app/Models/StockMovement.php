<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model{
    // The table associated with the model.
    protected $table = 'stock_movements';

    // The primary key associated with the table.
    protected $primaryKey = 'movement_id';

    // Indicates if the model should be timestamped.
    public $timestamps = false;

    // The attrbutes that are mass assignable.
    protected $fillable = [
        'ingredient_id',
        'user_id',
        'movement_type',
        'qty_changed',
        'recorded_at',
        'notes',
        'reference_number',
        'voids_movement_id',
    ];

    // The attributes that should be cast.
    protected function casts(): array{
        return [
            'qty_changed' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    // The ingredient this movement affects.
    public function ingredient(){
        return $this->belongsTo(Ingredient::class, 'ingredient_id', 'ingredient_id');
    }

    // The staff member who recorded this movement.
    public function recordedBy(){
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}