<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('pinned_memory');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('meta');
        });

        Schema::create('chat_turn_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 50);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_turn_logs');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('edited_at');
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('is_pinned');
        });
    }
};
