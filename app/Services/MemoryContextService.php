<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\MemoryEntry;
use Illuminate\Support\Collection;

class MemoryContextService
{
    public function forChat(Chat $chat, string $query = '', int $limit = 24): Collection
    {
        $terms = collect(preg_split('/[^a-z0-9\']+/i', strtolower($query)) ?: [])
            ->filter(fn ($term) => strlen($term) > 3)
            ->unique()
            ->take(10);

        $ranked = MemoryEntry::query()
            ->where('user_id', $chat->user_id)
            ->where('character_id', $chat->character_id)
            ->where('status', '!=', 'deleted')
            ->whereNull('superseded_by_id')
            ->where(function ($builder) use ($chat) {
                $builder->whereNull('chat_id')->orWhere('chat_id', $chat->id);
            })
            ->get()
            ->map(function (MemoryEntry $memory) use ($terms, $chat) {
                $haystack = strtolower($memory->title.' '.$memory->content.' '.$memory->category);
                $relevance = $terms->sum(fn ($term) => substr_count($haystack, $term));
                $memory->context_score = ($memory->is_pinned ? 5 : 0)
                    + ($memory->chat_id === $chat->id ? 1.5 : 0)
                    + ($memory->importance * 2)
                    + $memory->confidence
                    + ($relevance * 1.25);

                return $memory;
            })
            ->sortByDesc('context_score')
            ->take(max(1, min($limit, 40)))
            ->values();

        $selected = collect();
        $characterBudget = 6000;
        $used = 0;
        foreach ($ranked as $memory) {
            $size = mb_strlen($memory->title.' '.$memory->content.' '.$memory->category);
            if (! $memory->is_pinned && $used + $size > $characterBudget) {
                continue;
            }
            $selected->push($memory);
            $used += $size;
        }

        return $selected->values();
    }
}
