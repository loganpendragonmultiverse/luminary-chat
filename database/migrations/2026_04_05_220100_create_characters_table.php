<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description');
            $table->text('personality');
            $table->text('scenario');
            $table->longText('first_message');
            $table->longText('long_bio')->nullable();
            $table->string('writing_style')->nullable();
            $table->string('tone')->nullable();
            $table->string('tags')->nullable();
            $table->longText('lore_notes')->nullable();
            $table->string('default_model');
            $table->decimal('temperature', 3, 2)->default(0.9);
            $table->unsignedInteger('max_tokens')->default(900);
            $table->decimal('top_p', 3, 2)->default(0.95);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
