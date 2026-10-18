<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Chat;
use App\Models\User;
use App\Services\MemoryExtractionService;
use App\Services\VeniceChatService;
use App\Services\VeniceImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Mockery;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Tests\TestCase;

class ChatFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_admin_can_create_archive_and_restore_chat(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $character = $this->makeCharacter($user);

        $venice = Mockery::mock(VeniceChatService::class);
        $venice->shouldReceive('isModelAvailable')->once()->andReturnTrue();
        $venice->shouldReceive('completionTokenLimit')->once()->andReturn(4096);
        $this->app->instance(VeniceChatService::class, $venice);

        $response = $this->actingAs($user)->post(route('chats.store', $character), [
            'creation_token' => (string) Str::uuid(),
            'title' => 'Forge Session',
            'initial_chat_mode' => 'normal',
        ]);

        $chat = Chat::firstOrFail();

        $response->assertRedirect(route('chats.show', $chat));
        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => $character->first_message,
        ]);

        $this->actingAs($user)->post(route('chats.archive', $chat))
            ->assertSessionHas('status', 'Chat archived.');
        $this->assertDatabaseHas('chats', [
            'id' => $chat->id,
            'is_archived' => true,
        ]);

        $this->actingAs($user)->post(route('chats.restore', $chat))
            ->assertSessionHas('status', 'Chat restored.');
        $this->assertDatabaseHas('chats', [
            'id' => $chat->id,
            'is_archived' => false,
        ]);
    }

    public function test_empty_opening_creates_chat_without_initial_message(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $character = $this->makeCharacter($user);

        $venice = Mockery::mock(VeniceChatService::class);
        $venice->shouldReceive('isModelAvailable')->once()->andReturnTrue();
        $venice->shouldReceive('completionTokenLimit')->once()->andReturn(4096);
        $this->app->instance(VeniceChatService::class, $venice);

        $this->actingAs($user)->post(route('chats.store', $character), [
            'creation_token' => (string) Str::uuid(),
            'title' => 'Quiet Start',
            'model' => '__default__',
            'initial_chat_mode' => 'empty',
        ])->assertRedirect();

        $chat = Chat::firstOrFail();
        $this->assertSame(0, $chat->messages()->count());
        $this->assertSame('', (string) $chat->pinned_memory);
        $this->assertSame('venice-uncensored-role-play', $character->fresh()->default_model);
    }

    public function test_new_chat_can_set_selected_model_as_character_default(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $character = $this->makeCharacter($user);
        $selectedModel = 'openrouter:test/new-default';

        $venice = Mockery::mock(VeniceChatService::class);
        $venice->shouldReceive('isModelSelectable')->once()->with($user, $selectedModel)->andReturnTrue();
        $venice->shouldReceive('completionTokenLimit')->once()->with($user, $selectedModel)->andReturn(4096);
        $this->app->instance(VeniceChatService::class, $venice);

        $this->actingAs($user)->post(route('chats.store', $character), [
            'creation_token' => (string) Str::uuid(),
            'title' => 'New Default',
            'model' => $selectedModel,
            'set_as_default' => '1',
            'initial_chat_mode' => 'empty',
        ])->assertRedirect();

        $this->assertSame($selectedModel, Chat::firstOrFail()->model);
        $this->assertSame($selectedModel, $character->fresh()->default_model);
    }

    public function test_chat_model_change_remains_chat_only_until_set_as_default_is_checked(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $chat = $this->makeChat($user);
        $chatOnlyModel = 'openrouter:test/chat-only';
        $newDefaultModel = 'openrouter:test/new-default';

        $venice = Mockery::mock(VeniceChatService::class);
        $venice->shouldReceive('isModelSelectable')->twice()->andReturnTrue();
        $venice->shouldReceive('completionTokenLimit')->twice()->andReturn(4096);
        $this->app->instance(VeniceChatService::class, $venice);

        $this->actingAs($user)->put(route('chats.update', $chat), [
            'model' => $chatOnlyModel,
        ])->assertSessionHas('status', 'Conversation settings updated.');

        $this->assertSame($chatOnlyModel, $chat->fresh()->model);
        $this->assertSame('venice-uncensored-role-play', $chat->character->fresh()->default_model);

        $this->actingAs($user)->put(route('chats.update', $chat), [
            'model' => $newDefaultModel,
            'set_as_default' => '1',
        ])->assertSessionHas('status', 'Conversation settings updated. Pendragon default model updated for future conversations.');

        $this->assertSame($newDefaultModel, $chat->fresh()->model);
        $this->assertSame($newDefaultModel, $chat->character->fresh()->default_model);
    }

    public function test_chat_workspace_exposes_empty_opening_default_and_composer_refocus(): void
    {
        $template = file_get_contents(resource_path('views/chats/index.blade.php'));
        $script = file_get_contents(public_path('luminary-chat.js'));
        $emptyPosition = strpos($template, '<option value="empty">Empty · No initial scene</option>');
        $characterDefaultPosition = strpos($template, '<option value="default">Character default');

        $this->assertSame(2, substr_count($template, 'name="set_as_default"'));
        $this->assertNotFalse($emptyPosition);
        $this->assertNotFalse($characterDefaultPosition);
        $this->assertLessThan($characterDefaultPosition, $emptyPosition);
        $this->assertStringContainsString('function focusComposer()', $script);
        $this->assertStringContainsString('const activeComposer = getComposer();', $script);
    }

    public function test_message_send_generates_reply_summary_and_log(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $chat = $this->makeChat($user);

        $venice = Mockery::mock(VeniceChatService::class);
        $venice->shouldReceive('isModelAvailable')->once()->andReturnTrue();
        $venice->shouldReceive('sendReply')->once()->andReturn([
            'content' => "Assistant reply\n\n> with quote",
            'meta' => [
                'usage' => ['total_tokens' => 321],
                'status_code' => 200,
            ],
            'request_payload' => ['messages' => []],
            'response_payload' => ['choices' => []],
        ]);
        $venice->shouldReceive('generateSummary')->once()->andReturn('Short summary');
        $this->app->instance(VeniceChatService::class, $venice);

        $memory = Mockery::mock(MemoryExtractionService::class);
        $memory->shouldReceive('extract')->once()->andReturn([]);
        $this->app->instance(MemoryExtractionService::class, $memory);

        $this->actingAs($user)->post(route('messages.store', $chat), [
            'content' => 'Hello from the user.',
        ])->assertRedirect(route('chats.show', $chat));

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'Hello from the user.',
        ]);
        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => "Assistant reply\n\n> with quote",
            'token_count' => 321,
        ]);
        $this->assertDatabaseHas('chat_summaries', [
            'chat_id' => $chat->id,
            'summary' => 'Short summary',
        ]);
        $this->assertDatabaseHas('chat_turn_logs', [
            'chat_id' => $chat->id,
            'event_type' => 'message_send',
            'status_code' => 200,
        ]);
    }

    public function test_rate_limited_reply_is_saved_with_a_clean_retry_path(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $chat = $this->makeChat($user);
        $assistantCountBefore = $chat->messages()->where('role', 'assistant')->count();
        $rateLimit = new RequestException(new Response(new Psr7Response(429, [], json_encode([
            'error' => ['message' => 'Provider returned error', 'metadata' => ['raw' => 'internal provider detail']],
        ]))));

        $venice = Mockery::mock(VeniceChatService::class);
        $venice->shouldReceive('isModelAvailable')->once()->andReturnTrue();
        $venice->shouldReceive('sendReply')->once()->andThrow($rateLimit);
        $venice->shouldReceive('availableModels')->twice()->andReturn([
            $chat->model => [
                'label' => 'TheDrummer: Cydonia 24B V4.1',
                'provider' => 'openrouter',
                'provider_label' => 'OpenRouter',
            ],
        ]);
        $this->app->instance(VeniceChatService::class, $venice);

        $this->actingAs($user)->post(route('messages.store', $chat), [
            'content' => 'Keep this message for retry.',
        ])->assertSessionHasErrors([
            'content' => 'TheDrummer: Cydonia 24B V4.1 is temporarily rate-limited by its upstream provider. Your message was saved. Open Message Tools on your latest message and choose Retry Response in a moment.',
        ]);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'Keep this message for retry.',
        ]);
        $this->assertSame($assistantCountBefore, $chat->messages()->where('role', 'assistant')->count());
        $this->assertDatabaseHas('chat_turn_logs', [
            'chat_id' => $chat->id,
            'event_type' => 'message_send_failed',
            'status_code' => 429,
        ]);

        $this->actingAs($user)->get(route('chats.show', $chat))
            ->assertOk()
            ->assertSee('>Retry Response</button>', false);
    }

    public function test_chat_package_import_creates_character_chat_and_memory(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $payload = json_encode([
            'package_version' => 'pendragon-chat-package.v1',
            'chat' => [
                'title' => 'Imported Session',
                'model' => 'venice-uncensored-role-play',
                'temperature' => 0.9,
                'max_tokens' => 900,
                'top_p' => 0.95,
            ],
            'character' => [
                'name' => 'Imported Hero',
                'short_description' => 'Imported character',
                'personality' => 'Brave',
                'scenario' => 'Test scenario',
                'first_message' => 'Hello there.',
            ],
            'messages' => [
                ['role' => 'assistant', 'content' => 'Hello there.'],
                ['role' => 'user', 'content' => 'General Kenobi.'],
            ],
            'summary' => [
                'summary' => 'Imported summary',
                'manual_notes' => 'Imported notes',
            ],
            'memory_entries' => [
                ['title' => 'Imported fact', 'content' => 'A durable memory', 'category' => 'lore'],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->post(route('chats.import'), [
            'chat_json' => $payload,
        ])->assertRedirect();

        $this->assertDatabaseHas('characters', [
            'name' => 'Imported Hero',
        ]);
        $this->assertDatabaseHas('chats', [
            'title' => 'Imported Session',
        ]);
        $this->assertDatabaseHas('memory_entries', [
            'title' => 'Imported fact',
            'category' => 'lore',
        ]);
    }

    public function test_image_generation_creates_assistant_message_with_generated_image_metadata(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $chat = $this->makeChat($user);

        $images = Mockery::mock(VeniceImageService::class);
        $images->shouldReceive('generate')->once()->andReturn([
            'mode' => 'character_selfie',
            'prompt' => 'Full selfie prompt',
            'display_prompt' => 'Selfie in the forge',
            'negative_prompt' => '',
            'model' => 'chroma',
            'aspect_ratio' => '1:1',
            'images' => [
                [
                    'path' => 'generated-images/chat-'.$chat->id.'/test.png',
                    'url' => 'https://example.com/generated-images/chat-'.$chat->id.'/test.png',
                    'filename' => 'test.png',
                ],
            ],
            'request_payload' => ['model' => 'chroma'],
            'response_payload' => ['images' => []],
        ]);
        $this->app->instance(VeniceImageService::class, $images);

        $this->actingAs($user)->post(route('images.store', $chat), [
            'mode' => 'character_selfie',
            'image_model' => 'chroma',
            'prompt' => 'Selfie in the forge',
            'negative_prompt' => '',
            'aspect_ratio' => '1:1',
        ])->assertRedirect(route('chats.show', $chat));

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'model' => 'chroma',
        ]);

        $message = \App\Models\Message::query()->where('chat_id', $chat->id)->latest('id')->firstOrFail();
        $this->assertSame('character_selfie', data_get($message->meta, 'generation_mode'));
        $this->assertSame('https://example.com/generated-images/chat-'.$chat->id.'/test.png', data_get($message->meta, 'generated_images.0.url'));
    }

    protected function makeCharacter(User $user): Character
    {
        return Character::create([
            'user_id' => $user->id,
            'name' => 'Pendragon',
            'slug' => 'pendragon-test',
            'short_description' => 'A heroic companion',
            'personality' => 'Steady and loyal',
            'scenario' => 'In the forge',
            'first_message' => 'The fire is ready.',
            'default_model' => 'venice-uncensored-role-play',
            'temperature' => 0.9,
            'max_tokens' => 900,
            'top_p' => 0.95,
            'is_active' => true,
        ]);
    }

    protected function makeChat(User $user): Chat
    {
        $character = $this->makeCharacter($user);

        $chat = Chat::create([
            'user_id' => $user->id,
            'character_id' => $character->id,
            'title' => 'Session One',
            'model' => 'venice-uncensored-role-play',
            'temperature' => 0.9,
            'max_tokens' => 900,
            'top_p' => 0.95,
            'is_pinned' => false,
            'is_archived' => false,
        ]);

        $chat->summary()->create([
            'summary' => '',
            'manual_notes' => '',
        ]);

        $chat->messages()->create([
            'role' => 'assistant',
            'content' => 'The forge awaits.',
            'model' => $chat->model,
        ]);

        return $chat;
    }
}
