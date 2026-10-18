<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ModelCatalogService
{
    public const VENICE = 'venice';
    public const OPENROUTER = 'openrouter';
    public const RECOMMENDED_MODEL = 'aion-3.5';

    public function recommendedModel(?User $user): ?string
    {
        $models = $this->forUser($user);
        $candidates = [
            self::VENICE => 'aion-labs-aion-3-5',
            self::OPENROUTER => 'aion-labs/aion-3.5',
        ];
        foreach ($candidates as $provider => $rawId) {
            $hasKey = $provider === self::VENICE
                ? ($user?->venice_api_key || $user?->is_admin)
                : (bool) $user?->openrouter_api_key;
            $id = $this->storedId($provider, $rawId);
            if ($hasKey && isset($models[$id]) && ($models[$id]['status'] ?? 'active') === 'active') return $id;
        }
        return null;
    }

    public function resolveDefaultModel(?User $user, ?string $selection): ?string
    {
        if (! $selection || $selection === self::RECOMMENDED_MODEL) return $this->recommendedModel($user);
        if ($this->hasModel($user, $selection)) return $selection;
        $legacy = $this->storedId(self::VENICE, $selection);
        if ($this->hasModel($user, $legacy)) return $legacy;
        return $this->recommendedModel($user);
    }

    public function reconcile(?string $veniceKey, ?string $openRouterKey): array
    {
        $models = [];

        if ($veniceKey) {
            $models += $this->fetchVeniceModels($veniceKey);
        }

        if ($openRouterKey) {
            $models += $this->fetchOpenRouterModels($openRouterKey);
        }

        uasort($models, fn (array $left, array $right) => [$left['provider'], $left['label']] <=> [$right['provider'], $right['label']]);

        return $models;
    }

    public function forUser(?User $user): array
    {
        return collect($this->allForUser($user))
            ->reject(fn (array $model) => $model['is_hidden'])
            ->sortBy(fn (array $model) => [! $model['is_favorite'], $model['provider'], mb_strtolower($model['label'])])
            ->all();
    }

    public function allForUser(?User $user): array
    {
        $models = $this->rawCatalogForUser($user);
        $preferences = is_array($user?->ai_model_preferences) ? $user->ai_model_preferences : [];
        $hidden = array_fill_keys(array_filter((array) ($preferences['hidden'] ?? []), 'is_string'), true);
        $favorites = array_fill_keys(array_filter((array) ($preferences['favorites'] ?? []), 'is_string'), true);

        return collect($models)
            ->map(function (array $model, string $id) use ($hidden, $favorites) {
                return $model + [
                    'is_hidden' => isset($hidden[$id]),
                    'is_favorite' => isset($favorites[$id]),
                ];
            })
            ->sortBy(fn (array $model) => [$model['provider'], mb_strtolower($model['label'])])
            ->all();
    }

    public function savePreferences(User $user, array $visibleModels, array $favoriteModels): void
    {
        $catalogIds = array_keys($this->rawCatalogForUser($user));
        $visible = array_values(array_intersect($catalogIds, array_filter($visibleModels, 'is_string')));
        $favorites = array_values(array_intersect($visible, array_filter($favoriteModels, 'is_string')));

        $user->update([
            'ai_model_preferences' => [
                ...(is_array($user->ai_model_preferences) ? $user->ai_model_preferences : []),
                'hidden' => array_values(array_diff($catalogIds, $visible)),
                'favorites' => $favorites,
            ],
        ]);
    }

    private function rawCatalogForUser(?User $user): array
    {
        $catalog = $user?->ai_model_catalog;

        if (is_array($catalog) && $catalog !== []) {
            return $catalog;
        }

        if ($user && ! $user->venice_api_key && ! $user->is_admin) {
            return [];
        }

        return collect(config('pendragon.models', []))
            ->mapWithKeys(function (array $model, string $id) {
                $storedId = $this->storedId(self::VENICE, $id);

                return [$storedId => $model + [
                    'id' => $storedId,
                    'provider' => self::VENICE,
                    'provider_label' => 'Venice',
                    'raw_id' => $id,
                ]];
            })
            ->all();
    }

    public function hasModel(?User $user, string $storedId): bool
    {
        if (isset($this->rawCatalogForUser($user)[$storedId])) {
            return true;
        }

        return ! str_contains($storedId, ':')
            && ($user?->venice_api_key || $user?->is_admin || $user === null)
            && isset(config('pendragon.models', [])[$storedId]);
    }

    public function providerFor(string $storedId): string
    {
        if (str_starts_with($storedId, self::OPENROUTER.':')) {
            return self::OPENROUTER;
        }

        return self::VENICE;
    }

    public function rawId(string $storedId): string
    {
        foreach ([self::VENICE, self::OPENROUTER] as $provider) {
            if (str_starts_with($storedId, $provider.':')) {
                return substr($storedId, strlen($provider) + 1);
            }
        }

        return $storedId;
    }

    public function completionTokenLimit(?User $user, string $storedId): int
    {
        $model = $this->rawCatalogForUser($user)[$storedId] ?? null;
        $reportedMaximum = (int) data_get($model, 'max_completion_tokens', 0);

        if ($reportedMaximum > 0) {
            return max(128, min($reportedMaximum, 8192));
        }

        $contextLength = (int) data_get($model, 'context_length', 0);
        if ($contextLength > 0) {
            return max(128, min((int) floor($contextLength / 4), 8192));
        }

        return 4096;
    }

    public function contextTokenLimit(?User $user, string $storedId): int
    {
        $model = $this->rawCatalogForUser($user)[$storedId] ?? null;

        return max(8192, (int) data_get($model, 'context_length', 32768));
    }

    public function storedId(string $provider, string $rawId): string
    {
        return $provider.':'.$rawId;
    }

    private function fetchVeniceModels(string $key): array
    {
        $response = $this->providerClient(config('services.venice.base_url'), $key)
            ->get('/models', ['type' => 'text']);

        if (! $response->successful()) {
            throw new RuntimeException('Venice rejected this API key or could not return its model catalog.');
        }

        return collect($response->json('data', []))
            ->filter(fn ($model) => is_array($model) && ($model['type'] ?? 'text') === 'text' && ! data_get($model, 'model_spec.offline', false))
            ->mapWithKeys(function (array $model) {
                $rawId = trim((string) ($model['id'] ?? ''));
                if ($rawId === '') {
                    return [];
                }

                $storedId = $this->storedId(self::VENICE, $rawId);

                return [$storedId => [
                    'id' => $storedId,
                    'raw_id' => $rawId,
                    'label' => (string) data_get($model, 'model_spec.name', $rawId),
                    'provider' => self::VENICE,
                    'provider_label' => 'Venice',
                    'description' => (string) data_get($model, 'model_spec.description', ''),
                    'context_length' => data_get($model, 'model_spec.availableContextTokens'),
                    'max_completion_tokens' => null,
                    'status' => 'active',
                ]];
            })
            ->all();
    }

    private function fetchOpenRouterModels(string $key): array
    {
        $client = $this->providerClient(config('services.openrouter.base_url'), $key)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-OpenRouter-Title' => config('app.name'),
            ]);

        $keyResponse = $client->get('/key');
        if (! $keyResponse->successful()) {
            throw new RuntimeException('OpenRouter rejected this API key.');
        }

        $response = $client->get('/models', ['output_modalities' => 'text']);

        if (! $response->successful()) {
            throw new RuntimeException('OpenRouter rejected this API key or could not return its model catalog.');
        }

        return collect($response->json('data', []))
            ->filter(fn ($model) => is_array($model) && in_array('text', data_get($model, 'architecture.output_modalities', ['text']), true))
            ->mapWithKeys(function (array $model) {
                $rawId = trim((string) ($model['id'] ?? ''));
                if ($rawId === '') {
                    return [];
                }

                $storedId = $this->storedId(self::OPENROUTER, $rawId);

                return [$storedId => [
                    'id' => $storedId,
                    'raw_id' => $rawId,
                    'label' => (string) ($model['name'] ?? $rawId),
                    'provider' => self::OPENROUTER,
                    'provider_label' => 'OpenRouter',
                    'description' => (string) ($model['description'] ?? ''),
                    'context_length' => $model['context_length'] ?? null,
                    'max_completion_tokens' => data_get($model, 'top_provider.max_completion_tokens'),
                    'status' => 'active',
                ]];
            })
            ->all();
    }

    private function providerClient(string $baseUrl, string $key): PendingRequest
    {
        return Http::baseUrl($baseUrl)
            ->withToken($key)
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 250, throw: false);
    }
}
