<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Chat;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class VeniceImageService
{
    public function __construct(protected AppSettingsService $settings)
    {
    }

    public function availableModels(): array
    {
        return config('pendragon.image_models', []);
    }

    public function generate(Chat $chat, Character $character, array $input): array
    {
        $mode = $input['mode'] === 'character_selfie' ? 'character_selfie' : 'standard';
        $prompt = $mode === 'character_selfie'
            ? $this->buildSelfiePrompt($character, $input['prompt'])
            : trim((string) $input['prompt']);

        [$width, $height] = $this->dimensionsFor($input['aspect_ratio'] ?? '1:1');

        $payload = [
            'model' => $input['image_model'],
            'prompt' => $prompt,
            'negative_prompt' => trim((string) ($input['negative_prompt'] ?? '')),
            'width' => $width,
            'height' => $height,
            'safe_mode' => false,
            'response_format' => 'b64_json',
        ];

        $response = $this->client()->post('/image/generate', $payload);
        $response->throw();

        $images = data_get($response->json(), 'images', []);
        if (! is_array($images) || $images === []) {
            throw new RuntimeException('Venice image generation returned no images.');
        }

        $saved = [];

        foreach ($images as $index => $image) {
            $base64 = (string) (data_get($image, 'b64_json') ?? data_get($image, 'base64') ?? $image);
            if ($base64 === '') {
                continue;
            }

            $saved[] = $this->storeImage($chat, $base64, $input['image_model'], $index);
        }

        if ($saved === []) {
            throw new RuntimeException('Venice image generation returned unreadable image data.');
        }

        return [
            'mode' => $mode,
            'prompt' => $prompt,
            'display_prompt' => trim((string) $input['prompt']),
            'negative_prompt' => trim((string) ($input['negative_prompt'] ?? '')),
            'model' => $input['image_model'],
            'aspect_ratio' => $input['aspect_ratio'] ?? '1:1',
            'images' => $saved,
            'response_payload' => $response->json(),
            'request_payload' => $payload,
        ];
    }

    protected function buildSelfiePrompt(Character $character, string $prompt): string
    {
        return trim(implode("\n", array_filter([
            'Create an in-character selfie style image of this character.',
            'Keep the same visual identity consistent across future images.',
            'Character name: '.$character->name,
            'Short description: '.$character->short_description,
            'Personality: '.$character->personality,
            'Scenario: '.$character->scenario,
            $character->writing_style ? 'Writing style vibe: '.$character->writing_style : null,
            $character->tone ? 'Tone: '.$character->tone : null,
            $character->long_bio ? 'Long bio: '.$character->long_bio : null,
            $character->lore_notes ? 'Lore notes: '.$character->lore_notes : null,
            'Image direction: selfie perspective, natural framing, in-world authenticity.',
            'Additional request: '.trim($prompt),
        ])));
    }

    protected function dimensionsFor(string $aspectRatio): array
    {
        return match ($aspectRatio) {
            '3:4' => [960, 1280],
            '4:3' => [1280, 960],
            '16:9' => [1280, 720],
            default => [1024, 1024],
        };
    }

    protected function storeImage(Chat $chat, string $base64, string $model, int $index): array
    {
        $binary = base64_decode($base64, true);

        if ($binary === false) {
            throw new RuntimeException('Invalid base64 image received from Venice.');
        }

        $folder = public_path('generated-images/chat-'.$chat->id);
        File::ensureDirectoryExists($folder);

        $filename = Str::slug($model).'-'.now()->format('YmdHis').'-'.$index.'-'.Str::lower(Str::random(6)).'.png';
        $fullPath = $folder.DIRECTORY_SEPARATOR.$filename;

        file_put_contents($fullPath, $binary);

        return [
            'path' => 'generated-images/chat-'.$chat->id.'/'.$filename,
            'url' => asset('generated-images/chat-'.$chat->id.'/'.$filename),
            'filename' => $filename,
        ];
    }

    protected function client()
    {
        $key = $this->settings->get('app', 'venice_admin_key', config('services.venice.admin_key'));

        if (! $key) {
            throw new RuntimeException('VENICE_ADMIN_KEY is not configured.');
        }

        return Http::baseUrl(config('services.venice.base_url'))
            ->withToken($key)
            ->acceptJson()
            ->timeout(120);
    }
}
