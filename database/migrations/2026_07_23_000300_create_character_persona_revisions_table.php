<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_persona_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('file_name', 180);
            $table->unsignedInteger('version');
            $table->longText('prior_content');
            $table->longText('content');
            $table->string('change_note')->nullable();
            $table->timestamps();
            $table->unique(['character_id', 'file_name', 'version'], 'persona_revision_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_persona_revisions');
    }
};
