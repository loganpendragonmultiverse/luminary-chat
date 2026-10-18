<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'model',
        'token_count',
        'meta',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'token_count' => 'integer',
            'edited_at' => 'datetime',
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}
