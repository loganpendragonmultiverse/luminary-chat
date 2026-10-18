<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('openrouter_api_key')->nullable()->after('venice_api_key');
            $table->json('ai_model_catalog')->nullable()->after('openrouter_api_key');
            $table->timestamp('ai_models_synced_at')->nullable()->after('ai_model_catalog');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['openrouter_api_key', 'ai_model_catalog', 'ai_models_synced_at']);
        });
    }
};
