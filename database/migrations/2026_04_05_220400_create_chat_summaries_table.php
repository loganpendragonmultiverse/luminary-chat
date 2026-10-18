<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('summary')->nullable();
            $table->longText('manual_notes')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_summaries');
    }
};
