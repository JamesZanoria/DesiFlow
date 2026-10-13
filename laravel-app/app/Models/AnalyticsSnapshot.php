<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model{
    // The table associated with the model.
    protected $table = 'analytics_snapshots';

    // The primary key associated with the table.
    protected $primaryKey = 'snapshot_id';

    // Indicates if the model should be timestamped.
    public $timestamps = false;

    // The attrbutes that are mass assignable.
    protected $fillable = [
        'snapshot_type',
        'reference_id',
        'result_data',
        'generated_at',
    ];

    // The attributes that should be cast.
    protected function casts(): array{
        return [
            'result_data' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    // Scope a query to only include the latest snapshot of a given type.
    public function scopeLatestOfType($query, string $type){
        return $query->where('snapshot_type', $type)
            ->orderByDesc('generated_at')
            ->limit(1);
    }
}