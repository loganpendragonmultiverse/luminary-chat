<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memory_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('chat_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('category')->default('general');
            $table->string('source')->default('manual');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_entries');
    }
};
