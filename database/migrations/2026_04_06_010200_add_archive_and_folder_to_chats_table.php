<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->foreignId('chat_folder_id')->nullable()->after('character_id')->constrained('chat_folders')->nullOnDelete();
            $table->boolean('is_archived')->default(false)->after('is_pinned');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chat_folder_id');
            $table->dropColumn(['is_archived', 'archived_at']);
        });
    }
};
