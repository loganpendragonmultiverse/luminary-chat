<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'date_of_birth',
        'terms_accepted_at',
        'terms_version',
        'password',
        'is_admin',
        'is_active',
        'venice_api_key',
        'openrouter_api_key',
        'ai_model_catalog',
        'ai_model_preferences',
        'ai_models_synced_at',
        'avatar_url',
        'memory_enabled',
        'memory_auto_extract_enabled',
        'context_depth',
        'randomize_initial_chats',
        'initial_chat_style',
        'privacy_hide_email',
        'privacy_allow_diagnostics',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'venice_api_key',
        'openrouter_api_key',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'terms_accepted_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'venice_api_key' => 'encrypted',
            'openrouter_api_key' => 'encrypted',
            'ai_model_catalog' => 'array',
            'ai_model_preferences' => 'array',
            'ai_models_synced_at' => 'datetime',
            'memory_enabled' => 'boolean',
            'memory_auto_extract_enabled' => 'boolean',
            'context_depth' => 'string',
            'randomize_initial_chats' => 'boolean',
            'initial_chat_style' => 'string',
            'privacy_hide_email' => 'boolean',
            'privacy_allow_diagnostics' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function chatFolders(): HasMany
    {
        return $this->hasMany(ChatFolder::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
