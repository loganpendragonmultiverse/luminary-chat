<?php
namespace App\Services;

use App\Models\Chat;
use App\Models\Message;
use App\Models\MemoryEntry;

class CrossModeContextService
{
    public function forChat(Chat $chat, string $query = ''): string
    {
        if (! $chat->user?->memory_enabled) return '';
        // Only this account and this character; never merge companion identities.
        $otherChats = Chat::query()->where('user_id', $chat->user_id)
            ->where('character_id', $chat->character_id)->whereKeyNot($chat->id)
            ->with('summary')->latest('updated_at')->limit(12)->get();
        $terms = collect(preg_split('/[^a-z0-9]+/i', strtolower($query)) ?: [])
            ->filter(fn ($term) => strlen($term) > 3)->unique()->take(8);
        $ranked = $otherChats->sortByDesc(function ($other) use ($terms) {
            $text = strtolower($other->title.' '.$other->pinned_memory.' '.$other->summary?->summary.' '.$other->summary?->manual_notes);
            return $terms->sum(fn ($term) => substr_count($text, $term));
        })->take(4);
        $savedMemories = MemoryEntry::query()->where('user_id', $chat->user_id)->where('character_id', $chat->character_id)
            ->where('status', '!=', 'deleted')->whereNull('superseded_by_id')->whereNotNull('chat_id')->where('chat_id', '!=', $chat->id)
            ->latest('is_pinned')->latest('importance')->latest('updated_at')->limit(24)->get()
            ->map(fn ($entry) => '['.$entry->category.'] '.$entry->title.': '.$entry->content)->implode("\n");
        $parts = $savedMemories !== '' ? ['Saved memories from this character’s other conversations: '.mb_substr($savedMemories, 0, 2500)] : [];
        foreach ($ranked as $other) {
            $history = Message::query()->where('chat_id', $other->id)->whereIn('role', ['user', 'assistant'])
                ->latest('id')->limit(6)->get()->reverse()
                ->map(fn ($message) => strtoupper($message->role).': '.mb_substr($message->content, 0, 700))->implode("\n");
            $parts[] = 'Saved conversation: '.mb_substr($other->title, 0, 120)."\n".
                mb_substr(implode("\n", array_filter([$other->pinned_memory, $other->summary?->summary, $other->summary?->manual_notes, $history])), 0, 2200);
        }
        return mb_substr(implode("\n\n", $parts), 0, 7000);
    }
}
