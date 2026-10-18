<?php

use App\Models\Character;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Character::query()
            ->with('versions')
            ->get()
            ->each(function (Character $character) {
                if ($character->versions->isNotEmpty()) {
                    return;
                }

                $character->versions()->create([
                    'user_id' => $character->user_id,
                    'label' => 'Backfilled initial version',
                    'snapshot' => $character->snapshotPayload(),
                ]);
            });
    }

    public function down(): void
    {
    }
};
