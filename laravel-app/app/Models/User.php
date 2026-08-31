<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticable{
    use Notifiable;

    // The table associated with the model.
    protected $table = 'users';

    // The primary key associated with the table.
    protected $primaryKey = 'user_id';

    // Indicates if the model should be timestamped.
    public $timestamps = false;

    // The attributes that are mass assignable.
    protected $fillable = [
        'role',
        'name',
        'email',
        'password',
    ];

    // The attributes that should be hidden for serialization.
    protected $hidden = [
        'password',
    ];

    // Get the attributes that should be cast.
    protected function casts(): array{
        return [
            'password' => 'hashed',
        ];
    }
}