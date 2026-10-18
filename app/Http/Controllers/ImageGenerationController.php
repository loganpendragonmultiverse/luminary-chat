<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatTurnLog;
use App\Models\Message;
use App\Services\VeniceImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ImageGenerationController extends Controller
{
    public function store(Request $request, Chat $chat, VeniceImageService $images): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'mode' => ['required', 'in:standard,character_selfie'],
            'image_model' => ['required', 'string', 'max:120'],
            'prompt' => ['required', 'string'],
            'negative_prompt' => ['nullable', 'string'],
            'aspect_ratio' => ['required', 'in:1:1,3:4,4:3,16:9'],
        ]);

        try {
            $generated = $images->generate($chat->fresh(['character']), $chat->character, $data);

            $message = Message::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => sprintf(
                    'Generated %d image%s using %s (%s).',
                    count($generated['images']),
                    count($generated['images']) === 1 ? '' : 's',
                    data_get(config('pendragon.image_models'), $generated['model'].'.label', $generated['model']),
                    $generated['mode'] === 'character_selfie' ? 'character selfie' : 'standard image'
                ),
                'model' => $generated['model'],
                'meta' => [
                    'generated_images' => $generated['images'],
                    'image_prompt' => $generated['display_prompt'],
                    'full_prompt' => $generated['prompt'],
                    'negative_prompt' => $generated['negative_prompt'],
                    'generation_mode' => $generated['mode'],
                    'aspect_ratio' => $generated['aspect_ratio'],
                ],
            ]);

            ChatTurnLog::create([
                'chat_id' => $chat->id,
                'message_id' => $message->id,
                'event_type' => 'image_generate',
                'request_payload' => $request->user()->privacy_allow_diagnostics ? $generated['request_payload'] : null,
                'response_payload' => $request->user()->privacy_allow_diagnostics ? $generated['response_payload'] : null,
                'status_code' => 200,
            ]);

            return redirect()->route('chats.show', $chat)->with('status', 'Image generated.');
        } catch (Throwable $exception) {
            ChatTurnLog::create([
                'chat_id' => $chat->id,
                'event_type' => 'image_generate_failed',
                'status_code' => 500,
                'error_message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'prompt' => 'Image generation failed: '.$exception->getMessage(),
            ]);
        }
    }
}
