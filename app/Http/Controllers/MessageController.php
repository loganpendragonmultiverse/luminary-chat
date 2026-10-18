<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatTurnLog;
use App\Models\Message;
use App\Services\MemoryExtractionService;
use App\Services\RoomTransitionService;
use App\Services\VeniceChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Throwable;

class MessageController extends Controller
{
    public function store(Request $request, Chat $chat, VeniceChatService $venice, MemoryExtractionService $memoryExtraction): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        if (! $venice->isModelAvailable($request->user(), $chat->model)) {
            return back()->withErrors([
                'content' => 'That model is not available through your saved provider keys. Reconcile your keys in Settings or choose another model.',
            ]);
        }

        $chat->loadMissing(['character', 'summary']);

        $userMessage = Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $data['content'],
            'model' => $chat->model,
        ]);

        app(RoomTransitionService::class)->apply($chat, $data['content']);

        return $this->generateAssistantReply($chat->fresh(), $venice, $memoryExtraction, 'message_send', $userMessage->id);
    }

    public function regenerate(Request $request, Chat $chat, VeniceChatService $venice, MemoryExtractionService $memoryExtraction): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);

        $lastMessage = $chat->messages()->reorder()->latest('id')->first();

        if (! $lastMessage) {
            return back()->withErrors([
                'content' => 'There is no message available to retry yet.',
            ]);
        }

        if ($lastMessage->role === 'user') {
            return $this->generateAssistantReply(
                $chat->fresh(),
                $venice,
                $memoryExtraction,
                'retry_saved_message',
                $lastMessage->id,
                'Response generated from your saved message.'
            );
        }

        $lastMessage->delete();

        return $this->generateAssistantReply($chat->fresh(), $venice, $memoryExtraction, 'regenerate_reply');
    }

    public function update(Request $request, Message $message, VeniceChatService $venice, MemoryExtractionService $memoryExtraction): RedirectResponse
    {
        abort_unless($message->chat->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $chat = $message->chat;
        $removedCount = 0;
        DB::transaction(function () use ($message, $chat, $data, &$removedCount): void {
            $laterMessageIds = $chat->messages()->where('id', '>', $message->id)->pluck('id');
            $removedCount = $laterMessageIds->count();

            if ($laterMessageIds->isNotEmpty()) {
                ChatTurnLog::whereIn('message_id', $laterMessageIds)->delete();
                Message::whereIn('id', $laterMessageIds)->delete();
            }

            $message->update([
                'content' => $data['content'],
                'edited_at' => now(),
            ]);
            $chat->summary()->updateOrCreate(
                ['chat_id' => $chat->id],
                ['summary' => '', 'last_generated_at' => null]
            );
            ChatTurnLog::create([
                'chat_id' => $chat->id,
                'message_id' => $message->id,
                'event_type' => 'message_edit_branch_'.$message->role,
                'response_payload' => ['removed_message_count' => $removedCount],
                'status_code' => 200,
            ]);
        });

        $removalNotice = $removedCount.' later '.str('message')->plural($removedCount).' permanently removed.';
        if ($message->role === 'user') {
            return $this->generateAssistantReply(
                $chat->fresh(['character', 'summary', 'user', 'room']),
                $venice,
                $memoryExtraction,
                'message_edit_branch_reply',
                $message->id,
                'New branch saved. '.$removalNotice.' A new assistant reply was generated from the edited user message.',
                'The new branch and edited user message were saved. '.$removalNotice.' The replacement assistant reply failed: '
            );
        }

        return redirect()->route('chats.show', $chat)->with(
            'status',
            'New branch saved. '.$removalNotice.' The edited assistant message is now part of the conversation history; send the next user message to continue.'
        );
    }

    public function destroy(Request $request, Message $message): RedirectResponse
    {
        abort_unless($message->chat->user_id === $request->user()->id, 404);

        $message->delete();

        return back()->with('status', 'Message deleted.');
    }

    protected function generateAssistantReply(Chat $chat, VeniceChatService $venice, MemoryExtractionService $memoryExtraction, string $eventType, ?int $userMessageId = null, ?string $successMessage = null, string $failurePrefix = ''): RedirectResponse
    {
        $startedAt = hrtime(true);
        try {
            $reply = $venice->sendReply($chat->fresh(), $chat->character);

            $roomTransitions = app(RoomTransitionService::class);
            $previousRoomId = $chat->room_id;
            $newRoom = $roomTransitions->apply($chat, $reply['content']);
            $reply['content'] = $roomTransitions->withoutControlDirectives($reply['content']);

            $reply['meta']['timing']['server_total_ms'] = round((hrtime(true) - $startedAt) / 1_000_000, 1);

            $assistantMessage = Message::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $reply['content'],
                'model' => $chat->model,
                'meta' => $reply['meta'],
                'token_count' => (int) data_get($reply, 'meta.usage.total_tokens', 0),
            ]);

            if ($newRoom && $newRoom->id !== $previousRoomId) {
                ChatTurnLog::create([
                    'chat_id' => $chat->id,
                    'message_id' => $assistantMessage->id,
                    'event_type' => 'character_room_change',
                    'response_payload' => [
                        'from_room_id' => $previousRoomId,
                        'room_id' => $newRoom->id,
                        'room_slug' => $newRoom->slug,
                    ],
                    'status_code' => 200,
                ]);
            }

            ChatTurnLog::create([
                'chat_id' => $chat->id,
                'message_id' => $userMessageId ?? $assistantMessage->id,
                'event_type' => $eventType,
                'request_payload' => $chat->user?->privacy_allow_diagnostics ? $reply['request_payload'] : null,
                'response_payload' => $chat->user?->privacy_allow_diagnostics ? $reply['response_payload'] : null,
                'status_code' => (int) data_get($reply, 'meta.status_code'),
            ]);

            $this->updateContinuityAfterResponse($chat->id, $venice, $memoryExtraction);

            $response = redirect()->route('chats.show', $chat);

            return $successMessage ? $response->with('status', $successMessage) : $response;
        } catch (Throwable $exception) {
            $statusCode = $exception instanceof RequestException
                ? $exception->response->status()
                : 500;

            ChatTurnLog::create([
                'chat_id' => $chat->id,
                'message_id' => $userMessageId,
                'event_type' => $eventType.'_failed',
                'status_code' => $statusCode,
                'error_message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'content' => $failurePrefix.$this->providerFailureMessage($chat, $venice, $exception),
            ]);
        }
    }

    protected function providerFailureMessage(Chat $chat, VeniceChatService $venice, Throwable $exception): string
    {
        if (! $exception instanceof RequestException) {
            return $exception->getMessage();
        }

        $models = $venice->availableModels($chat->user);
        $label = (string) ($models[$chat->model]['label'] ?? 'The selected model');
        $retryHint = ' Your message was saved. Open Message Tools on your latest message and choose Retry Response in a moment.';

        return match ($exception->response->status()) {
            429 => $label.' is temporarily rate-limited by its upstream provider.'.$retryHint,
            503 => $label.' is temporarily unavailable through its upstream provider.'.$retryHint,
            401, 403 => 'OpenRouter rejected the saved API key or its permissions. Reconcile the key in Settings, then retry the response.',
            402 => 'OpenRouter reports insufficient credits for this request. Add credits, then retry the response.',
            default => 'The model provider could not complete this reply.'.$retryHint,
        };
    }

    protected function updateContinuityAfterResponse(int $chatId, VeniceChatService $venice, MemoryExtractionService $memoryExtraction): void
    {
        app()->terminating(function () use ($chatId, $venice, $memoryExtraction): void {
            $chat = Chat::with(['character', 'summary'])->find($chatId);

            if (! $chat) {
                return;
            }

            try {
                $chat->summary()->updateOrCreate(
                    ['chat_id' => $chat->id],
                    [
                        'summary' => $venice->generateSummary($chat->fresh()),
                        'last_generated_at' => now(),
                    ]
                );
            } catch (Throwable $exception) {
                ChatTurnLog::create([
                    'chat_id' => $chat->id,
                    'message_id' => null,
                    'event_type' => 'summary_failed',
                    'status_code' => 500,
                    'error_message' => $exception->getMessage(),
                ]);
            }

            $this->extractMemory($chat->fresh(['character']), $memoryExtraction);
        });
    }

    protected function extractMemory(Chat $chat, MemoryExtractionService $memoryExtraction): void
    {
        try {
            foreach ($memoryExtraction->extract($chat, $chat->character) as $entry) {
                $target = $entry['scope'] === 'character'
                    ? $chat->character->memoryEntries()
                    : $chat->memoryEntries();

                $exists = $target->get()->contains(function ($memory) use ($entry): bool {
                    $existing = $this->memoryTerms($memory->title.' '.$memory->content);
                    $candidate = $this->memoryTerms($entry['title'].' '.$entry['content']);
                    if ($existing === [] || $candidate === []) {
                        return false;
                    }
                    $overlap = count(array_intersect($existing, $candidate));

                    return $overlap / max(1, min(count($existing), count($candidate))) >= .72;
                });

                if ($exists) {
                    continue;
                }

                $target->create([
                    'user_id' => $chat->user_id,
                    'chat_id' => $entry['scope'] === 'character' ? null : $chat->id,
                    'character_id' => $chat->character_id,
                    'title' => $entry['title'],
                    'content' => $entry['content'],
                    'category' => $entry['category'],
                    'source' => 'auto_extract',
                    'is_pinned' => $entry['is_pinned'],
                    'confidence' => $entry['confidence'],
                    'importance' => $entry['importance'],
                    'status' => $entry['status'],
                ]);
            }
        } catch (Throwable $exception) {
            ChatTurnLog::create([
                'chat_id' => $chat->id,
                'message_id' => null,
                'event_type' => 'memory_extract_failed',
                'status_code' => 500,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    protected function memoryTerms(string $value): array
    {
        $terms = preg_split('/[^a-z0-9]+/i', mb_strtolower($value)) ?: [];

        return array_values(array_unique(array_filter($terms, fn (string $term) => strlen($term) > 3)));
    }
}
