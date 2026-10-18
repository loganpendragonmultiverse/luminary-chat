<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Chat;
use App\Models\MemoryEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemoryEntryController extends Controller
{
    public function storeForChat(Request $request, Chat $chat): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);

        $chat->memoryEntries()->create($this->validated($request) + [
            'user_id' => $request->user()->id,
            'character_id' => $chat->character_id,
            'source' => 'manual',
        ]);

        return back()->with('status', 'Chat memory saved.');
    }

    public function storeForCharacter(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);

        $character->memoryEntries()->create($this->validated($request) + [
            'user_id' => $request->user()->id,
            'source' => 'manual',
        ]);

        return back()->with('status', 'Character memory saved.');
    }

    public function destroy(Request $request, MemoryEntry $memoryEntry): RedirectResponse
    {
        abort_unless($memoryEntry->user_id === $request->user()->id, 404);

        if ($memoryEntry->is_locked) {
            return back()->withErrors(['memory' => 'Locked memory must be unlocked before it can be deleted.']);
        }

        $memoryEntry->update(['status' => 'deleted']);

        return back()->with('status', 'Memory entry retired. Its revision history was preserved.');
    }

    public function update(Request $request, MemoryEntry $memoryEntry): RedirectResponse
    {
        abort_unless($memoryEntry->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'content' => ['required', 'string'],
            'status' => ['required', 'in:active,review,conflicted'],
            'importance' => ['required', 'numeric', 'between:0,1'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_locked' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $memoryEntry->revisions()->create([
            'prior_content' => $memoryEntry->content,
            'prior_status' => $memoryEntry->status,
            'reason' => $data['reason'] ?: 'Manual memory revision',
        ]);
        $memoryEntry->update([
            'content' => $data['content'],
            'status' => $data['status'],
            'importance' => $data['importance'],
            'is_pinned' => $request->boolean('is_pinned'),
            'is_locked' => $request->boolean('is_locked'),
        ]);

        return back()->with('status', 'Memory revised with history preserved.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string'],
            'category' => ['required', 'string', 'max:80'],
            'is_pinned' => ['nullable', 'boolean'],
            'importance' => ['nullable', 'numeric', 'between:0,1'],
        ]) + [
            'is_pinned' => $request->boolean('is_pinned'),
            'importance' => (float) $request->input('importance', 0.7),
            'confidence' => 1.0,
            'status' => 'active',
        ];
    }
}
