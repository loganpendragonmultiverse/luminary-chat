<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\Chat;
use App\Models\User;
use App\Services\AppSettingsService;
use App\Services\MemoryExtractionService;
use App\Services\VeniceChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MemoryExtractionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_throws_when_model_returns_invalid_json(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Pendragon',
            'slug' => 'pendragon-unit',
            'short_description' => 'Unit test character',
            'personality' => 'Focused',
            'scenario' => 'Unit test',
            'first_message' => 'Ready.',
            'default_model' => 'venice-uncensored-role-play',
            'temperature' => 0.9,
            'max_tokens' => 900,
            'top_p' => 0.95,
            'is_active' => true,
        ]);

        $chat = Chat::create([
            'user_id' => $user->id,
            'character_id' => $character->id,
            'title' => 'Unit Session',
            'model' => 'venice-uncensored-role-play',
            'temperature' => 0.9,
            'max_tokens' => 900,
            'top_p' => 0.95,
            'is_pinned' => false,
            'is_archived' => false,
        ]);

        $chat->messages()->create([
            'role' => 'user',
            'content' => 'Remember this forever.',
        ]);

        $venice = Mockery::mock(VeniceChatService::class);
        $venice->shouldReceive('rawCompletion')->once()->andReturn([
            'choices' => [
                ['message' => ['content' => 'not json']],
            ],
        ]);

        $settings = Mockery::mock(AppSettingsService::class);
        $settings->shouldReceive('get')->andReturn(3);

        $service = new MemoryExtractionService($venice, $settings);

        $this->expectException(RuntimeException::class);

        $service->extract($chat, $character);
    }
}
