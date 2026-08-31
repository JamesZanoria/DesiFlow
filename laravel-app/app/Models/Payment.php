<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model{
    // The table associated with the model.
    protected $table = 'payments';

    // The primary key associated with the table.
    protected $primaryKey = 'payment_id';

    // Indicates if the model should be timestamped.
    public $timestamps = false;

    // The attrbutes that are mass assignable.
    protected $fillable = [
        'order_id',
        'payment_method',
        'gcash_ref_num',
        'amount_tethered',
        'change_due',
    ];

    // The attributes that should be cast.
    protected function casts(): array{
        return [
            'amount_tethered' => 'decimal:2',
            'change_due' => 'decimal:2',
        ];
    }

    // The order this payment is for.
    public function order(){
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}