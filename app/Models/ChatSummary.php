<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'summary',
        'manual_notes',
        'last_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_generated_at' => 'datetime',
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}
