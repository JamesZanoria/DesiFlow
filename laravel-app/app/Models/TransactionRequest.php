<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionRequest extends Model
{
    protected $table = 'transaction_requests';

    protected $primaryKey = 'transaction_request_id';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'requested_by',
        'reviewed_by',
        'request_type',
        'reason',
        'status',
        'decision_reason',
        'requested_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}
