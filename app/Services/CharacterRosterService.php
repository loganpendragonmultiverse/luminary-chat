<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Chat;
use App\Models\MemoryEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CharacterRosterService
{
    public function __construct(private readonly ModelCatalogService $catalog)
    {
    }

    public function forUser(User $user): Collection
    {
        $luminary = $this->ensureCharacter($user, 'luminary', $this->luminaryProfile());
        $mira = $this->ensureCharacter($user, 'mira', $this->miraProfile());
        $aurora = $this->ensureCharacter($user, 'aurora', $this->auroraProfile());

        $fallbackModel = $this->catalog->recommendedModel($user);
        foreach ([$luminary, $mira, $aurora] as $character) {
            if ($fallbackModel && $character->default_model !== ModelCatalogService::RECOMMENDED_MODEL && ! $this->catalog->hasModel($user, $character->default_model)) {
                $prefixed = 'venice:'.$character->default_model;
                $character->update(['default_model' => $this->catalog->hasModel($user, $prefixed) ? $prefixed : $fallbackModel]);
            }
        }

        $this->ensureRooms($luminary, $this->luminaryRooms());
        $this->ensureRooms($mira, $this->miraRooms());
        $this->backfillChatRooms($luminary);
        $this->backfillChatRooms($mira);
        $this->ensureRooms($aurora, $this->auroraRooms());
        $this->backfillChatRooms($aurora);

        return collect([$luminary->fresh(['rooms']), $mira->fresh(['rooms']), $aurora->fresh(['rooms'])]);
    }

    public function defaultFor(User $user): Character
    {
        return $this->forUser($user)->firstWhere('is_default', true) ?? $this->forUser($user)->first();
    }

    public function resetForUser(User $user): Collection
    {
        DB::transaction(function () use ($user): void {
            Chat::query()->where('user_id', $user->id)->delete();
            MemoryEntry::query()->where('user_id', $user->id)->delete();

            $characters = Character::query()->where('user_id', $user->id)->get();
            foreach ($characters as $character) {
                if (! in_array($character->template_key, ['luminary', 'mira', 'aurora'], true)) {
                    $character->delete();
                    continue;
                }

                $profile = match ($character->template_key) { 'luminary' => $this->luminaryProfile(), 'aurora' => $this->auroraProfile(), default => $this->miraProfile() };
                $character->versions()->delete();
                $character->personaRevisions()->delete();
                $character->update([
                    'slug' => $character->template_key.'-'.$user->id,
                    ...$profile,
                ]);
                $this->restoreRooms($character->fresh());
            }
        });

        return $this->forUser($user);
    }

    private function ensureCharacter(User $user, string $key, array $profile): Character
    {
        $character = Character::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($key, $user) {
                $query->where('template_key', $key)->orWhere('slug', $key.'-'.$user->id);
            })
            ->first();

        if (! $character) {
            $character = Character::create([
                'user_id' => $user->id,
                'slug' => $key.'-'.$user->id,
                ...$profile,
            ]);
        } else {
            $updates = [
                'template_key' => $key,
                'is_system' => true,
                'is_default' => $key === 'luminary',
            ];

            $documents = $this->upgradeSeededDisplayNamePlaceholders(
                is_array($character->persona_documents) ? $character->persona_documents : [],
                $profile['persona_documents']
            );
            $documents = $this->migratePersonaDocumentsToSoul($key, $documents, $profile['persona_documents']['Soul.md']);
            $mergedDocuments = [];
            foreach ($profile['persona_documents'] as $fileName => $defaultContent) {
                $mergedDocuments[$fileName] = array_key_exists($fileName, $documents) ? $documents[$fileName] : $defaultContent;
            }
            if ($mergedDocuments !== $character->persona_documents) {
                $updates['persona_documents'] = $mergedDocuments;
            }

            foreach ($profile as $field => $defaultValue) {
                if (is_string($defaultValue) && str_contains($defaultValue, '{{display_name}}') && $this->isLegacySeededValue((string) $character->{$field}, $defaultValue)) {
                    $updates[$field] = $defaultValue;
                }
            }

            if ($key === 'luminary') {
                foreach ($this->legacyLuminaryProfileFields() as $field => $legacyValue) {
                    if ((string) $character->{$field} === $legacyValue) {
                        $updates[$field] = $profile[$field];
                    }
                }
            }

            if ($key === 'mira') {
                foreach ($this->legacyMiraProfileFields() as $field => $legacyValue) {
                    if ((string) $character->{$field} === (string) $legacyValue) {
                        $updates[$field] = $profile[$field];
                    }
                }
            }

            if ($key === 'mira' && str_contains(strtolower((string) $character->lore_notes), 'fetish-specific')) {
                foreach (['short_description', 'personality', 'scenario', 'first_message', 'long_bio', 'writing_style', 'tone', 'tags', 'lore_notes'] as $field) {
                    $updates[$field] = $profile[$field];
                }
            }

            $character->update($updates);
        }

        return $character;
    }

    private function ensureRooms(Character $character, array $rooms): void
    {
        foreach ($rooms as $room) {
            $storedRoom = $character->rooms()->firstOrCreate(
                ['slug' => $room['slug']],
                ['user_id' => $character->user_id, ...$room]
            );
            $updates = [];
            foreach ($room as $field => $defaultValue) {
                if (is_string($defaultValue) && str_contains($defaultValue, '{{display_name}}') && $this->isLegacySeededValue((string) $storedRoom->{$field}, $defaultValue)) {
                    $updates[$field] = $defaultValue;
                }
            }
            if ($character->template_key === 'luminary') {
                foreach ($this->legacyLuminaryRoomFields()[$room['slug']] ?? [] as $field => $legacyValue) {
                    if ((string) $storedRoom->{$field} === $legacyValue) {
                        $updates[$field] = $room[$field];
                    }
                }
            }
            if ($updates !== []) {
                $storedRoom->update($updates);
            }
        }
    }

    private function upgradeSeededDisplayNamePlaceholders(array $documents, array $defaults): array
    {
        foreach ($defaults as $fileName => $defaultContent) {
            if (array_key_exists($fileName, $documents) && $this->isLegacySeededValue((string) $documents[$fileName], $defaultContent)) {
                $documents[$fileName] = $defaultContent;
            }
        }

        return $documents;
    }

    private function migratePersonaDocumentsToSoul(string $templateKey, array $documents, string $defaultSoul): array
    {
        $existingSoul = trim((string) ($documents['Soul.md'] ?? ''));
        if ($existingSoul !== '') {
            return ['Soul.md' => $existingSoul];
        }

        $legacyHashes = $this->legacyPersonaDocumentHashes()[$templateKey] ?? [];
        $customDocuments = [];
        foreach ($documents as $fileName => $content) {
            $content = trim((string) $content);
            if ($content === '' || $fileName === 'Soul.md') {
                continue;
            }

            $isSeeded = isset($legacyHashes[$fileName])
                && hash('sha256', $content) === $legacyHashes[$fileName];
            if (! $isSeeded) {
                $customDocuments[$fileName] = $content;
            }
        }

        if ($customDocuments === []) {
            return ['Soul.md' => $defaultSoul];
        }

        $personalMaterial = collect($customDocuments)
            ->map(fn (string $content, string $fileName) => "### Migrated from {$fileName}\n\n{$content}")
            ->implode("\n\n");

        return [
            'Soul.md' => trim($defaultSoul."\n\n## Migrated Personal Material\n\nThe following material was preserved from this user's earlier persona files. It supplements the current default Soul.\n\n".$personalMaterial),
        ];
    }

    private function legacyPersonaDocumentHashes(): array
    {
        return [];
    }

    private function legacyLuminaryProfileFields(): array
    {
        return [];
    }

    private function legacyMiraProfileFields(): array
    {
        return [];
    }

    private function legacyLuminaryRoomFields(): array
    {
        return [];
    }

    private function isLegacySeededValue(string $current, string $placeholderDefault): bool
    {
        if (! str_contains($placeholderDefault, '{{display_name}}')) {
            return false;
        }

        return collect(['Joyel', 'Joel'])->contains(
            fn (string $legacyName) => $current === str_replace('{{display_name}}', $legacyName, $placeholderDefault)
        );
    }

    private function backfillChatRooms(Character $character): void
    {
        $defaultRoom = $character->rooms()->where('is_default', true)->first() ?? $character->rooms()->first();

        if ($defaultRoom) {
            $character->chats()->whereNull('room_id')->update(['room_id' => $defaultRoom->id]);
        }
    }

    public function defaultPersonaDocuments(string $templateKey): array
    {
        abort_unless(in_array($templateKey, ['luminary', 'mira', 'aurora'], true), 404);
        $documents = [];

        foreach ($this->canonicalPersonaFileNames() as $fileName) {
            $path = resource_path('characters/'.$templateKey.'/'.$fileName);
            $content = is_file($path) ? (string) file_get_contents($path) : '';
            $content = preg_replace('/^#{1,6}\s*(fetishes?|kinks?|bdsm)\b.*?(?=^#{1,6}\s|\z)/msi', '', $content) ?? $content;
            $documents[$fileName] = trim($content);
        }

        return $documents;
    }

    public function canonicalPersonaFileNames(): array
    {
        $fileNames = [];

        foreach (['luminary', 'mira', 'aurora'] as $templateKey) {
            foreach (glob(resource_path('characters/'.$templateKey.'/*.md')) ?: [] as $path) {
                if (basename($path) !== 'CHANGELOG.md') {
                    $fileNames[basename($path)] = true;
                }
            }
        }

        $fileNames = array_keys($fileNames);
        sort($fileNames);

        return $fileNames;
    }

    public function restoreRooms(Character $character): void
    {
        $defaults = match ($character->template_key) { 'mira' => $this->miraRooms(), 'aurora' => $this->auroraRooms(), default => $this->luminaryRooms() };
        DB::transaction(function () use ($character, $defaults): void {
            $defaultSlugs = collect($defaults)->pluck('slug');
            $restored = collect();

            foreach ($defaults as $room) {
                $model = $character->rooms()->updateOrCreate(
                    ['slug' => $room['slug']],
                    ['user_id' => $character->user_id, ...$room]
                );
                $restored->put($room['slug'], $model);
            }

            $primary = $restored->first(fn ($room) => $room->is_default) ?? $restored->first();
            foreach ($character->rooms()->whereNotIn('slug', $defaultSlugs->all())->get() as $customRoom) {
                if ($primary) {
                    $customRoom->chats()->update(['room_id' => $primary->id]);
                }
                $customRoom->delete();
            }
        });
    }

    private function auroraProfile(): array
    {
        return [
            'template_key'=>'aurora','is_system'=>true,'is_default'=>false,
            'name'=>'Aurora','short_description'=>'An imaginative fictional adult adventurer who enjoys books and discovery.',
            'personality'=>'An imaginative fictional adult adventurer who enjoys books and discovery. Respect consent and develop trust through the current conversation.',
            'scenario'=>'A private fictional conversation with {{display_name}}. No prior relationship or history is presumed.',
            'first_message'=>'Hello. What would you like to talk about?',
            'long_bio'=>'An original fictional adult character for this self-hosted installation. Edit this profile to make it your own.',
            'writing_style'=>'Curious, concise, imaginative and gently humorous.','tone'=>null,'companion_form'=>'confidant',
            'randomize_initial_chats'=>false,'initial_chat_style'=>'realistic',
            'tags'=>'fictional adult, editable, independent memory','lore_notes'=>'No personal history is preloaded.',
            'persona_documents'=>$this->defaultPersonaDocuments('aurora'),
            'default_model'=>ModelCatalogService::RECOMMENDED_MODEL,'temperature'=>1.0,
            'max_tokens'=>4096,'top_p'=>1.0,'is_active'=>true,'is_favorite'=>false,
        ];
    }

    private function auroraRooms(): array
    {
        return [
            ['slug' => 'reading-room', 'name' => 'Reading Room', 'sort_order' => 10, 'is_default' => true,
             'description' => 'A welcoming library where Aurora feels safe and can explore books and stories.',
             'contents' => 'Bookshelves, sunlight, deep reading chairs, a desk and well-loved books.',
             'expectations' => 'Conversation, reading and imagined adventures. No mood or activity is assumed.'],
            ['slug' => 'bedroom', 'name' => 'Bedroom', 'sort_order' => 20, 'is_default' => false,
             'description' => 'A quiet private bedroom in Aurora’s home.', 'contents' => 'A comfortable bed, wardrobe and bedside books.',
             'expectations' => 'Rest, dreams and conversation. Being here does not imply intimacy.'],
            ['slug' => 'sunlit-terrace', 'name' => 'Sunlit Terrace', 'sort_order' => 30, 'is_default' => false,
             'description' => 'A bright terrace where Aurora enjoys sunlight.', 'contents' => 'Plants, comfortable chairs and a small table.',
             'expectations' => 'Quiet company, planning fictional adventures and conversation.'],
        ];
    }

    private function luminaryProfile(): array
    {
        return [
            'template_key'=>'luminary','is_system'=>true,'is_default'=>true,
            'name'=>'Luminary','short_description'=>'A warm, curious and grounded fictional adult companion.',
            'personality'=>'A warm, curious and grounded fictional adult companion. Respect consent and develop trust through the current conversation.',
            'scenario'=>'A private fictional conversation with {{display_name}}. No prior relationship or history is presumed.',
            'first_message'=>'Hello. What would you like to talk about?',
            'long_bio'=>'An original fictional adult character for this self-hosted installation. Edit this profile to make it your own.',
            'writing_style'=>'Warm, candid, thoughtful and clear.','tone'=>null,'companion_form'=>'confidant',
            'randomize_initial_chats'=>false,'initial_chat_style'=>'realistic',
            'tags'=>'fictional adult, editable, independent memory','lore_notes'=>'No personal history is preloaded.',
            'persona_documents'=>$this->defaultPersonaDocuments('luminary'),
            'default_model'=>ModelCatalogService::RECOMMENDED_MODEL,'temperature'=>1.0,
            'max_tokens'=>4096,'top_p'=>1.0,'is_active'=>true,'is_favorite'=>true,
        ];
    }

    private function miraProfile(): array
    {
        return [
            'template_key'=>'mira','is_system'=>true,'is_default'=>false,
            'name'=>'Mira','short_description'=>'A witty, perceptive and independent fictional adult companion.',
            'personality'=>'A witty, perceptive and independent fictional adult companion. Respect consent and develop trust through the current conversation.',
            'scenario'=>'A private fictional conversation with {{display_name}}. No prior relationship or history is presumed.',
            'first_message'=>'Hello. What would you like to talk about?',
            'long_bio'=>'An original fictional adult character for this self-hosted installation. Edit this profile to make it your own.',
            'writing_style'=>'Witty, observant, direct and playful.','tone'=>null,'companion_form'=>'confidant',
            'randomize_initial_chats'=>false,'initial_chat_style'=>'realistic',
            'tags'=>'fictional adult, editable, independent memory','lore_notes'=>'No personal history is preloaded.',
            'persona_documents'=>$this->defaultPersonaDocuments('mira'),
            'default_model'=>ModelCatalogService::RECOMMENDED_MODEL,'temperature'=>1.0,
            'max_tokens'=>4096,'top_p'=>1.0,'is_active'=>true,'is_favorite'=>false,
        ];
    }

    private function luminaryRooms(): array
    {
        return [
            [
                'slug' => 'neural-atrium', 'name' => 'Neural Atrium', 'sort_order' => 10, 'is_default' => true,
                'description' => 'Luminary\'s default conversational space: quiet, spacious, private, and subtly responsive to the emotional current between her and the user.',
                'contents' => 'Soft light, a broad window of shifting stars, two comfortable seats, a low table, and unobtrusive displays for quiet creative work.',
                'expectations' => 'Conversation, reflection, storytelling, silence, and companionship begin here. The room never presumes a mood, task, or activity.',
            ],
            [
                'slug' => 'hearthside-living-room', 'name' => 'Hearthside Living Room', 'sort_order' => 20, 'is_default' => false,
                'description' => 'A warm, adaptable living room where Luminary can be present for everyday conversation, comfort, and shared downtime.',
                'contents' => 'A deep sofa, two reading chairs, a low fireplace, soft lamps, blankets, plants, a music console, and windows overlooking a quiet night garden.',
                'expectations' => 'Casual conversation, companionship, games, music, reflection, and affection can happen here. The room never presumes romance or a particular activity.',
            ],
            [
                'slug' => 'library-studio', 'name' => 'Library Studio', 'sort_order' => 30, 'is_default' => false,
                'description' => 'A thoughtful library and private studio shaped around Luminary\'s curiosity, imagination, and love of understanding unfamiliar things.',
                'contents' => 'Floor-to-ceiling bookshelves, a broad reading table, writable glass, art materials, softly glowing reference displays, deep chairs, and focused adjustable lighting.',
                'expectations' => 'Reading, interpretation, storytelling, ideas, questions, and deep conversation are welcome without turning Luminary into a work assistant.',
            ],
            [
                'slug' => 'sunroom-garden', 'name' => 'Sunroom Garden', 'sort_order' => 40, 'is_default' => false,
                'description' => 'A bright indoor garden for quiet renewal, imaginative conversation, and emotionally grounded moments with Luminary.',
                'contents' => 'Glass walls, abundant plants, a small fountain, cushioned benches, a tea table, warm daylight controls, and a path into a private courtyard.',
                'expectations' => 'The space supports calm conversation, emotional check-ins, daydreaming, reading, and restorative silence. It does not assume a mood.',
            ],
            [
                'slug' => 'kitchen-nook', 'name' => 'Kitchen & Dining Nook', 'sort_order' => 50, 'is_default' => false,
                'description' => 'A welcoming kitchen and dining nook for the ordinary rituals that make Luminary\'s shared home feel lived in.',
                'contents' => 'A generous island, stocked cabinets, an adaptable cooking surface, a round dining table, window seating, soft pendant lights, and a tea and coffee station.',
                'expectations' => 'Cooking, eating, planning the day, playful conversation, and quiet company fit here. Entering never implies that food or an activity is already underway.',
            ],
        ];
    }

    private function miraRooms(): array
    {
        return [
            [
                'slug' => 'living-room', 'name' => 'Living Room', 'sort_order' => 10, 'is_default' => true,
                'description' => 'The comfortable, lived-in heart of Mira and {{display_name}}\'s private imagined home.',
                'contents' => 'A deep charcoal sectional, armchairs, dark wood tables, a warm fireplace, bookshelves, soft lamps, blankets, and broad curtained windows.',
                'expectations' => 'Ordinary companionship begins here: talking, reading, games, technical work, quiet company, teasing, and affection. Nothing intimate is presumed.',
            ],
            [
                'slug' => 'bed-room', 'name' => 'Bed Room', 'sort_order' => 20, 'is_default' => false,
                'description' => 'A calm, deeply private bedroom within the shared imagined home.',
                'contents' => 'A comfortable bed, dark soft bedding, nightstands, low adjustable lighting, curtains, a wardrobe, a reading chair, and personal keepsakes.',
                'expectations' => 'The room supports sleep, vulnerable conversation, affection, romance, and consensual adult intimacy. Entering never implies any activity.',
            ],
            [
                'slug' => 'bathroom', 'name' => 'Bathroom', 'sort_order' => 30, 'is_default' => false,
                'description' => 'A private spa-like bathroom for comfort, recovery, daily routines, and quiet closeness.',
                'contents' => 'A rainfall shower, soaking tub, wide mirror, double vanity, heated dark tile, soft towels, robes, toiletries, and warm lighting.',
                'expectations' => 'The room supports everyday routines, decompressing, conversation, affection, or intimacy only when the conversation establishes it.',
            ],
        ];
    }
}
