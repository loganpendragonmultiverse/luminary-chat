<aside class="luminary-drawer" data-chat-sidebar>
    <div class="panel-heading split-heading">
        <div>
            <p class="eyebrow">Threads</p>
            <h2>Conversations</h2>
            <p class="panel-copy">Every thread is a separate conversation with Luminary.</p>
        </div>
        <span class="tiny-pill">{{ $chats->count() }} active</span>
    </div>

    @if ($luminary)
        <form method="POST" action="{{ route('chats.store', $luminary) }}" class="new-thread-form" data-single-submit>
            @csrf
            <input type="hidden" name="creation_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="title" value="Luminary Conversation">
            <button type="submit" class="primary-button">New Conversation</button>
        </form>
    @endif

    <form method="GET" action="{{ route('chats.index') }}" class="thread-search">
        <input type="text" name="q" value="{{ $query }}" placeholder="Search conversations">
    </form>

    <div class="chat-links">
        @forelse ($chats as $chat)
            <a href="{{ route('chats.show', $chat) }}" class="{{ optional($activeChat)->id === $chat->id ? 'chat-link active' : 'chat-link' }}">
                <span>{{ $chat->title }}</span>
                <small>{{ $chat->updated_at->diffForHumans() }}{{ $chat->is_pinned ? ' | pinned' : '' }}</small>
            </a>
        @empty
            <p class="empty-note">No conversations yet.</p>
        @endforelse
    </div>

    @if ($archivedChats->isNotEmpty())
        <details class="archived-thread-list">
            <summary>Archived conversations</summary>
        <div class="log-stack compact-form">
            @foreach ($archivedChats as $chat)
                <article class="memory-card">
                    <div class="card-heading">
                        <div>
                            <h4>{{ $chat->title }}</h4>
                            <p>{{ $chat->archived_at ? 'Archived '.$chat->archived_at->diffForHumans() : 'Archived' }}</p>
                        </div>
                        <form method="POST" action="{{ route('chats.restore', $chat) }}" data-async="chat-shell">
                            @csrf
                            <button type="submit" class="ghost-button">Restore</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
        </details>
    @endif
</aside>
