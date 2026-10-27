<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemAddOn extends Model
{
    protected $table = 'order_item_add_ons';

    protected $primaryKey = 'order_item_add_on_id';

    public $timestamps = false;

    protected $fillable = [
        'order_item_id',
        'add_on_id',
        'add_on_name_snapshot',
        'unit_price',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'order_item_id');
    }
}
