<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Collection;

class AppSettingsService
{
    public function group(string $group): Collection
    {
        return AppSetting::query()
            ->where('group', $group)
            ->get()
            ->pluck('value', 'key');
    }

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        return AppSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public function setMany(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value)]
            );
        }
    }

    public function defaults(): array
    {
        return [
            'default_model' => $this->get('app', 'default_model', ModelCatalogService::RECOMMENDED_MODEL),
            'default_image_model' => $this->get('app', 'default_image_model', config('pendragon.default_image_model', 'chroma')),
            'default_temperature' => (float) $this->get('app', 'default_temperature', config('pendragon.default_temperature', 0.9)),
            'default_max_tokens' => (int) $this->get('app', 'default_max_tokens', config('pendragon.default_max_tokens', 900)),
            'default_top_p' => (float) $this->get('app', 'default_top_p', config('pendragon.default_top_p', 0.95)),
            'summary_message_limit' => (int) $this->get('app', 'summary_message_limit', config('pendragon.summary_message_limit', 12)),
            'memory_extraction_limit' => (int) $this->get('app', 'memory_extraction_limit', config('pendragon.memory_extraction_limit', 3)),
            'venice_admin_key' => $this->get('app', 'venice_admin_key', env('VENICE_ADMIN_KEY')),
        ];
    }
}
