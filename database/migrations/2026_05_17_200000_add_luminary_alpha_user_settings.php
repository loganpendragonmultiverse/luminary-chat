<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url', 500)->nullable()->after('venice_api_key');
            }
            if (! Schema::hasColumn('users', 'memory_enabled')) {
                $table->boolean('memory_enabled')->default(true)->after('avatar_url');
            }
            if (! Schema::hasColumn('users', 'memory_auto_extract_enabled')) {
                $table->boolean('memory_auto_extract_enabled')->default(true)->after('memory_enabled');
            }
            if (! Schema::hasColumn('users', 'privacy_hide_email')) {
                $table->boolean('privacy_hide_email')->default(false)->after('memory_auto_extract_enabled');
            }
            if (! Schema::hasColumn('users', 'privacy_allow_diagnostics')) {
                $table->boolean('privacy_allow_diagnostics')->default(true)->after('privacy_hide_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['privacy_allow_diagnostics', 'privacy_hide_email', 'memory_auto_extract_enabled', 'memory_enabled', 'avatar_url'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
