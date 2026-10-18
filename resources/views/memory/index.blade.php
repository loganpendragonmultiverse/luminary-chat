@extends('layouts.app', ['title' => 'Memory | Luminary Chat'])

@section('content')
    <main class="stack-layout">
        <section class="panel">
            <div class="panel-heading split-heading">
                <div>
                    <p class="eyebrow">Memory Index</p>
                    <h2>Memory</h2>
                    <p class="panel-copy">Browse saved facts across chats and characters, whether they were entered manually or extracted automatically.</p>
                </div>
                <span class="tiny-pill">{{ $entries->count() }} entries</span>
            </div>

            <form method="GET" action="{{ route('admin.memory.index') }}" class="stack-form compact-form">
                <div class="triple-grid">
                    <label>
                        <span>Scope</span>
                        <select name="scope">
                            <option value="all" @selected($scope === 'all')>All</option>
                            <option value="chat" @selected($scope === 'chat')>Chat</option>
                            <option value="character" @selected($scope === 'character')>Character</option>
                        </select>
                    </label>
                    <label>
                        <span>Source</span>
                        <select name="source">
                            <option value="all" @selected($source === 'all')>All</option>
                            <option value="manual" @selected($source === 'manual')>Manual</option>
                            <option value="auto_extract" @selected($source === 'auto_extract')>Auto Extract</option>
                        </select>
                    </label>
                    <label>
                        <span>Search</span>
                        <input type="text" name="q" value="{{ $query }}" placeholder="Search title, content, category, chat, character">
                    </label>
                </div>
                <button type="submit" class="primary-button">Filter Memory</button>
            </form>

            <div class="button-row compact-form">
                <form method="POST" action="{{ route('admin.memory.dedupe') }}">
                    @csrf
                    <button type="submit" class="ghost-button">Auto Dedupe Exact Matches</button>
                </form>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.memory.bulk') }}" class="stack-form">
            @csrf
            <section class="panel nested-panel">
                <h3>Bulk Memory Tools</h3>
                <div class="triple-grid">
                    <label>
                        <span>Action</span>
                        <select name="action" required>
                            <option value="merge">Merge Selected</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                    </label>
                    <label>
                        <span>Merged Title</span>
                        <input type="text" name="merged_title" placeholder="Optional when merging">
                    </label>
                    <label>
                        <span>Merged Category</span>
                        <input type="text" name="merged_category" placeholder="Optional when merging">
                    </label>
                </div>
                <button type="submit" class="primary-button">Run Memory Action</button>
            </section>

            <section class="comparison-grid">
                @forelse ($entries as $entry)
                    <article class="panel nested-panel comparison-card">
                        <div class="card-heading">
                            <div>
                                <h3>{{ $entry->title }}</h3>
                                <p>{{ $entry->category }}{{ $entry->is_pinned ? ' | pinned' : '' }}{{ $entry->source ? ' | '.$entry->source : '' }}</p>
                            </div>
                            <label class="checkbox-row">
                                <input type="checkbox" name="memory_ids[]" value="{{ $entry->id }}">
                                <span>Select</span>
                            </label>
                        </div>
                        <div class="message-content">{{ $entry->content }}</div>
                        <p class="meta-copy">
                            {{ $entry->chat ? 'Chat: '.$entry->chat->title : 'Chat: none' }}
                            {{ $entry->character ? ' | Character: '.$entry->character->name : '' }}
                        </p>
                        <p class="meta-copy">Use bulk actions above to merge or delete selected memory entries.</p>
                    </article>
                @empty
                    <p class="empty-note">No memory entries match the current filters.</p>
                @endforelse
            </section>
        </form>
    </main>
@endsection
