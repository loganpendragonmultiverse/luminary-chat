<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->string('template_key')->nullable()->after('slug');
            $table->boolean('is_system')->default(false)->after('template_key');
            $table->boolean('is_default')->default(false)->after('is_system');
            $table->json('persona_documents')->nullable()->after('lore_notes');
            $table->unique(['user_id', 'template_key']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name', 120);
            $table->text('description');
            $table->text('contents')->nullable();
            $table->text('expectations')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['character_id', 'slug']);
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('character_id')->constrained('rooms')->nullOnDelete();
        });

        Schema::table('memory_entries', function (Blueprint $table) {
            $table->decimal('confidence', 4, 3)->default(0.8)->after('source');
            $table->decimal('importance', 4, 3)->default(0.5)->after('confidence');
            $table->string('status', 30)->default('active')->after('importance');
            $table->boolean('is_locked')->default(false)->after('is_pinned');
            $table->foreignId('superseded_by_id')->nullable()->after('is_locked')->constrained('memory_entries')->nullOnDelete();
        });

        Schema::create('memory_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memory_entry_id')->constrained()->cascadeOnDelete();
            $table->longText('prior_content');
            $table->string('prior_status', 30);
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_revisions');
        Schema::table('memory_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('superseded_by_id');
            $table->dropColumn(['confidence', 'importance', 'status', 'is_locked']);
        });
        Schema::table('chats', fn (Blueprint $table) => $table->dropConstrainedForeignId('room_id'));
        Schema::dropIfExists('rooms');
        Schema::table('characters', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'template_key']);
            $table->dropColumn(['template_key', 'is_system', 'is_default', 'persona_documents']);
        });
    }
};
