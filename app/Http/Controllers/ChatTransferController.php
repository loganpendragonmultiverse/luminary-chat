<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Chat;
use App\Models\MemoryEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatTransferController extends Controller
{
    public function export(Request $request, Chat $chat): JsonResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);
        $chat->load(['character', 'messages', 'summary', 'memoryEntries']);

        return response()->json([
            'package_version' => 'pendragon-chat-package.v1',
            'exported_at' => now()->toIso8601String(),
            'chat' => [
                'title' => $chat->title,
                'model' => $chat->model,
                'temperature' => $chat->temperature,
                'max_tokens' => $chat->max_tokens,
                'top_p' => $chat->top_p,
                'pinned_memory' => $chat->pinned_memory,
                'is_pinned' => $chat->is_pinned,
            ],
            'character' => $chat->character->snapshotPayload(),
            'messages' => $chat->messages->map(fn ($message) => [
                'role' => $message->role,
                'content' => $message->content,
                'model' => $message->model,
                'token_count' => $message->token_count,
                'meta' => $message->meta,
                'edited_at' => $message->edited_at?->toIso8601String(),
            ])->values(),
            'summary' => [
                'summary' => $chat->summary?->summary,
                'manual_notes' => $chat->summary?->manual_notes,
            ],
            'memory_entries' => $chat->memoryEntries->map(fn ($entry) => [
                'title' => $entry->title,
                'content' => $entry->content,
                'category' => $entry->category,
                'source' => $entry->source,
                'is_pinned' => $entry->is_pinned,
            ])->values(),
        ])->header('Content-Disposition', 'attachment; filename="chat-'.$chat->id.'-export.json"');
    }

    public function exportAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        $chats = Chat::query()
            ->where('user_id', $user->id)
            ->with(['character', 'messages', 'summary', 'memoryEntries'])
            ->latest('updated_at')
            ->get();

        $memory = MemoryEntry::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->get();

        return response()->json([
            'package_version' => 'luminary-account-export.v1',
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'date_of_birth' => $user->date_of_birth?->toDateString(),
                'terms_accepted_at' => $user->terms_accepted_at?->toIso8601String(),
                'terms_version' => $user->terms_version,
                'memory_enabled' => $user->memory_enabled,
                'memory_auto_extract_enabled' => $user->memory_auto_extract_enabled,
                'privacy_allow_diagnostics' => $user->privacy_allow_diagnostics,
            ],
            'chats' => $chats->map(fn (Chat $chat) => [
                'id' => $chat->id,
                'title' => $chat->title,
                'model' => $chat->model,
                'temperature' => $chat->temperature,
                'max_tokens' => $chat->max_tokens,
                'top_p' => $chat->top_p,
                'created_at' => $chat->created_at?->toIso8601String(),
                'updated_at' => $chat->updated_at?->toIso8601String(),
                'character' => $chat->character?->snapshotPayload(),
                'messages' => $chat->messages->map(fn ($message) => [
                    'role' => $message->role,
                    'content' => $message->content,
                    'model' => $message->model,
                    'token_count' => $message->token_count,
                    'edited_at' => $message->edited_at?->toIso8601String(),
                    'created_at' => $message->created_at?->toIso8601String(),
                ])->values(),
                'summary' => [
                    'summary' => $chat->summary?->summary,
                    'manual_notes' => $chat->summary?->manual_notes,
                ],
                'memory_entries' => $chat->memoryEntries->map(fn ($entry) => [
                    'title' => $entry->title,
                    'content' => $entry->content,
                    'category' => $entry->category,
                    'source' => $entry->source,
                    'is_pinned' => $entry->is_pinned,
                    'created_at' => $entry->created_at?->toIso8601String(),
                ])->values(),
            ])->values(),
            'memory_entries' => $memory->map(fn ($entry) => [
                'title' => $entry->title,
                'content' => $entry->content,
                'category' => $entry->category,
                'source' => $entry->source,
                'is_pinned' => $entry->is_pinned,
                'chat_id' => $entry->chat_id,
                'created_at' => $entry->created_at?->toIso8601String(),
            ])->values(),
        ])->header('Content-Disposition', 'attachment; filename="luminary-account-export-'.$user->id.'.json"');
    }

    public function import(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'chat_json' => ['required', 'string'],
        ])['chat_json'];

        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $characterData = data_get($decoded, 'character', []);
        $chatData = data_get($decoded, 'chat', []);

        $character = Character::create([
            'user_id' => $request->user()->id,
            'name' => data_get($characterData, 'name', 'Imported Chat Character'),
            'slug' => Str::slug((string) data_get($characterData, 'name', 'imported-chat-character')).'-'.Str::lower(Str::random(5)),
            'short_description' => data_get($characterData, 'short_description', 'Imported from chat package'),
            'personality' => data_get($characterData, 'personality', ''),
            'scenario' => data_get($characterData, 'scenario', ''),
            'first_message' => data_get($characterData, 'first_message', 'Hello.'),
            'long_bio' => data_get($characterData, 'long_bio'),
            'writing_style' => data_get($characterData, 'writing_style'),
            'tone' => data_get($characterData, 'tone'),
            'tags' => data_get($characterData, 'tags'),
            'lore_notes' => data_get($characterData, 'lore_notes'),
            'default_model' => data_get($characterData, 'default_model', \App\Services\ModelCatalogService::RECOMMENDED_MODEL),
            'temperature' => (float) data_get($characterData, 'temperature', 0.9),
            'max_tokens' => (int) data_get($characterData, 'max_tokens', 900),
            'top_p' => (float) data_get($characterData, 'top_p', 0.95),
            'is_active' => true,
            'is_favorite' => false,
        ]);

        $character->versions()->create([
            'user_id' => $request->user()->id,
            'label' => 'Imported from chat package',
            'snapshot' => $character->snapshotPayload(),
        ]);

        $chat = Chat::create([
            'user_id' => $request->user()->id,
            'character_id' => $character->id,
            'title' => data_get($chatData, 'title', $character->name.' Imported Session'),
            'model' => data_get($chatData, 'model', app(\App\Services\ModelCatalogService::class)->resolveDefaultModel($request->user(), $character->default_model)),
            'temperature' => (float) data_get($chatData, 'temperature', $character->temperature),
            'max_tokens' => (int) data_get($chatData, 'max_tokens', $character->max_tokens),
            'top_p' => (float) data_get($chatData, 'top_p', $character->top_p),
            'pinned_memory' => data_get($chatData, 'pinned_memory'),
            'is_pinned' => (bool) data_get($chatData, 'is_pinned', false),
            'is_archived' => false,
        ]);

        foreach ((array) data_get($decoded, 'messages', []) as $message) {
            $chat->messages()->create([
                'role' => data_get($message, 'role', 'assistant'),
                'content' => data_get($message, 'content', ''),
                'model' => data_get($message, 'model', $chat->model),
                'token_count' => (int) data_get($message, 'token_count', 0),
                'meta' => data_get($message, 'meta'),
                'edited_at' => data_get($message, 'edited_at'),
            ]);
        }

        $chat->summary()->create([
            'summary' => data_get($decoded, 'summary.summary', ''),
            'manual_notes' => data_get($decoded, 'summary.manual_notes', ''),
        ]);

        foreach ((array) data_get($decoded, 'memory_entries', []) as $entry) {
            $chat->memoryEntries()->create([
                'user_id' => $request->user()->id,
                'character_id' => $character->id,
                'title' => data_get($entry, 'title', 'Imported Memory'),
                'content' => data_get($entry, 'content', ''),
                'category' => data_get($entry, 'category', 'general'),
                'source' => data_get($entry, 'source', 'imported_chat'),
                'is_pinned' => (bool) data_get($entry, 'is_pinned', false),
            ]);
        }

        return redirect()->route('chats.show', $chat)->with('status', 'Chat package imported.');
    }
}
