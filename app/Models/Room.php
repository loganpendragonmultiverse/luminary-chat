<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'user_id', 'character_id', 'slug', 'name', 'description', 'contents',
        'expectations', 'is_default', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'sort_order' => 'integer'];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
