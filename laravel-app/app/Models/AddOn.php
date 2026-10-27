<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddOn extends Model
{
    protected $table = 'add_ons';

    protected $primaryKey = 'add_on_id';

    public $timestamps = false;

    protected $fillable = [
        'add_on_name',
        'price',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function menuItems()
    {
        return $this->belongsToMany(
            MenuItem::class,
            'menu_item_add_ons',
            'add_on_id',
            'menu_item_id',
            'add_on_id',
            'menu_item_id'
        );
    }
}
