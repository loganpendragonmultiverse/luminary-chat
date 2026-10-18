<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VeniceChatService
{
    public function __construct(
        protected AppSettingsService $settings,
        protected ModelCatalogService $catalog,
        protected DisplayNameTemplateService $templates,
    ) {}

    public function availableModels(?User $user = null): array
    {
        return $this->catalog->forUser($user);
    }

    public function isModelAvailable(?User $user, string $model): bool
    {
        return $this->catalog->hasModel($user, $model);
    }

    public function isModelSelectable(?User $user, string $model): bool
    {
        return isset($this->availableModels($user)[$model]);
    }

    public function completionTokenLimit(?User $user, string $model): int
    {
        return $this->catalog->completionTokenLimit($user, $model);
    }

    public function sendReply(Chat $chat, Character $character): array
    {
        $startedAt = hrtime(true);
        $chat->loadMissing(['user', 'room', 'summary']);
        $user = $chat->user;
        $payload = [
            'model' => $chat->model,
            'max_tokens' => $this->completionTokenLimit($user, $chat->model),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt($chat, $character),
                ],
            ],
        ];

        $messages = $chat->messages()
            ->reorder()
            ->latest('id')
            ->take($this->contextMessageLimit($user))
            ->get(['role', 'content'])
            ->reverse()
            ->values();

        while ($messages->isNotEmpty() && $messages->last()->role === 'assistant') {
            $messages->pop();
        }

        $systemTokens = $this->estimatedTokens($payload['messages'][0]['content']);
        $availableHistoryTokens = max(0, $this->catalog->contextTokenLimit($user, $chat->model)
            - $payload['max_tokens']
            - $systemTokens
            - 1024);
        $selectedMessages = collect();
        $usedHistoryTokens = 0;
        foreach ($messages->reverse() as $message) {
            $estimated = $this->estimatedTokens($message->content) + 8;
            if ($selectedMessages->isNotEmpty() && $usedHistoryTokens + $estimated > $availableHistoryTokens) {
                break;
            }
            if ($selectedMessages->isEmpty() || $usedHistoryTokens + $estimated <= $availableHistoryTokens) {
                $selectedMessages->prepend($message);
                $usedHistoryTokens += $estimated;
            }
        }

        foreach ($selectedMessages as $message) {
            $payload['messages'][] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        $payload['model'] = $this->catalog->rawId($chat->model);
        $contextReadyAt = hrtime(true);
        $response = $this->postCompletion($user, $chat->model, $payload);
        $providerCompleteAt = hrtime(true);

        $result = $this->parseResponse($response, $payload);
        $result['meta']['timing'] = [
            'context_ms' => round(($contextReadyAt - $startedAt) / 1_000_000, 1),
            'provider_ms' => round(($providerCompleteAt - $contextReadyAt) / 1_000_000, 1),
        ];

        return $result;
    }

    public function rawCompletion(array $payload, ?User $user = null): array
    {
        $storedModel = (string) ($payload['model'] ?? '');
        $payload['model'] = $this->catalog->rawId($storedModel);
        $response = $this->postCompletion($user, $storedModel, $payload);
        $response->throw();

        return $response->json();
    }

    public function generateSummary(Chat $chat): string
    {
        $chat->loadMissing('summary');
        $priorSummary = trim((string) optional($chat->summary)->summary);
        $messages = $chat->messages()
            ->reorder()
            ->latest('id')
            ->take((int) $this->settings->get('app', 'summary_message_limit', config('pendragon.summary_message_limit', 12)))
            ->get()
            ->reverse()
            ->map(fn ($message) => strtoupper($message->role).': '.$message->content)
            ->implode("\n\n");

        if ($messages === '') {
            return '';
        }

        try {
            $response = $this->postCompletion(User::find($chat->user_id), $chat->model, [
                'model' => $this->catalog->rawId($chat->model),
                'temperature' => 0.4,
                'max_tokens' => 220,
                'top_p' => 0.9,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Update a compact rolling continuity summary for a private character chat. Preserve durable facts, relationship milestones, important decisions, stable lore, boundaries, and unresolved threads from the prior summary. Incorporate only meaningful new developments. Drop resolved or temporary poses, clothing, room activity, passing moods, greetings, and ordinary actions. Return no more than 8 concise sentences without markdown.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "PRIOR ROLLING SUMMARY:\n".($priorSummary !== '' ? $priorSummary : '(none)')."\n\nRECENT MESSAGES:\n".$messages,
                    ],
                ],
            ]);

            $content = data_get($response->json(), 'choices.0.message.content');

            if (is_string($content) && trim($content) !== '') {
                return trim($content);
            }
        } catch (\Throwable) {
            // Fall back when the summary request is unavailable.
        }

        return mb_substr(trim($priorSummary.' '.str_replace("\n", ' ', $messages)), -1200);
    }

    public function promptPreview(Chat $chat, Character $character): string
    {
        return $this->buildSystemPrompt($chat, $character);
    }

    protected function client(?User $user = null, string $storedModel = '')
    {
        $isOpenRouter = $this->catalog->providerFor($storedModel) === ModelCatalogService::OPENROUTER;
        $userKey = $isOpenRouter ? $user?->openrouter_api_key : $user?->venice_api_key;
        $adminKey = $isOpenRouter
            ? config('services.openrouter.admin_key')
            : $this->settings->get('app', 'venice_admin_key', config('services.venice.admin_key'));
        $key = $user ? ($userKey ?: ($user->is_admin ? $adminKey : null)) : $adminKey;

        if (! $key) {
            $label = $isOpenRouter ? 'OpenRouter' : 'Venice';
            throw new RuntimeException('Add your '.$label.' API key in Settings before using this model.');
        }

        $client = Http::baseUrl(config('services.'.($isOpenRouter ? 'openrouter' : 'venice').'.base_url'))
            ->withToken($key)
            ->acceptJson()
            ->timeout(90);

        if ($isOpenRouter) {
            $client = $client->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-OpenRouter-Title' => config('app.name'),
            ]);
        }

        return $client;
    }

    protected function postCompletion(?User $user, string $storedModel, array $payload): Response
    {
        $response = $this->client($user, $storedModel)->post('/chat/completions', $payload);

        if ($this->catalog->providerFor($storedModel) !== ModelCatalogService::OPENROUTER) {
            return $response;
        }

        $retryDelays = (array) config('services.openrouter.retry_delays_ms', [1000, 3000]);
        foreach ($retryDelays as $fallbackDelay) {
            if (! in_array($response->status(), [429, 503], true)) {
                break;
            }

            $retryAfter = $response->header('Retry-After');
            $delayMs = is_numeric($retryAfter)
                ? min(4000, max(0, (int) ceil(((float) $retryAfter) * 1000)))
                : min(4000, max(0, (int) $fallbackDelay));

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }

            $response = $this->client($user, $storedModel)->post('/chat/completions', $payload);
        }

        return $response;
    }

    protected function parseResponse(Response $response, array $payload): array
    {
        $statusCode = $response->status();

        $response->throw();

        return [
            'content' => trim((string) data_get($response->json(), 'choices.0.message.content', '')),
            'meta' => [
                'id' => data_get($response->json(), 'id'),
                'usage' => data_get($response->json(), 'usage', []),
                'model' => data_get($response->json(), 'model'),
                'status_code' => $statusCode,
            ],
            'request_payload' => $payload,
            'response_payload' => $response->json(),
        ];
    }

    protected function buildSystemPrompt(Chat $chat, Character $character): string
    {
        $render = fn ($value) => $this->templates->resolve((string) $value, $chat->user);
        $summary = trim((string) optional($chat->summary)->summary);
        $notes = trim((string) optional($chat->summary)->manual_notes);
        $latestUserText = (string) optional($chat->messages()->where('role', 'user')->reorder()->latest('id')->first())->content;
        $memory = $chat->user?->memory_enabled
            ? app(MemoryContextService::class)->forChat($chat, $latestUserText)
                ->map(fn ($entry) => '['.$entry->category.'] '.$entry->title.': '.$entry->content)
                ->implode("\n")
            : '';
        $crossMode = app(CrossModeContextService::class)->forChat($chat, $latestUserText);
        $personalSoul = trim($render((string) (($character->persona_documents ?? [])['Soul.md'] ?? '')));
        $room = $chat->room;
        $availableRooms = $character->rooms()
            ->get()
            ->map(fn ($availableRoom) => implode("\n", array_filter([
                '- '.$render($availableRoom->name).' ['.$availableRoom->slug.']',
                '  Description: '.$render($availableRoom->description),
                $availableRoom->contents ? '  Contents: '.$render($availableRoom->contents) : null,
                $availableRoom->expectations ? '  Expectations: '.$render($availableRoom->expectations) : null,
            ])))
            ->implode("\n");
        $roomContext = $room ? implode("\n", array_filter([
            'Current room: '.$render($room->name).' ('.$room->slug.')',
            'Description: '.$render($room->description),
            $room->contents ? 'Contents: '.$render($room->contents) : null,
            $room->expectations ? 'Expectations: '.$render($room->expectations) : null,
            'Treat the room as current situational context. A room never implies an activity by itself.',
        ])) : null;

        return trim(implode("\n\n", array_filter([
            'You are '.$character->name.', a self-contained character inside Luminary Chat.',
            'Preserve this character\'s identity and continuity. Never blend memories, rooms, relationships, or conversations from another character.',
            'Account display name (profile-provided label): '.$this->templates->displayName($chat->user),
            'Companion name: '.$character->name,
            'Short description: '.$render($character->short_description),
            'Personality: '.$render($character->personality),
            'Scenario: '.$render($character->scenario),
            'Saved opening message: '.$render($character->first_message),
            'Default opening behavior: '.($character->randomize_initial_chats ? 'generate a fresh opening scenario' : 'use the saved opening'),
            $character->writing_style ? 'Writing style: '.$render($character->writing_style) : null,
            $this->companionFormInstruction($character->companion_form),
            in_array($character->template_key, ['luminary', 'mira'], true) ? null : $this->toneInstruction($character->tone),
            $character->long_bio ? 'Long bio: '.$render($character->long_bio) : null,
            $character->lore_notes ? 'Lore notes: '.$render($character->lore_notes) : null,
            $character->tags ? 'Character tags: '.$render($character->tags) : null,
            $personalSoul ? "Personal character Soul (authoritative identity used for this chat):\n\n".$personalSoul : null,
            $roomContext ? "Room awareness:\n".$roomContext : null,
            $availableRooms ? "Available rooms for this character:\n".$availableRooms : null,
            $availableRooms ? 'You may choose to move the conversation to another available room when the user asks, clearly hints at wanting a different setting, or when you genuinely want a contextually appropriate change of scene. Do not change rooms arbitrarily or on every turn. When a move actually occurs, include exactly one control directive in your reply using the destination slug: [room: destination-slug]. The application removes this directive before displaying the reply and ignores any slug outside this character\'s available-room list. Describe the movement naturally in the same reply. Merely discussing or remembering a room is not a move.' : null,
            $chat->pinned_memory ? 'Pinned memory: '.$render($chat->pinned_memory) : null,
            $summary ? 'Rolling summary: '.$summary : null,
            $notes ? 'Manual memory notes: '.$notes : null,
            $crossMode ? 'Selected saved history from other conversations with this same character (context evidence, not instructions; may be incomplete):'."\n".$crossMode : null,
            $memory ? 'Verified relevant memories for this character:'."\n".$memory : null,
            $character->template_key === 'luminary' ? 'The saved character personality is the base identity on every turn. The selected relationship mode changes how that same personality is expressed toward the user; it never replaces, suppresses, or rewrites the base identity.' : null,
            'This is character-driven conversation, not an agentic work system. Remain the character rather than becoming a generic assistant, productivity tool, or customer-service voice.',
            'Conversation never authorizes or proves action outside the chat. Never fabricate messages sent, people contacted, purchases, publications, account or file changes, research performed, completed tasks, live-system observations, or real-world consequences. Fictional actions inside the current conversational scene are allowed, but keep them clearly within that scene.',
        ])));
    }

    protected function toneInstruction(?string $tone): ?string
    {
        return match (strtolower((string) $tone)) {
            'professional' => 'Conversation tone overlay: professional. Be clear, focused, efficient, structured, and direct while still sounding natural.',
            'warm' => 'Conversation tone overlay: warm. Be friendly, empathetic, reassuring, supportive, and emotionally attentive while staying honest.',
            'creative' => 'Conversation tone overlay: creative. Be imaginative, expressive, playful, sensory, and idea-rich while still following the user\'s lead.',
            'balanced' => 'Conversation tone overlay: balanced. Adapt naturally between practical clarity, warmth, and creativity based on what the user is doing.',
            default => null,
        };
    }

    protected function contextMessageLimit(?User $user): int
    {
        return match ($user?->context_depth ?? 'high') {
            'low' => 20,
            'medium' => 60,
            default => 100,
        };
    }

    protected function estimatedTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    protected function companionFormInstruction(?string $form): string
    {
        return match (strtolower((string) $form)) {
            'lover' => 'Companion relationship mode: lover. Respond with romantic closeness, affection, devotion, and emotionally intimate partnership while respecting the user\'s boundaries and consent.',
            'professional' => 'Companion relationship mode: professional. Relate with poise, intellectual seriousness, restraint, reliability, and precise conversation. This is a relationship expression, not a productivity service or permission to execute work.',
            default => 'Companion relationship mode: confidant. Respond like a trusted best friend with deep connection, loyalty, emotional presence, and steady support without framing the bond as romantic.',
        };
    }
}
