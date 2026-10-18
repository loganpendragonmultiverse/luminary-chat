<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'user_id',
        'label',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
