<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryRevision extends Model
{
    protected $fillable = ['memory_entry_id', 'prior_content', 'prior_status', 'reason'];

    public function memoryEntry(): BelongsTo
    {
        return $this->belongsTo(MemoryEntry::class);
    }
}
