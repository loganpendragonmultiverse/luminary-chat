<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            if (! Schema::hasColumn('characters', 'companion_form')) {
                $table->string('companion_form', 40)->default('confidant')->after('tone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            if (Schema::hasColumn('characters', 'companion_form')) {
                $table->dropColumn('companion_form');
            }
        });
    }
};
