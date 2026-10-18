<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatFolderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        ChatFolder::create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]) + [
            'user_id' => $request->user()->id,
            'is_favorite' => false,
        ]);

        return back()->with('status', 'Chat folder created.');
    }

    public function update(Request $request, ChatFolder $chatFolder): RedirectResponse
    {
        abort_unless($chatFolder->user_id === $request->user()->id, 404);

        $chatFolder->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('status', 'Chat folder updated.');
    }

    public function destroy(Request $request, ChatFolder $chatFolder): RedirectResponse
    {
        abort_unless($chatFolder->user_id === $request->user()->id, 404);

        Chat::query()
            ->where('user_id', $request->user()->id)
            ->where('chat_folder_id', $chatFolder->id)
            ->update(['chat_folder_id' => null]);

        $chatFolder->delete();

        return back()->with('status', 'Chat folder deleted.');
    }

    public function assign(Request $request, Chat $chat): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);

        $folderId = $request->validate([
            'chat_folder_id' => ['nullable', 'integer'],
        ])['chat_folder_id'] ?? null;

        if ($folderId) {
            abort_unless(ChatFolder::query()->where('user_id', $request->user()->id)->whereKey($folderId)->exists(), 404);
        }

        $chat->update([
            'chat_folder_id' => $folderId,
        ]);

        return back()->with('status', 'Chat folder updated.');
    }

    public function archive(Request $request, Chat $chat): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);

        $chat->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return back()->with('status', 'Chat archived.');
    }

    public function restore(Request $request, Chat $chat): RedirectResponse
    {
        abort_unless($chat->user_id === $request->user()->id, 404);

        $chat->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return back()->with('status', 'Chat restored.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'chat_ids' => ['required', 'array', 'min:1'],
            'chat_ids.*' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:archive,restore,move'],
            'chat_folder_id' => ['nullable', 'integer'],
        ]);

        $query = Chat::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $data['chat_ids']);

        if ($data['action'] === 'archive') {
            $query->update(['is_archived' => true, 'archived_at' => now()]);

            return back()->with('status', 'Selected chats archived.');
        }

        if ($data['action'] === 'restore') {
            $query->update(['is_archived' => false, 'archived_at' => null]);

            return back()->with('status', 'Selected chats restored.');
        }

        $folderId = $data['chat_folder_id'] ?? null;

        if ($folderId) {
            abort_unless(ChatFolder::query()->where('user_id', $request->user()->id)->whereKey($folderId)->exists(), 404);
        }

        $query->update(['chat_folder_id' => $folderId]);

        return back()->with('status', 'Selected chats moved.');
    }

    public function favorite(Request $request, ChatFolder $chatFolder): RedirectResponse
    {
        abort_unless($chatFolder->user_id === $request->user()->id, 404);

        $chatFolder->update([
            'is_favorite' => ! $chatFolder->is_favorite,
        ]);

        return back()->with('status', $chatFolder->is_favorite ? 'Folder favorited.' : 'Folder unfavorited.');
    }
}
