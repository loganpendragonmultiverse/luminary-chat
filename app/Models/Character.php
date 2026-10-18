<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'template_key',
        'is_system',
        'is_default',
        'short_description',
        'personality',
        'scenario',
        'first_message',
        'long_bio',
        'writing_style',
        'tone',
        'companion_form',
        'randomize_initial_chats',
        'initial_chat_style',
        'tags',
        'lore_notes',
        'persona_documents',
        'default_model',
        'temperature',
        'max_tokens',
        'top_p',
        'is_active',
        'is_favorite',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'top_p' => 'float',
            'max_tokens' => 'integer',
            'is_active' => 'boolean',
            'is_favorite' => 'boolean',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
            'randomize_initial_chats' => 'boolean',
            'persona_documents' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class)->latest('updated_at');
    }

    public function memoryEntries(): HasMany
    {
        return $this->hasMany(MemoryEntry::class)->latest('is_pinned')->latest('updated_at');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class)->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CharacterVersion::class)->latest('id');
    }

    public function personaRevisions(): HasMany
    {
        return $this->hasMany(CharacterPersonaRevision::class)->latest('id');
    }

    public function snapshotPayload(): array
    {
        return $this->only([
            'name',
            'short_description',
            'personality',
            'scenario',
            'first_message',
            'long_bio',
            'writing_style',
            'tone',
            'companion_form',
            'randomize_initial_chats',
            'initial_chat_style',
            'tags',
            'lore_notes',
            'persona_documents',
            'default_model',
            'temperature',
            'max_tokens',
            'top_p',
            'is_active',
            'is_favorite',
        ]);
    }

    public function duplicateForUser(int $userId): self
    {
        return static::create([
            ...$this->only([
                'short_description',
                'personality',
                'scenario',
                'first_message',
                'long_bio',
                'writing_style',
                'tone',
                'companion_form',
                'randomize_initial_chats',
                'initial_chat_style',
                'tags',
                'lore_notes',
                'default_model',
                'temperature',
                'max_tokens',
                'top_p',
                'is_active',
                'is_favorite',
            ]),
            'user_id' => $userId,
            'name' => $this->name.' Copy',
            'slug' => $this->slug.'-copy-'.strtolower(str()->random(4)),
        ]);
    }
}
