<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->boolean('randomize_initial_chats')->default(false)->after('companion_form');
            $table->string('initial_chat_style', 30)->default('realistic')->after('randomize_initial_chats');
        });

        $luminaries = DB::table('characters')
            ->where('template_key', 'luminary')
            ->orWhere(function ($query) {
                $query->whereNull('template_key')->where('slug', 'like', 'luminary-%');
            })
            ->get(['id', 'user_id']);
        $preferences = DB::table('users')
            ->whereIn('id', $luminaries->pluck('user_id'))
            ->get(['id', 'randomize_initial_chats', 'initial_chat_style'])
            ->keyBy('id');

        foreach ($luminaries as $luminary) {
            $preference = $preferences->get($luminary->user_id);
            if ($preference) {
                DB::table('characters')->where('id', $luminary->id)->update([
                    'randomize_initial_chats' => (bool) $preference->randomize_initial_chats,
                    'initial_chat_style' => in_array($preference->initial_chat_style, ['realistic', 'fantasy'], true)
                        ? $preference->initial_chat_style
                        : 'realistic',
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['initial_chat_style', 'randomize_initial_chats']);
        });
    }
};
