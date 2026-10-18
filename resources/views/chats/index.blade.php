@extends('layouts.app', ['title' => $activeCharacter->name.' Chat | Luminary', 'immersive' => true])

@section('content')
    <main class="original-chat-app" data-chat-shell>
        <aside class="original-chat-sidebar" data-chat-sidebar>
            <div class="original-sidebar-header"><button type="button" class="original-menu-button mobile-sidebar-close" data-sidebar-toggle aria-label="Close conversation list">×</button>
                <div class="original-sidebar-brand">
                    <span class="original-logo-mark" aria-hidden="true">{{ strtoupper(substr($activeCharacter->name, 0, 1)) }}</span>
                    <span><strong>{{ $activeCharacter->name }}</strong><small>Current character</small></span>
                </div>
            </div>

            <details class="new-chat-config">
                <summary>+ New {{ $activeCharacter->name }} Conversation</summary>
                <form method="POST" action="{{ route('chats.store', $activeCharacter) }}" class="stack-form compact-form" data-single-submit>
                    @csrf
                    <input type="hidden" name="creation_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <label><span>Title</span><input type="text" name="title" value="{{ $activeCharacter->name }} Conversation" required></label>
                    <label><span>Model</span><select name="model">
                        <option value="__default__">Default · {{ $modelOptions[$defaultCharacterModel]['label'] ?? $defaultCharacterModel }}</option>
                        @include('chats.partials.model-option-groups', ['selectedModel' => null])
                    </select></label>
                    <label class="checkbox-row"><input type="checkbox" name="set_as_default" value="1"><span>Set to default</span></label>
                    <label><span>Room</span><select name="room_id">
                        @foreach ($activeCharacter->rooms as $room)<option value="{{ $room->id }}" @selected($room->is_default)>{{ $room->name }}{{ $room->is_default ? ' · Default' : '' }}</option>@endforeach
                    </select></label>
                    <label><span>Opening scene</span><select name="initial_chat_mode">
                        <option value="empty">Empty · No initial scene</option>
                        <option value="default">Character default · {{ $activeCharacter->randomize_initial_chats ? 'Randomized' : 'Normal' }}</option>
                        <option value="normal">Normal · Use saved opening</option>
                        <option value="randomized">Randomized · Generate a fresh scenario</option>
                    </select></label>
                    <button type="submit" class="primary-button">Start Conversation</button>
                </form>
            </details>

            <form method="GET" action="{{ route('characters.chats', $activeCharacter) }}" class="original-chat-search">
                <input type="text" name="q" value="{{ $query }}" placeholder="Search {{ $activeCharacter->name }} conversations">
            </form>

            <div class="original-conversation-list">
                @forelse ($chats as $chat)
                    <article id="conversation-{{ $chat->id }}" class="{{ optional($activeChat)->id === $chat->id ? 'original-conversation-row active' : 'original-conversation-row' }}">
                        <div class="conversation-row-main">
                            <a href="{{ route('chats.show', $chat) }}" class="original-conversation">
                                <span class="original-conversation-top"><strong>{{ $chat->title }}</strong>@if(optional($activeChat)->id === $chat->id)<i aria-hidden="true"></i>@endif</span>
                                <small class="original-conversation-identity">{{ $activeCharacter->name }} · {{ $modelOptions[$chat->model]['label'] ?? $chat->model }}</small>
                                <small>{{ $chat->room?->name ?? 'No room' }} · {{ optional($chat->updated_at)->diffForHumans() }}</small>
                            </a>
                            <a href="{{ ($activeChat ? route('chats.show', $activeChat) : route('characters.chats', $activeCharacter)).(optional($settingsChat)->id === $chat->id ? '' : '?settings_chat='.$chat->id.'#conversation-settings-'.$chat->id) }}" class="conversation-settings-trigger" aria-label="{{ optional($settingsChat)->id === $chat->id ? 'Close' : 'Open' }} settings for {{ $chat->title }}" aria-expanded="{{ optional($settingsChat)->id === $chat->id ? 'true' : 'false' }}">⚙</a>
                        </div>
                        @if(optional($settingsChat)->id === $chat->id)
                            <div class="conversation-row-settings" id="conversation-settings-{{ $chat->id }}">
                                <p class="conversation-settings-summary">{{ $modelOptions[$settingsChat->model]['label'] ?? $settingsChat->model }} · {{ $settingsChat->room?->name ?? 'No room' }}</p>
                                <form method="POST" action="{{ route('chats.update', $settingsChat) }}" class="chat-control-form">
                                    @csrf @method('PUT')
                                    <label><span>Title</span><input type="text" name="title" value="{{ $settingsChat->title }}" required maxlength="120"></label>
                                    <label><span>Model for this chat</span><select name="model">
                                        <option value="__default__" @selected($settingsChat->model === $defaultCharacterModel)>Default · {{ $modelOptions[$defaultCharacterModel]['label'] ?? $defaultCharacterModel }}</option>
                                        @if(!isset($modelOptions[$settingsChat->model]) && $settingsChat->model !== $defaultCharacterModel)<option value="{{ $settingsChat->model }}" selected>Current · {{ $settingsChat->model }} (not currently available)</option>@endif
                                        @include('chats.partials.model-option-groups', ['selectedModel' => $settingsChat->model])
                                    </select></label>
                                    <label class="checkbox-row"><input type="checkbox" name="set_as_default" value="1"><span>Set to default</span></label>
                                    <label><span>Current room</span><select name="room_id">@foreach($activeCharacter->rooms as $room)<option value="{{ $room->id }}" @selected($settingsChat->room_id === $room->id)>{{ $room->name }}</option>@endforeach</select></label>
                                    <input type="hidden" name="is_pinned" value="0">
                                    <label class="checkbox-row"><input type="checkbox" name="is_pinned" value="1" @checked($settingsChat->is_pinned)><span>Pin conversation</span></label>
                                    <button type="submit" class="secondary-button">Save Chat Settings</button>
                                </form>
                                <form method="POST" action="{{ route('chats.destroy', $settingsChat) }}" class="conversation-delete-form" onsubmit="return confirm('Delete this whole conversation? This cannot be undone.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="conversation-delete-button">Delete Chat</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="original-empty-note">No {{ $activeCharacter->name }} conversations yet.</p>
                @endforelse
            </div>

            @if ($archivedChats->isNotEmpty())
                <details class="original-archive-list"><summary>Archived conversations</summary>
                    @foreach ($archivedChats as $chat)<form method="POST" action="{{ route('chats.restore', $chat) }}">@csrf<button type="submit">{{ $chat->title }}</button></form>@endforeach
                </details>
            @endif

            <div class="original-user-profile">
                <div class="original-user-avatar">@if(auth()->user()->avatar_url)<img src="{{ auth()->user()->avatar_url }}" alt="">@else{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}@endif</div>
                <div><strong>{{ auth()->user()->name ?: 'User' }}</strong><small>{{ $activeCharacter->name }} instance</small></div>
                <a href="{{ route('settings.index') }}" aria-label="Settings">⚙</a>
            </div>
        </aside>

        <section class="original-chat-main" data-chat-workspace data-active-chat-id="{{ optional($activeChat)->id }}" data-active-character-name="{{ $activeCharacter->name }}" data-user-display-name="{{ $userDisplayName }}">
            <header class="original-chat-header">
                <div class="original-chat-title">
                    <button type="button" class="original-menu-button" aria-label="Toggle conversation list" data-sidebar-toggle>☰</button>
                    <span class="original-logo-mark filled" aria-hidden="true">{{ strtoupper(substr($activeCharacter->name, 0, 1)) }}</span>
                    <div><h1>{{ $activeCharacter->name }}</h1><p><span></span> Online</p></div>
                    <a href="{{ route('characters.index') }}" class="change-character-action" aria-label="Choose a different character">Change character</a>
                </div>
            </header>

            @if ($activeChat)
                <div class="original-message-stream"><div class="original-message-stack">
                    @php($latestMessageId = optional($activeChat->messages->last())->id)
                    @php($latestAssistantId = optional($activeChat->messages->where('role', 'assistant')->last())->id)
                    @foreach($activeChat->messages as $message)@include('chats.partials.message', ['message' => $message, 'latestMessageId' => $latestMessageId, 'latestAssistantId' => $latestAssistantId, 'laterMessageCount' => $activeChat->messages->where('id', '>', $message->id)->count()])@endforeach
                </div></div>
            @else
                <div class="original-chat-welcome">
                    <div class="character-welcome-avatar {{ $activeCharacter->template_key }}">{{ strtoupper(substr($activeCharacter->name, 0, 1)) }}</div>
                    <h2>Hello, I'm {{ $activeCharacter->name }}</h2>
                    <p>{{ $activeCharacter->short_description }}</p>
                    <div class="original-prompt-grid">
                        @foreach([['Normal Chat', 'Use the saved character defaults.', 'normal'], ['Randomized Chat', 'Generate a fresh opening scene in the default room.', 'randomized']] as [$title, $prompt, $mode])
                            <form method="POST" action="{{ route('chats.store', $activeCharacter) }}" data-single-submit>@csrf<input type="hidden" name="creation_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="title" value="{{ $activeCharacter->name }} · {{ $title }}"><input type="hidden" name="initial_chat_mode" value="{{ $mode }}"><button type="submit"><strong>{{ $title }}</strong><span>{{ $prompt }}</span></button></form>
                        @endforeach
                    </div>
                    <div class="original-capabilities"><span>Isolated Memory</span><span>Room Awareness</span><span>Per-chat Models</span></div>
                </div>
            @endif

            @if ($activeChat)
                <div class="original-chat-input"><form method="POST" action="{{ route('messages.store', $activeChat) }}" data-async="chat-shell" data-chat-compose-form>@csrf
                    <div class="original-composer-box"><textarea id="chat-composer" name="content" rows="1" required placeholder="Message {{ $activeCharacter->name }}..."></textarea><button type="submit" aria-label="Send message" data-submit-label="Send">➤</button></div>
                    <p class="original-composer-status" data-composer-status role="status" aria-live="polite"></p>
                    <div class="original-input-hints"><span>Enter to send · Shift+Enter for a new line</span><span><i></i> {{ $activeCharacter->name }} Online</span></div>
                </form></div>
            @endif
        </section>
    </main>
@endsection
