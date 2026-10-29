<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssistantConversation extends Model
{
    protected $fillable = ['user_id', 'title'];

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
