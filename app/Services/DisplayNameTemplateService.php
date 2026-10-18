<?php

namespace App\Services;

use App\Models\User;

class DisplayNameTemplateService
{
    public function resolve(?string $template, ?User $user): string
    {
        return str_replace('{{display_name}}', $this->displayName($user), (string) $template);
    }

    public function displayName(?User $user): string
    {
        $name = preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', (string) $user?->name) ?? '';
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return $name !== '' ? mb_substr($name, 0, 80) : 'the user';
    }
}
