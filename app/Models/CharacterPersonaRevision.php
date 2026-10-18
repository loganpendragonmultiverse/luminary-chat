<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterPersonaRevision extends Model
{
    protected $fillable = [
        'user_id', 'character_id', 'file_name', 'version', 'prior_content', 'content', 'change_note',
    ];

    protected function casts(): array
    {
        return ['version' => 'integer'];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
