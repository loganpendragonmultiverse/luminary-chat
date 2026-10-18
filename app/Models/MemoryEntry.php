<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'character_id',
        'chat_id',
        'title',
        'content',
        'category',
        'source',
        'confidence',
        'importance',
        'status',
        'is_pinned',
        'is_locked',
        'superseded_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'confidence' => 'float',
            'importance' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function revisions()
    {
        return $this->hasMany(MemoryRevision::class)->latest('id');
    }
}
