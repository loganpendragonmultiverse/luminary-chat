<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'randomize_initial_chats')) {
                $table->boolean('randomize_initial_chats')->default(true)->after('context_depth');
            }

            if (! Schema::hasColumn('users', 'initial_chat_style')) {
                $table->string('initial_chat_style', 30)->default('realistic')->after('randomize_initial_chats');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['initial_chat_style', 'randomize_initial_chats'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
