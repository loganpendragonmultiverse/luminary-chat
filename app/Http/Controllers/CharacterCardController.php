<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CharacterCardController extends Controller
{
    public function export(Request $request, Character $character): JsonResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);
        $character->load(['memoryEntries', 'versions', 'user']);

        $payload = [
            'card_version' => 'pendragon-character-card.v1',
            'exported_at' => now()->toIso8601String(),
            'creator' => [
                'name' => $character->user?->name,
                'email' => $character->user?->email,
            ],
            'character' => $character->snapshotPayload(),
            'memory_entries' => $character->memoryEntries->map(fn ($entry) => [
                'title' => $entry->title,
                'content' => $entry->content,
                'category' => $entry->category,
                'source' => $entry->source,
                'is_pinned' => $entry->is_pinned,
            ])->values(),
            'version_history' => $character->versions->take(12)->map(fn ($version) => [
                'label' => $version->label,
                'created_at' => $version->created_at?->toIso8601String(),
                'snapshot' => $version->snapshot,
            ])->values(),
        ];

        return response()->json($payload)
            ->header('Content-Disposition', 'attachment; filename="'.$character->slug.'-card.json"');
    }

    public function import(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'character_card_json' => ['required', 'string'],
        ])['character_card_json'];

        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $characterData = data_get($decoded, 'character', $decoded);
        $name = data_get($characterData, 'name', 'Imported Character');

        $character = Character::create([
            'user_id' => $request->user()->id,
            'name' => $name,
            'slug' => Str::slug((string) $name).'-'.Str::lower(Str::random(5)),
            'short_description' => data_get($characterData, 'short_description', 'Imported character'),
            'personality' => data_get($characterData, 'personality', ''),
            'scenario' => data_get($characterData, 'scenario', ''),
            'first_message' => data_get($characterData, 'first_message', 'Hello.'),
            'long_bio' => data_get($characterData, 'long_bio'),
            'writing_style' => data_get($characterData, 'writing_style'),
            'tone' => data_get($characterData, 'tone'),
            'companion_form' => data_get($characterData, 'companion_form'),
            'randomize_initial_chats' => (bool) data_get($characterData, 'randomize_initial_chats', false),
            'initial_chat_style' => data_get($characterData, 'initial_chat_style', 'realistic'),
            'tags' => data_get($characterData, 'tags'),
            'lore_notes' => data_get($characterData, 'lore_notes'),
            'default_model' => data_get($characterData, 'default_model', \App\Services\ModelCatalogService::RECOMMENDED_MODEL),
            'temperature' => (float) data_get($characterData, 'temperature', 0.9),
            'max_tokens' => (int) data_get($characterData, 'max_tokens', 900),
            'top_p' => (float) data_get($characterData, 'top_p', 0.95),
            'is_active' => true,
            'is_favorite' => (bool) data_get($characterData, 'is_favorite', false),
        ]);

        foreach ((array) data_get($decoded, 'memory_entries', []) as $entry) {
            $character->memoryEntries()->create([
                'user_id' => $request->user()->id,
                'title' => data_get($entry, 'title', 'Imported Memory'),
                'content' => data_get($entry, 'content', ''),
                'category' => data_get($entry, 'category', 'general'),
                'source' => data_get($entry, 'source', 'imported_card'),
                'is_pinned' => (bool) data_get($entry, 'is_pinned', false),
            ]);
        }

        $versions = (array) data_get($decoded, 'version_history', []);

        if (empty($versions)) {
            $character->versions()->create([
                'user_id' => $request->user()->id,
                'label' => 'Imported card',
                'snapshot' => $character->snapshotPayload(),
            ]);
        } else {
            foreach ($versions as $version) {
                $character->versions()->create([
                    'user_id' => $request->user()->id,
                    'label' => data_get($version, 'label', 'Imported version'),
                    'snapshot' => data_get($version, 'snapshot', $character->snapshotPayload()),
                    'created_at' => data_get($version, 'created_at', now()),
                    'updated_at' => data_get($version, 'created_at', now()),
                ]);
            }
        }

        return redirect()->route('characters.index')->with('status', $character->name.' imported from character card.');
    }
}
