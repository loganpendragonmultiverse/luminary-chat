<?php

namespace App\Http\Controllers;

use App\Models\MemoryEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemoryController extends Controller
{
    public function index(Request $request): View
    {
        $scope = (string) $request->input('scope', 'all');
        $source = (string) $request->input('source', 'all');
        $query = trim((string) $request->input('q'));

        $entries = MemoryEntry::query()
            ->where('user_id', $request->user()->id)
            ->with(['character', 'chat'])
            ->when($scope === 'chat', fn ($builder) => $builder->whereNotNull('chat_id'))
            ->when($scope === 'character', fn ($builder) => $builder->whereNull('chat_id')->whereNotNull('character_id'))
            ->when($source !== 'all', fn ($builder) => $builder->where('source', $source))
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($nested) use ($query) {
                    $nested->where('title', 'like', '%'.$query.'%')
                        ->orWhere('content', 'like', '%'.$query.'%')
                        ->orWhere('category', 'like', '%'.$query.'%')
                        ->orWhereHas('character', fn ($character) => $character->where('name', 'like', '%'.$query.'%'))
                        ->orWhereHas('chat', fn ($chat) => $chat->where('title', 'like', '%'.$query.'%'));
                });
            })
            ->latest('is_pinned')
            ->latest('updated_at')
            ->get();

        return view('memory.index', [
            'entries' => $entries,
            'scope' => $scope,
            'source' => $source,
            'query' => $query,
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'memory_ids' => ['required', 'array', 'min:1'],
            'memory_ids.*' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:delete,merge'],
            'merged_title' => ['nullable', 'string', 'max:120'],
            'merged_category' => ['nullable', 'string', 'max:80'],
        ]);

        $entries = MemoryEntry::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $data['memory_ids'])
            ->get();

        if ($entries->isEmpty()) {
            return back()->withErrors(['memory_ids' => 'No memory entries selected.']);
        }

        if ($data['action'] === 'delete') {
            MemoryEntry::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('id', $data['memory_ids'])
                ->delete();

            return back()->with('status', 'Selected memory entries deleted.');
        }

        $primary = $entries->sortBy('id')->first();
        $combinedContent = $entries->pluck('content')->unique()->implode("\n\n");

        $primary->update([
            'title' => $data['merged_title'] ?: $primary->title,
            'category' => $data['merged_category'] ?: $primary->category,
            'content' => $combinedContent,
            'is_pinned' => $entries->contains(fn ($entry) => $entry->is_pinned),
        ]);

        MemoryEntry::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $entries->pluck('id')->reject(fn ($id) => $id === $primary->id))
            ->delete();

        return back()->with('status', 'Selected memory entries merged.');
    }

    public function dedupe(Request $request): RedirectResponse
    {
        $entries = MemoryEntry::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($entry) => implode('|', [
                $entry->chat_id ?: 'none',
                $entry->character_id ?: 'none',
                $entry->title,
                $entry->content,
                $entry->category,
            ]));

        $deleted = 0;

        foreach ($entries as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $keep = $group->sortByDesc(fn ($entry) => [$entry->is_pinned, $entry->updated_at])->first();
            $toDelete = $group->pluck('id')->reject(fn ($id) => $id === $keep->id);
            $deleted += $toDelete->count();

            MemoryEntry::query()->whereIn('id', $toDelete)->delete();
        }

        return back()->with('status', $deleted > 0 ? $deleted.' duplicate memory entries removed.' : 'No duplicate memory entries found.');
    }
}
