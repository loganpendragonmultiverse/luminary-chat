<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function update(Request $request, Chat $chat): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);

        $chat->summary()->updateOrCreate(
            ['chat_id' => $chat->id],
            $request->validate([
                'summary' => ['nullable', 'string'],
                'manual_notes' => ['nullable', 'string'],
            ])
        );

        return back()->with('status', 'Memory summary updated.');
    }
}
