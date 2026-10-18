<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'character_id',
        'creation_token',
        'room_id',
        'chat_folder_id',
        'title',
        'model',
        'temperature',
        'max_tokens',
        'top_p',
        'pinned_memory',
        'is_pinned',
        'is_archived',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'top_p' => 'float',
            'max_tokens' => 'integer',
            'is_pinned' => 'boolean',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
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

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ChatFolder::class, 'chat_folder_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function summary(): HasOne
    {
        return $this->hasOne(ChatSummary::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ChatTurnLog::class)->latest('id');
    }

    public function memoryEntries(): HasMany
    {
        return $this->hasMany(MemoryEntry::class)->latest('is_pinned')->latest('updated_at');
    }
}
