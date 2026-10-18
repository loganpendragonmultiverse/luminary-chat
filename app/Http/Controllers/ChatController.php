<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Chat;
use App\Models\ChatFolder;
use App\Models\ChatSummary;
use App\Models\ChatTurnLog;
use App\Models\Message;
use App\Services\AppSettingsService;
use App\Services\CharacterRosterService;
use App\Services\DisplayNameTemplateService;
use App\Services\InitialScenarioService;
use App\Services\ModelCatalogService;
use App\Services\VeniceChatService;
use App\Services\VeniceImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request, CharacterRosterService $roster, VeniceChatService $venice, VeniceImageService $images): View
    {
        return $this->workspace($request, $roster->defaultFor($request->user()), null, $venice, $images);
    }

    public function forCharacter(Request $request, Character $character, CharacterRosterService $roster, VeniceChatService $venice, VeniceImageService $images): View
    {
        abort_unless($character->user_id === $request->user()->id, 404);
        $roster->forUser($request->user());

        return $this->workspace($request, $character->fresh(['rooms']), null, $venice, $images);
    }

    public function store(Request $request, Character $character, InitialScenarioService $scenarios, VeniceChatService $venice): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);
        $creationData = $request->validate([
            'creation_token' => ['required', 'uuid'],
            'model' => ['nullable', 'string', 'max:255'],
            'initial_chat_mode' => ['nullable', 'in:empty,default,normal,randomized'],
            'set_as_default' => ['nullable', 'boolean'],
        ]);
        $creationToken = $creationData['creation_token'];
        $existingChat = Chat::query()
            ->where('user_id', $request->user()->id)
            ->where('creation_token', $creationToken)
            ->first();
        if ($existingChat) {
            return redirect()->route('chats.show', $existingChat);
        }

        $requestedModel = (string) ($creationData['model'] ?? '__default__');
        $model = $requestedModel === '__default__' ? (string) app(ModelCatalogService::class)->resolveDefaultModel($request->user(), $character->default_model) : $requestedModel;
        $setAsDefault = $request->boolean('set_as_default') && $requestedModel !== '__default__';

        if (($requestedModel === '__default__' && ! $venice->isModelAvailable($request->user(), $model))
            || ($requestedModel !== '__default__' && ! $venice->isModelSelectable($request->user(), $model))) {
            return back()->withErrors(['model' => 'Choose a model available through one of your provider keys.']);
        }

        $folderId = $request->input('chat_folder_id');
        if ($folderId) {
            abort_unless(ChatFolder::query()->where('user_id', $request->user()->id)->whereKey($folderId)->exists(), 404);
        }

        $room = $character->rooms()
            ->when($request->filled('room_id'), fn (Builder $query) => $query->whereKey($request->integer('room_id')))
            ->when(! $request->filled('room_id'), fn (Builder $query) => $query->where('is_default', true))
            ->first() ?? $character->rooms()->first();

        $openingMode = (string) ($creationData['initial_chat_mode'] ?? 'empty');
        $opening = null;
        $scenarioMemory = '';
        if ($openingMode !== 'empty') {
            $randomizeOverride = match ($openingMode) {
                'randomized' => true,
                'normal' => false,
                default => null,
            };
            $opening = $scenarios->openingFor($request->user(), $character, $randomizeOverride);
            $scenarioMemory = 'Opening scenario: '.$opening['scenario'];
        }
        $pinnedMemory = trim((string) $request->input('pinned_memory'));

        try {
            $chat = DB::transaction(function () use ($request, $character, $room, $model, $venice, $scenarioMemory, $pinnedMemory, $folderId, $opening, $creationToken, $setAsDefault): Chat {
                if ($setAsDefault && $character->default_model !== $model) {
                    $character->update(['default_model' => $model]);
                }

                $chat = Chat::create([
                    'user_id' => $request->user()->id,
                    'character_id' => $character->id,
                    'creation_token' => $creationToken,
                    'room_id' => $room?->id,
                    'title' => $request->input('title', $character->name.' Conversation'),
                    'model' => $model,
                    'temperature' => 1.0,
                    'max_tokens' => $venice->completionTokenLimit($request->user(), $model),
                    'top_p' => 1.0,
                    'pinned_memory' => trim(implode("\n\n", array_filter([$scenarioMemory, $pinnedMemory]))),
                    'is_pinned' => false,
                    'chat_folder_id' => $folderId,
                    'is_archived' => false,
                ]);

                ChatSummary::create(['chat_id' => $chat->id, 'summary' => '', 'manual_notes' => '']);
                if ($opening) {
                    Message::create(['chat_id' => $chat->id, 'role' => 'assistant', 'content' => $opening['message'], 'model' => $chat->model]);
                }

                return $chat;
            });
        } catch (QueryException $exception) {
            $chat = Chat::query()
                ->where('user_id', $request->user()->id)
                ->where('creation_token', $creationToken)
                ->first();
            if (! $chat) {
                throw $exception;
            }
        }

        $response = redirect()->route('chats.show', $chat);

        return $setAsDefault
            ? $response->with('status', $character->name.' default model updated for future conversations.')
            : $response;
    }

    public function show(Request $request, Chat $chat, CharacterRosterService $roster, VeniceChatService $venice, VeniceImageService $images): View
    {
        abort_unless($chat->user_id === $request->user()->id, 404);
        $roster->forUser($request->user());
        $chat->load(['character.rooms', 'room', 'messages', 'summary', 'logs', 'memoryEntries', 'folder']);

        return $this->workspace($request, $chat->character, $chat, $venice, $images);
    }

    public function update(Request $request, Chat $chat, VeniceChatService $venice): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);
        $chat->loadMissing('character');

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'model' => ['sometimes', 'required', 'string', 'max:255'],
            'set_as_default' => ['nullable', 'boolean'],
            'pinned_memory' => ['nullable', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
            'room_id' => ['nullable', 'integer'],
        ]);

        $model = $chat->model;
        $setAsDefault = false;
        if (array_key_exists('model', $data)) {
            $model = $data['model'] === '__default__' ? (string) app(ModelCatalogService::class)->resolveDefaultModel($request->user(), $chat->character->default_model) : $data['model'];
            $setAsDefault = $request->boolean('set_as_default') && $data['model'] !== '__default__';
            if (($model !== $chat->model || $setAsDefault) && ! $venice->isModelSelectable($request->user(), $model)) {
                return back()->withErrors(['model' => 'Choose a model available through one of your provider keys.']);
            }
        }

        $room = null;
        if (array_key_exists('room_id', $data) && $data['room_id'] !== null) {
            $room = $chat->character->rooms()->whereKey($data['room_id'])->first();
            abort_unless($room, 404);
        }

        $updates = [];
        if (array_key_exists('title', $data)) {
            $updates['title'] = $data['title'];
        }
        if (array_key_exists('model', $data)) {
            $updates['model'] = $model;
            $updates['temperature'] = 1.0;
            $updates['max_tokens'] = $venice->completionTokenLimit($request->user(), $model);
            $updates['top_p'] = 1.0;
        }
        if (array_key_exists('pinned_memory', $data)) {
            $updates['pinned_memory'] = $data['pinned_memory'];
        }
        if ($request->has('is_pinned')) {
            $updates['is_pinned'] = $request->boolean('is_pinned');
        }
        if ($room) {
            $updates['room_id'] = $room->id;
        }

        $roomChanged = $room && $chat->room_id !== $room->id;
        DB::transaction(function () use ($chat, $updates, $room, $roomChanged, $model, $setAsDefault): void {
            if ($updates !== []) {
                $chat->update($updates);
            }

            if ($setAsDefault && $chat->character->default_model !== $model) {
                $chat->character->update(['default_model' => $model]);
            }

            if (! $roomChanged) {
                return;
            }

            $message = Message::create([
                'chat_id' => $chat->id,
                'role' => 'system',
                'content' => 'Room changed manually to '.$room->name.'. Treat this as the current room and acknowledge the new setting naturally in the next reply.',
                'model' => $model,
                'meta' => ['event' => 'manual_room_change', 'room_id' => $room->id, 'room_slug' => $room->slug],
            ]);
            ChatTurnLog::create([
                'chat_id' => $chat->id,
                'message_id' => $message->id,
                'event_type' => 'manual_room_change',
                'response_payload' => ['room_id' => $room->id, 'room_slug' => $room->slug],
                'status_code' => 200,
            ]);
        });

        $status = $roomChanged
            ? 'Conversation settings updated. The room change was recorded in the conversation; '.$chat->character->name.' will acknowledge it in the next reply.'
            : 'Conversation settings updated.';
        if ($setAsDefault) {
            $status .= ' '.$chat->character->name.' default model updated for future conversations.';
        }

        return back()->with('status', $status);
    }

    public function destroy(Request $request, Chat $chat): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);
        $character = $chat->character;
        $chat->delete();

        return redirect()->route('characters.chats', $character)->with('status', 'Conversation deleted.');
    }

    private function workspace(Request $request, Character $character, ?Chat $activeChat, VeniceChatService $venice, VeniceImageService $images): View
    {
        $query = trim((string) $request->input('q'));
        $base = Chat::query()
            ->where('user_id', $request->user()->id)
            ->where('character_id', $character->id)
            ->when($query !== '', fn (Builder $builder) => $builder->where(function (Builder $nested) use ($query) {
                $nested->where('title', 'like', '%'.$query.'%')
                    ->orWhereHas('messages', fn (Builder $message) => $message->where('content', 'like', '%'.$query.'%'));
            }));

        if (! $activeChat) {
            $activeChat = (clone $base)->where('is_archived', false)
                ->with(['character.rooms', 'room', 'messages', 'summary', 'logs', 'memoryEntries', 'folder'])
                ->orderByDesc('is_pinned')->latest('updated_at')->first();
        }

        $settingsChat = null;
        if ($request->filled('settings_chat')) {
            $settingsChat = (clone $base)
                ->with(['character', 'room'])
                ->whereKey($request->integer('settings_chat'))
                ->firstOrFail();
        }

        $roster = app(CharacterRosterService::class)->forUser($request->user());

        return view('chats.index', [
            'characters' => $roster,
            'activeCharacter' => $character->loadMissing('rooms'),
            'luminary' => $character,
            'chatFolders' => ChatFolder::query()->where('user_id', $request->user()->id)->get(),
            'chats' => (clone $base)->where('is_archived', false)->with(['character', 'room'])->orderByDesc('is_pinned')->latest('updated_at')->get(),
            'archivedChats' => (clone $base)->where('is_archived', true)->with(['character', 'room'])->latest('archived_at')->get(),
            'activeChat' => $activeChat,
            'settingsChat' => $settingsChat,
            'modelOptions' => $venice->availableModels($request->user()),
            'defaultCharacterModel' => app(ModelCatalogService::class)->resolveDefaultModel($request->user(), $character->default_model),
            'imageModelOptions' => $images->availableModels(),
            'defaultImageModel' => app(AppSettingsService::class)->defaults()['default_image_model'],
            'userDisplayName' => app(DisplayNameTemplateService::class)->displayName($request->user()),
            'query' => $query,
        ]);
    }

}
