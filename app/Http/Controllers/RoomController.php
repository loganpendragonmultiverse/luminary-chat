<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Room;
use App\Services\CharacterRosterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function restore(Request $request, Character $character, CharacterRosterService $roster): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id && $character->is_system, 404);
        $roster->restoreRooms($character);

        return back()->with('status', $character->name.' rooms restored to the original defaults.');
    }

    public function store(Request $request, Character $character): RedirectResponse
    {
        abort_unless($character->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string'],
            'contents' => ['nullable', 'string'],
            'expectations' => ['nullable', 'string'],
        ]);

        $character->rooms()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'sort_order' => ((int) $character->rooms()->max('sort_order')) + 10,
            'is_default' => ! $character->rooms()->exists(),
        ]);

        return back()->with('status', 'Room added to '.$character->name.'.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string'],
            'contents' => ['nullable', 'string'],
            'expectations' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            $room->character->rooms()->update(['is_default' => false]);
        }

        $room->update($data + ['is_default' => $request->boolean('is_default')]);

        return back()->with('status', 'Room updated.');
    }

    public function destroy(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->user_id === $request->user()->id, 404);

        if ($room->is_default || $room->chats()->exists()) {
            return back()->withErrors(['room' => 'Choose another default and move existing chats before deleting this room.']);
        }

        $room->delete();

        return back()->with('status', 'Room deleted.');
    }
}
