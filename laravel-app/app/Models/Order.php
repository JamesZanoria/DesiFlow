<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model{
    // The table associated with the model.
    protected $table = 'orders';

    // The primary key associated with the table.
    protected $primaryKey = 'order_id';

    // Indicates if the model should be timestamped.
    public $timestamps = false;

    // The attrbutes that are mass assignable.
    protected $fillable = [
        'cashier_id',
        'table_number',
        'order_type',
        'status',
        'total_amount',
        'created_at',
    ];

    // The attributes that should be cast.
    protected function casts(): array{
        return [
            'total_amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    // The cashier who processed this order.
    public function cashier(){
        return $this->belongsTo(User::class, 'cashier_id', 'user_id');
    }

    // The line items belonging to this order.
    public function orderItems(){
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    // The payment record for this order.
    public function payment(){
        return $this->hasOne(Payment::class, 'order_id', 'order_id');
    }
}