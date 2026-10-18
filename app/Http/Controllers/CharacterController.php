<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Services\AppSettingsService;
use App\Services\CharacterRosterService;
use App\Services\ModelCatalogService;
use App\Services\VeniceChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CharacterController extends Controller
{
    public function index(Request $request, CharacterRosterService $roster, VeniceChatService $venice): View
    {
        $modelOptions = $venice->availableModels($request->user());
        $characters = $roster->forUser($request->user())
            ->map(fn (Character $character) => $character->load(['user', 'memoryEntries.revisions', 'versions', 'rooms']));
        $managedCharacter = $characters->firstWhere('id', $request->integer('manage'));
        $managementSection = in_array($request->query('section'), ['defaults', 'markdown', 'rooms', 'memory'], true)
            ? $request->query('section')
            : 'defaults';
        if ($managedCharacter?->template_key === 'luminary' && $managementSection === 'markdown') {
            $managementSection = 'defaults';
        }
        if ($managedCharacter?->template_key === 'mira' && $managementSection === 'defaults') {
            $managementSection = 'markdown';
        }

        return view('characters.index', [
            'characters' => $characters,
            'managedCharacter' => $managedCharacter,
            'managementSection' => $managementSection,
            'miraDefaultSoul' => $managedCharacter?->template_key === 'mira'
                ? ($roster->defaultPersonaDocuments('mira')['Soul.md'] ?? '')
                : null,
            'modelOptions' => $modelOptions,
            'defaultModelSelection' => $managedCharacter
                ? app(ModelCatalogService::class)->resolveDefaultModel($request->user(), $managedCharacter->default_model)
                : null,
            'query' => '',
        ]);
    }

    public function updateDefaultModel(Request $request, Character $character, VeniceChatService $venice): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'default_model' => ['required', 'string', 'max:255'],
        ]);

        if (! $venice->isModelSelectable($request->user(), $data['default_model'])) {
            return back()->withErrors(['default_model' => 'Choose a visible model available through one of your provider keys.']);
        }

        $character->update(['default_model' => $data['default_model']]);

        return back()->with('status', $character->name.' default model updated for new chats.');
    }

    public function resetAll(Request $request, CharacterRosterService $roster): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESET ALL CHARACTERS'],
        ], [
            'confirmation.in' => 'Type RESET ALL CHARACTERS exactly to confirm the permanent reset.',
        ]);

        $roster->resetForUser($request->user());

        return redirect()->route('characters.index')->with('status', 'All characters were reset. Chats and memories were deleted, and the original Souls, rooms, and character settings were restored.');
    }

    public function updateDefaults(Request $request, Character $character): RedirectResponse
    {
        abort_unless(
            $character->user_id === $request->user()->id
                && $character->is_system
                && $character->template_key === 'luminary',
            404
        );
        $data = $request->validate([
            'companion_form' => ['required', 'in:confidant,lover,professional'],
        ]);

        $character->update(['companion_form' => $data['companion_form']]);

        return back()->with('status', $character->name.' relationship role updated.');
    }

    public function store(Request $request, AppSettingsService $settings): RedirectResponse
    {
        $data = $this->validated($request);
        $defaults = $settings->defaults();
        $data['user_id'] = $request->user()->id;
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['is_active'] = true;
        $data['default_model'] = $data['default_model'] ?: $defaults['default_model'];
        $data['temperature'] = $data['temperature'] ?: $defaults['default_temperature'];
        $data['max_tokens'] = $data['max_tokens'] ?: $defaults['default_max_tokens'];
        $data['top_p'] = $data['top_p'] ?: $defaults['default_top_p'];

        $character = Character::create($data);
        $this->storeVersion($character, $request->user()->id, 'Initial version');

        return redirect()->route('characters.index')->with('status', 'Character created.');
    }

    public function import(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'character_json' => ['required', 'string'],
        ])['character_json'];

        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $character = Character::create([
            'user_id' => $request->user()->id,
            'name' => data_get($decoded, 'name', 'Imported Character'),
            'slug' => Str::slug((string) data_get($decoded, 'name', 'imported-character')).'-'.Str::lower(Str::random(5)),
            'short_description' => data_get($decoded, 'short_description', 'Imported character'),
            'personality' => data_get($decoded, 'personality', ''),
            'scenario' => data_get($decoded, 'scenario', ''),
            'first_message' => data_get($decoded, 'first_message', 'Hello.'),
            'long_bio' => data_get($decoded, 'long_bio'),
            'writing_style' => data_get($decoded, 'writing_style'),
            'tone' => data_get($decoded, 'tone'),
            'companion_form' => data_get($decoded, 'companion_form'),
            'randomize_initial_chats' => (bool) data_get($decoded, 'randomize_initial_chats', false),
            'initial_chat_style' => data_get($decoded, 'initial_chat_style', 'realistic'),
            'tags' => data_get($decoded, 'tags'),
            'lore_notes' => data_get($decoded, 'lore_notes'),
            'default_model' => data_get($decoded, 'default_model', ModelCatalogService::RECOMMENDED_MODEL),
            'temperature' => (float) data_get($decoded, 'temperature', 0.9),
            'max_tokens' => (int) data_get($decoded, 'max_tokens', 900),
            'top_p' => (float) data_get($decoded, 'top_p', 0.95),
            'is_active' => true,
        ]);

        $this->storeVersion($character, $request->user()->id, 'Imported version');

        return redirect()->route('characters.index')->with('status', $character->name.' imported.');
    }

    public function favorite(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);

        $character->update([
            'is_favorite' => ! $character->is_favorite,
        ]);

        return back()->with('status', $character->is_favorite ? 'Character favorited.' : 'Character unfavorited.');
    }

    public function export(Request $request, Character $character): JsonResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);

        return response()->json([
            'name' => $character->name,
            'short_description' => $character->short_description,
            'personality' => $character->personality,
            'scenario' => $character->scenario,
            'first_message' => $character->first_message,
            'long_bio' => $character->long_bio,
            'writing_style' => $character->writing_style,
            'tone' => $character->tone,
            'companion_form' => $character->companion_form,
            'randomize_initial_chats' => $character->randomize_initial_chats,
            'initial_chat_style' => $character->initial_chat_style,
            'tags' => $character->tags,
            'lore_notes' => $character->lore_notes,
            'default_model' => $character->default_model,
            'temperature' => $character->temperature,
            'max_tokens' => $character->max_tokens,
            'top_p' => $character->top_p,
            'memory_entries' => $character->memoryEntries()->get(['title', 'content', 'category', 'is_pinned']),
        ]);
    }

    public function duplicate(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);

        $duplicate = $character->duplicateForUser($request->user()->id);

        foreach ($character->memoryEntries as $entry) {
            $duplicate->memoryEntries()->create([
                'user_id' => $request->user()->id,
                'title' => $entry->title,
                'content' => $entry->content,
                'category' => $entry->category,
                'source' => $entry->source,
                'is_pinned' => $entry->is_pinned,
            ]);
        }

        $this->storeVersion($duplicate, $request->user()->id, 'Duplicated from '.$character->name);

        return redirect()->route('characters.index')->with('status', $duplicate->name.' created from template.');
    }

    public function update(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);

        $data = $this->validated($request);
        $data['slug'] = $character->slug ?: Str::slug($data['name']).'-'.Str::lower(Str::random(5));

        $character->update($data);
        $this->storeVersion($character->fresh(), $request->user()->id, 'Saved changes');

        return back()->with('status', 'Character updated.');
    }

    public function destroy(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);

        $character->delete();

        return redirect()->route('characters.index')->with('status', 'Character deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'short_description' => ['required', 'string', 'max:255'],
            'personality' => ['required', 'string'],
            'scenario' => ['required', 'string'],
            'first_message' => ['required', 'string'],
            'long_bio' => ['nullable', 'string'],
            'writing_style' => ['nullable', 'string', 'max:255'],
            'tone' => ['nullable', 'string', 'max:255'],
            'companion_form' => ['nullable', 'in:confidant,lover,professional'],
            'randomize_initial_chats' => ['nullable', 'boolean'],
            'initial_chat_style' => ['nullable', 'in:realistic,fantasy'],
            'tags' => ['nullable', 'string', 'max:255'],
            'lore_notes' => ['nullable', 'string'],
            'default_model' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                if ($value && ! app(VeniceChatService::class)->isModelAvailable($request->user(), (string) $value)) {
                    $fail('Choose a model available through one of your provider keys.');
                }
            }],
            'temperature' => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens' => ['nullable', 'integer', 'min:128', 'max:4096'],
            'top_p' => ['nullable', 'numeric', 'between:0,1'],
        ]);
    }

    protected function storeVersion(Character $character, int $userId, string $label): void
    {
        $character->versions()->create([
            'user_id' => $userId,
            'label' => $label,
            'snapshot' => $character->snapshotPayload(),
        ]);
    }

}
