<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CharacterVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CharacterVersionController extends Controller
{
    public function show(Request $request, Character $character, CharacterVersion $version): View
    {
        abort_unless($character->user_id === $request->user()->id, 404);
        abort_unless($version->character_id === $character->id, 404);

        $current = $character->snapshotPayload();
        $snapshot = $version->snapshot ?? [];
        $fields = [
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
            'default_model',
            'temperature',
            'max_tokens',
            'top_p',
        ];

        $diff = collect($fields)->map(function ($field) use ($current, $snapshot) {
            return [
                'field' => $field,
                'current' => (string) data_get($current, $field, ''),
                'version' => (string) data_get($snapshot, $field, ''),
            ];
        })->filter(fn ($row) => $row['current'] !== $row['version'])->values();

        return view('characters.version', [
            'character' => $character,
            'version' => $version,
            'diff' => $diff,
        ]);
    }

    public function restore(Request $request, Character $character, CharacterVersion $version): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);
        abort_unless($version->character_id === $character->id, 404);

        $snapshot = $version->snapshot ?? [];

        $character->update([
            'name' => data_get($snapshot, 'name', $character->name),
            'short_description' => data_get($snapshot, 'short_description', $character->short_description),
            'personality' => data_get($snapshot, 'personality', $character->personality),
            'scenario' => data_get($snapshot, 'scenario', $character->scenario),
            'first_message' => data_get($snapshot, 'first_message', $character->first_message),
            'long_bio' => data_get($snapshot, 'long_bio'),
            'writing_style' => data_get($snapshot, 'writing_style'),
            'tone' => data_get($snapshot, 'tone'),
            'companion_form' => data_get($snapshot, 'companion_form', $character->companion_form),
            'randomize_initial_chats' => (bool) data_get($snapshot, 'randomize_initial_chats', $character->randomize_initial_chats),
            'initial_chat_style' => data_get($snapshot, 'initial_chat_style', $character->initial_chat_style),
            'tags' => data_get($snapshot, 'tags'),
            'lore_notes' => data_get($snapshot, 'lore_notes'),
            'default_model' => data_get($snapshot, 'default_model', $character->default_model),
            'temperature' => data_get($snapshot, 'temperature', $character->temperature),
            'max_tokens' => data_get($snapshot, 'max_tokens', $character->max_tokens),
            'top_p' => data_get($snapshot, 'top_p', $character->top_p),
            'is_active' => data_get($snapshot, 'is_active', $character->is_active),
        ]);

        $character->versions()->create([
            'user_id' => $request->user()->id,
            'label' => 'Restored from '.$version->created_at?->format('Y-m-d H:i'),
            'snapshot' => $character->fresh()->snapshotPayload(),
        ]);

        return back()->with('status', 'Character restored from version history.');
    }
}
