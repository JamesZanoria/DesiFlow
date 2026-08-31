<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model{
    // The table associated with the model.
    protected $table = 'orders_items';

    // The primary key associated with the table.
    protected $primaryKey = 'order_item_id';

    // Indicates if the model should be timestamped.
    public $timestamps = false;

    // The attrbutes that are mass assignable.
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'quantity',
        'total',
    ];

    // The attributes that should be cast.
    protected function casts(): array{
        return [
            'total' => 'decimal:2',
        ];
    }

    // The order this line item belongs to.
    public function order(){
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    // The menu item ordered.
    public function menuItem(){
        return $this->belongsTo(MenuItem::class, 'menu_item_id', 'menu_item_id');
    }
}