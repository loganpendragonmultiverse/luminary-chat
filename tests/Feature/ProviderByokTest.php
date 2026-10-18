<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Chat;
use App\Models\User;
use App\Services\ModelCatalogService;
use App\Services\VeniceChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderByokTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_reconcile_venice_and_openrouter_keys_without_exposing_them(): void
    {
        Http::fake([
            'api.venice.ai/*' => Http::response(['data' => [[
                'id' => 'venice-model',
                'type' => 'text',
                'model_spec' => ['name' => 'Venice Model', 'offline' => false],
            ]]]),
            'openrouter.ai/api/v1/key' => Http::response(['data' => ['label' => 'test-key']]),
            'openrouter.ai/api/v1/models*' => Http::response(['data' => [[
                'id' => 'author/router-model',
                'name' => 'Router Model',
                'architecture' => ['output_modalities' => ['text']],
                'context_length' => 131072,
            ]]]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->put(route('settings.profile'), [
            'name' => $user->name,
            'email' => $user->email,
            'venice_api_key' => 'venice-secret',
            'openrouter_api_key' => 'openrouter-secret',
            'companion_name' => 'Luminary',
            'default_model' => 'openrouter:author/router-model',
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Settings updated and 2 models reconciled from your provider keys.');

        $saved = $user->fresh();
        $this->assertSame('venice-secret', $saved->venice_api_key);
        $this->assertSame('openrouter-secret', $saved->openrouter_api_key);
        $this->assertArrayHasKey('venice:venice-model', $saved->ai_model_catalog);
        $this->assertArrayHasKey('openrouter:author/router-model', $saved->ai_model_catalog);
        $this->assertArrayNotHasKey('venice_api_key', $saved->toArray());
        $this->assertArrayNotHasKey('openrouter_api_key', $saved->toArray());

        $this->actingAs($saved)->get(route('settings.index'))
            ->assertOk()
            ->assertDontSee('venice-secret')
            ->assertDontSee('openrouter-secret')
            ->assertSee('2 models reconciled', false);
    }

    public function test_invalid_openrouter_key_is_rejected_before_catalog_reconciliation(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/key' => Http::response(['error' => 'Unauthorized'], 401),
            'openrouter.ai/api/v1/models*' => Http::response(['data' => []]),
        ]);

        try {
            app(ModelCatalogService::class)->reconcile(null, 'invalid-key');
            $this->fail('An invalid OpenRouter key was accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('OpenRouter rejected this API key.', $exception->getMessage());
        }

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/models'));
    }

    public function test_openrouter_model_is_sent_to_the_openrouter_chat_endpoint(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'id' => 'completion-1',
                'model' => 'author/router-model',
                'choices' => [['message' => ['content' => 'Hello from OpenRouter']]],
                'usage' => ['total_tokens' => 12],
            ]),
        ]);

        $storedModel = 'openrouter:author/router-model';
        $user = User::factory()->create([
            'openrouter_api_key' => 'openrouter-secret',
            'ai_model_catalog' => [
                $storedModel => [
                    'id' => $storedModel,
                    'raw_id' => 'author/router-model',
                    'label' => 'Router Model',
                    'provider' => 'openrouter',
                    'provider_label' => 'OpenRouter',
                    'status' => 'active',
                ],
            ],
        ]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Luminary',
            'slug' => 'luminary-'.$user->id,
            'short_description' => 'AI companion',
            'personality' => 'Warm',
            'scenario' => 'A quiet room',
            'first_message' => 'Hello',
            'default_model' => $storedModel,
            'temperature' => 0.9,
            'max_tokens' => 900,
            'top_p' => 0.95,
            'is_active' => true,
        ]);
        $chat = Chat::create([
            'user_id' => $user->id,
            'character_id' => $character->id,
            'title' => 'Provider test',
            'model' => $storedModel,
            'temperature' => 0.9,
            'max_tokens' => 900,
            'top_p' => 0.95,
        ]);
        $chat->messages()->create(['role' => 'user', 'content' => 'Hello', 'model' => $storedModel]);

        $reply = app(VeniceChatService::class)->sendReply($chat, $character);

        $this->assertSame('Hello from OpenRouter', $reply['content']);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && $request['model'] === 'author/router-model'
                && $request->hasHeader('Authorization', 'Bearer openrouter-secret')
                && $request->hasHeader('X-OpenRouter-Title');
        });
    }

    public function test_openrouter_rate_limit_is_retried_before_the_reply_fails(): void
    {
        config()->set('services.openrouter.retry_delays_ms', [0, 0]);
        Http::fakeSequence('openrouter.ai/*')
            ->push(['error' => ['code' => 429, 'message' => 'Provider returned error']], 429)
            ->push([
                'id' => 'completion-after-retry',
                'model' => 'author/router-model',
                'choices' => [['message' => ['content' => 'Recovered reply']]],
                'usage' => ['total_tokens' => 9],
            ], 200);

        $storedModel = 'openrouter:author/router-model';
        $user = User::factory()->create([
            'openrouter_api_key' => 'openrouter-secret',
            'ai_model_catalog' => [
                $storedModel => [
                    'id' => $storedModel,
                    'raw_id' => 'author/router-model',
                    'label' => 'Router Model',
                    'provider' => 'openrouter',
                    'provider_label' => 'OpenRouter',
                    'status' => 'active',
                ],
            ],
        ]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Retry Character',
            'slug' => 'retry-character-'.$user->id,
            'short_description' => 'AI companion',
            'personality' => 'Steady',
            'scenario' => 'A quiet room',
            'first_message' => 'Hello',
            'default_model' => $storedModel,
            'temperature' => 0.9,
            'max_tokens' => 900,
            'top_p' => 0.95,
            'is_active' => true,
        ]);
        $chat = Chat::create([
            'user_id' => $user->id,
            'character_id' => $character->id,
            'title' => 'Retry test',
            'model' => $storedModel,
            'temperature' => 0.9,
            'max_tokens' => 900,
            'top_p' => 0.95,
        ]);
        $chat->messages()->create(['role' => 'user', 'content' => 'Hello', 'model' => $storedModel]);

        $reply = app(VeniceChatService::class)->sendReply($chat, $character);

        $this->assertSame('Recovered reply', $reply['content']);
        Http::assertSentCount(2);
    }


}
