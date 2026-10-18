<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Chat;
use App\Models\MemoryEntry;
use RuntimeException;

class MemoryExtractionService
{
    public function __construct(
        protected VeniceChatService $venice,
        protected AppSettingsService $settings,
    ) {
    }

    public function extract(Chat $chat, Character $character): array
    {
        if (! $chat->user?->memory_auto_extract_enabled) {
            return [];
        }

        $allowCrossChatMemory = (bool) $chat->user?->memory_enabled;
        $render = fn ($value) => app(DisplayNameTemplateService::class)->resolve((string) $value, $chat->user);

        $messages = $chat->messages()
            ->reorder()
            ->latest('id')
            ->take(8)
            ->get()
            ->reverse()
            ->map(fn ($message) => strtoupper($message->role).': '.$message->content)
            ->implode("\n\n");

        if ($messages === '') {
            return [];
        }

        $existingMemories = MemoryEntry::query()
            ->where('user_id', $chat->user_id)
            ->where('character_id', $character->id)
            ->where('status', '!=', 'deleted')
            ->whereNull('superseded_by_id')
            ->latest('is_pinned')
            ->latest('importance')
            ->take(30)
            ->get()
            ->map(fn (MemoryEntry $memory) => '- '.$memory->title.': '.$memory->content)
            ->implode("\n");

        $payload = [
            'model' => $chat->model,
            'temperature' => 0.3,
            'max_tokens' => 350,
            'top_p' => 0.9,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'Extract stable memory candidates from this private character chat.',
                        'Return valid JSON only.',
                        'Schema: {"entries":[{"title":"...","content":"...","category":"relationship|preference|goal|lore|constraint|plan","scope":"chat'.($allowCrossChatMemory ? '|character' : '').'","confidence":0.0,"importance":0.0,"durability":0.0,"is_pinned":false}]}',
                        $allowCrossChatMemory ? 'Use character scope only for facts that should persist across all future conversations.' : 'Use chat scope only. Cross-chat memory is disabled for this user.',
                        'Let the character identity and personality below guide what is meaningful enough to remember, but never invent a fact or treat personality as evidence.',
                        'Save only explicit durable facts, lasting preferences, boundaries, commitments, relationship milestones, ongoing goals, and stable story canon that will matter in a later conversation.',
                        'Do not save secrets, sensitive traits, or intimate details unless the user clearly asked for continuity or the detail is necessary to honor an explicit boundary or commitment.',
                        'Never save a current pose, clothing, room activity, sexual scene detail, passing mood, greeting, ordinary action, or temporary conversational state. A scene is not a memory.',
                        'Do not restate the character profile, relationship mode, Soul, room description, or an existing memory. Those are already supplied separately.',
                        'Use durability to score whether the fact should still matter weeks later. Return nothing unless durability is at least 0.85, importance at least 0.75, and confidence at least 0.8.',
                        'Return at most one genuinely new entry. Prefer an empty entries array over a weak memory.',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => implode("\n\n", [
                        'Character: '.$render($character->name),
                        'Personality and memory lens: '.$render($character->personality),
                        'Relationship role: '.($character->companion_form ?: 'unspecified'),
                        'Long-term identity: '.$render($character->long_bio),
                        'Scenario: '.$render($character->scenario),
                        'Lore and continuity priorities: '.$render($character->lore_notes),
                        'Character tags: '.$render($character->tags),
                        'Existing durable memories (do not duplicate or paraphrase these):',
                        $existingMemories !== '' ? $existingMemories : '(none)',
                        'Recent conversation:',
                        $messages,
                    ]),
                ],
            ],
        ];

        $response = $this->venice->rawCompletion($payload, $chat->user);
        $content = trim((string) data_get($response, 'choices.0.message.content', ''));

        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded) || ! isset($decoded['entries']) || ! is_array($decoded['entries'])) {
            throw new RuntimeException('Memory extraction returned invalid JSON.');
        }

        return collect($decoded['entries'])
            ->map(function ($entry) use ($allowCrossChatMemory) {
                return [
                    'title' => trim((string) data_get($entry, 'title', 'Memory')),
                    'content' => trim((string) data_get($entry, 'content', '')),
                    'category' => trim((string) data_get($entry, 'category', 'general')),
                    'scope' => $allowCrossChatMemory && data_get($entry, 'scope', 'chat') === 'character' ? 'character' : 'chat',
                    'is_pinned' => (bool) data_get($entry, 'is_pinned', false),
                    'confidence' => max(0, min(1, (float) data_get($entry, 'confidence', 0.8))),
                    'importance' => max(0, min(1, (float) data_get($entry, 'importance', 0.6))),
                    'durability' => max(0, min(1, (float) data_get($entry, 'durability', 0))),
                    'status' => 'active',
                ];
            })
            ->filter(fn ($entry) => $entry['title'] !== ''
                && $entry['content'] !== ''
                && $entry['durability'] >= .85
                && $entry['importance'] >= .75
                && $entry['confidence'] >= .8)
            ->map(function (array $entry): array {
                unset($entry['durability']);

                return $entry;
            })
            ->take(1)
            ->values()
            ->all();
    }
}
