<?php

namespace App\Http\Controllers;



use App\Models\Character;

use App\Models\Chat;

use App\Models\Message;

use App\Models\CompanionSession;

use App\Services\CharacterRosterService;

use App\Services\VeniceChatService;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;



class CompanionWorldController extends Controller

{

    private const CHARACTERS = ['luminary', 'mira', 'aurora'];

    private const ACTIONS = ['none', 'living', 'kitchen', 'bedroom', 'terrace', 'study', 'dining', 'bathroom', 'sit', 'lie', 'stand', 'stop', 'face'];



    public function context(Request $request)

    {

        if (! $request->user()) return $this->json(['authenticated' => false]);

        $roster = app(CharacterRosterService::class)->forUser($request->user());

        $characters = [];

        foreach (self::CHARACTERS as $key) {

            $character = $roster->firstWhere('template_key', $key);

            $characters[$key] = ['name' => $character->name, 'personaSource' => 'saved-character', 'chatUrl' => route('characters.chats', $character)];

        }

        $models = app(VeniceChatService::class)->availableModels($request->user());

        $choices = [];

        foreach ($models as $key => $model) $choices[] = ['id' => $key, 'name' => ($model['name'] ?? $model['label'] ?? $key).' · '.($model['provider_label'] ?? ucfirst(app(\App\Services\ModelCatalogService::class)->providerFor($key)))];

        return $this->json(['authenticated' => true, 'accountId' => $request->user()->id, 'csrf' => csrf_token(),

            'characters' => $characters, 'models' => $choices, 'providerConfigured' => count($choices) > 0,

            'memoryEnabled' => (bool) $request->user()->memory_enabled,
            'selectedCharacter' => in_array(data_get($request->user()->ai_model_preferences, 'companion_character'), self::CHARACTERS, true) ? data_get($request->user()->ai_model_preferences, 'companion_character') : 'luminary']);

    }



    public function session(Request $request, string $template, VeniceChatService $ai)

    {

        $character = $this->ownCharacter($request, $template);

        $data = $request->validate([

            'model' => ['sometimes', 'string', 'max:255'],
            'activate' => ['sometimes', 'boolean'],
            'selectionAt' => ['sometimes', 'numeric', 'min:0'],

            'state' => ['sometimes', 'array:look,control,x,z,yaw,posture,furniture,routineEpoch'],

            'state.look' => ['required_with:state', 'in:swim,casual,gala'],

            'state.control' => ['required_with:state', 'in:ai,manual'],

            'state.x' => ['required_with:state', 'numeric', 'between:-8,8'],

            'state.z' => ['required_with:state', 'numeric', 'between:-7,12'],

            'state.yaw' => ['required_with:state', 'numeric', 'between:-1000,1000'],

            'state.posture' => ['required_with:state', 'in:standing,sitting,lying'],

            'state.furniture' => ['nullable', 'string', 'max:60'],

            'state.routineEpoch' => ['required_with:state', 'numeric', 'min:0'],

        ]);

        if (isset($data['model']) && ! $ai->isModelSelectable($request->user(), $data['model']))

            return $this->json(['message' => 'Choose a model available through your saved keys.'], 422);

        $session = $this->ensureSession($request, $character, $ai);

        $chat = Chat::findOrFail($session->chat_id);

        if (isset($data['model'])) $chat->update(['model' => $data['model']]);

        if (isset($data['state'])) $session->update(['state' => $data['state']]);
        if (!empty($data['activate'])) {
            DB::transaction(function () use ($request,$template,$data) {
                $user=\App\Models\User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $preferences=(array)$user->ai_model_preferences;
                $selectedAt=min((float)($data['selectionAt']??microtime(true)*1000),microtime(true)*1000+10000);
                if ($selectedAt>=(float)($preferences['companion_selection_at']??0)) {
                    $user->update(['ai_model_preferences'=>array_replace($preferences,['companion_character'=>$template,'companion_selection_at'=>$selectedAt])]);
                }
            });
        }

        return $this->json(['chatId' => $chat->id, 'chatUrl' => route('chats.show', $chat), 'model' => $chat->model,

            'state' => $session->state, 'updatedAt' => $session->updated_at->toIso8601String(),

            'history' => $chat->messages()->reorder()->latest('id')->limit(80)->get()->reverse()->values()

                ->map(fn ($m) => ['id' => $m->id, 'role' => $m->role, 'text' => $m->content])]);

    }



    public function talk(Request $request, string $template, VeniceChatService $ai)

    {

        $character = $this->ownCharacter($request, $template);

        $data = $request->validate([

            'message' => ['required', 'string', 'max:4000'], 'requestId' => ['required', 'uuid'],

            'room' => ['required', 'in:Living room,Kitchen,Bedroom,Terrace,Study,Bathroom,Dining room'],

            'posture' => ['required', 'in:standing,sitting,lying'], 'appearance' => ['required', 'in:swim,casual,gala'],

            'control' => ['required', 'in:ai,manual'],

        ]);

        $lock = Cache::lock('companion-talk-'.$request->user()->id.'-'.$character->id, 180);

        if (! $lock->get()) return $this->json(['message' => 'A reply is already being generated for this companion.'], 409);

        try {

            $session = $this->ensureSession($request, $character, $ai);

            $chat = Chat::findOrFail($session->chat_id)->load(['user', 'character', 'summary', 'room']);

            $existing = $chat->messages()->where('role', 'assistant')->reorder()->latest('id')->limit(30)->get()

                ->first(fn ($m) => ($m->meta['request_id'] ?? null) === $data['requestId']);

            if ($existing) return $this->json(['reply' => $existing->content, 'action' => $existing->meta['action'] ?? 'none', 'target' => $existing->meta['target'] ?? null, 'messageId' => $existing->id]);

            if (! $ai->isModelSelectable($request->user(), $chat->model)) return $this->json(['message' => 'Select an available model or add a provider key in Account settings.'], 422);

            $hair = ['luminary' => 'long jet-black hair', 'mira' => 'long chestnut-brown hair', 'aurora' => 'long platinum-white hair'][$template];

            $prompt = $ai->promptPreview($chat, $character)."\n\nCompanion Mode scene overrides any remembered room or appearance. You are the only companion in a one-bedroom house with living room, study, kitchen, dining area, bathroom and terrace. Current room: ".$data['room'].'. Posture: '.$data['posture'].'. Outfit: '.$data['appearance'].'. Hair: '.$hair.'. Control: '.$data['control'].'. ';

            $prompt .= 'The user can temporarily take manual control. Respond with your own personality and agency; playful objections are allowed, never guilt, coercion or real-world claims. You can request bounded movement and furniture use in the house. Return JSON only: {"reply":"natural in-character answer","action":"'.implode('|', self::ACTIONS).'"}. The action must be one value from that list. Movement plans are executed even during manual control. You may choose to move, stay put, or decline a request in character; never claim to perform a movement while setting action to none. For chosen sitting use sit, chosen lying down use lie, and chosen standing up use stand. Choose none when no movement is relevant. The lie action walks to the bed and lies down automatically; for a request to go to the bedroom and lie down, choose lie directly. The sit action walks to a chair and sits automatically. For furniture actions also include target: bed, study-chair, sofa-left, sofa-right, lounge, dining-chair, dining-north, dining-west, dining-east, terrace-chair or terrace-right. Use the exact requested location, for example study-chair for the study chair. For other actions use target null. Actions are requests, not proof of successful movement. In manual mode, actions are suggestions and will not move the avatar. Do not use [room:] directives.';

            $messages = [['role' => 'system', 'content' => $prompt]];

            foreach ($chat->messages()->reorder()->latest('id')->limit(18)->get()->reverse() as $m)

                $messages[] = ['role' => $m->role, 'content' => mb_substr($m->content, 0, 4000)];

            $messages[] = ['role' => 'user', 'content' => $data['message']];

            $payload = ['model' => $chat->model, 'max_tokens' => min(900, $ai->completionTokenLimit($request->user(), $chat->model)), 'messages' => $messages,
                'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'companion_reply', 'strict' => true,
                    'schema' => ['type' => 'object', 'properties' => ['reply' => ['type' => 'string'], 'action' => ['type' => 'string', 'enum' => self::ACTIONS]],
                        'required' => ['reply', 'action'], 'additionalProperties' => false]]]];
            try { $result = $ai->rawCompletion($payload, $request->user()); }
            catch (\Illuminate\Http\Client\RequestException $e) {
                if (! in_array($e->response?->status(), [400, 422], true)) throw $e;
                unset($payload['response_format']);
                $result = $ai->rawCompletion($payload, $request->user());
            }

            $text = trim((string) ($result['choices'][0]['message']['content'] ?? ''));

            if ($text === '') return $this->json(['message' => 'The provider returned an empty reply. Please retry.'], 502);

            $decision=app(\App\Services\CompanionActionService::class)->decision($text);
            $reply=mb_substr($decision['reply'],0,6000);
            $action=$decision['action'];
            $target=$decision['target'];

            $assistant = DB::transaction(function () use ($chat, $data, $reply, $action, $target, $result) {

                Message::create(['chat_id' => $chat->id, 'role' => 'user', 'content' => $data['message'], 'model' => $chat->model, 'meta' => ['mode' => 'companion', 'request_id' => $data['requestId']]]);

                $assistant = Message::create(['chat_id' => $chat->id, 'role' => 'assistant', 'content' => $reply, 'model' => $chat->model,

                    'token_count' => (int) ($result['usage']['total_tokens'] ?? 0), 'meta' => ['mode' => 'companion', 'action' => $action, 'target' => $target, 'request_id' => $data['requestId']]]);

                $chat->touch(); return $assistant;

            });

            return $this->json(['reply' => $reply, 'action' => $action, 'target' => $target, 'messageId' => $assistant->id, 'chatId' => $chat->id]);

        } catch (\Throwable $e) {

            report($e); return $this->json(['message' => 'The provider could not complete the reply. Your message was not saved; retry after checking your model and key.'], 502);

        } finally { $lock->release(); }

    }



    private function ensureSession(Request $request, Character $character, VeniceChatService $ai): CompanionSession

    {

        return DB::transaction(function () use ($request, $character, $ai) {

            // Serialize creation for this account; one saved world per character.

            $request->user()->newQuery()->whereKey($request->user()->id)->lockForUpdate()->first();

            $session = CompanionSession::firstOrCreate(['user_id' => $request->user()->id, 'character_id' => $character->id]);

            if (! $session->chat_id || ! Chat::whereKey($session->chat_id)->where('user_id', $request->user()->id)->where('character_id', $character->id)->exists()) {

                $models = $ai->availableModels($request->user());

                $model = (string) app(\App\Services\ModelCatalogService::class)->resolveDefaultModel($request->user(), $character->default_model);

                $chat = Chat::create(['user_id' => $request->user()->id, 'character_id' => $character->id, 'title' => $character->name.' · Companion Mode',

                    'room_id' => $character->rooms()->where('is_default', true)->value('id'), 'model' => $model, 'temperature' => 1.0, 'max_tokens' => 900, 'top_p' => 1.0]);

                $session->update(['chat_id' => $chat->id]);

            }

            return $session;

        });

    }



    private function ownCharacter(Request $request, string $template): Character

    {

        abort_unless(in_array($template, self::CHARACTERS, true), 404);

        app(CharacterRosterService::class)->forUser($request->user());

        return Character::query()->where('user_id', $request->user()->id)->where('template_key', $template)->firstOrFail();

    }

    private function json(array $data, int $status = 200) { return response()->json($data, $status)->header('Cache-Control', 'private, no-store'); }

}
