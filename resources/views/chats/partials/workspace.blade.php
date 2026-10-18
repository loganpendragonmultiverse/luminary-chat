<section class="panel conversation-panel" data-chat-workspace data-active-chat-id="{{ optional($activeChat)->id }}">
    @if ($activeChat)
        <div class="conversation-header">
            <div class="conversation-title-block">
                <div class="luminary-avatar" aria-hidden="true">
                    @if (auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="">
                    @endif
                </div>
                <p class="eyebrow">Luminary is online</p>
                <h2>{{ $activeChat->title }}</h2>
                <p class="panel-copy">One companion, separate conversation threads.</p>
            </div>
            <div class="button-row">
                @if (! $activeChat->is_archived)
                    <form method="POST" action="{{ route('chats.archive', $activeChat) }}" data-async="chat-shell">
                        @csrf
                        <button type="submit" class="ghost-button">Archive</button>
                    </form>
                @endif
                <a href="{{ route('chats.export', $activeChat) }}" class="nav-link subtle-link">Export</a>
            </div>
        </div>

        <div class="conversation-layout single-chat-layout">
            <div class="message-column">
                <div class="conversation-toolbar">
                    <button type="button" class="chip-button" data-autoscroll-toggle>Auto-scroll: on</button>
                    <button type="button" class="chip-button" data-scroll-bottom>Newest</button>
                </div>

                <div class="message-stream panel-stack" data-panel-section="conversation">
                    @php
                        $latestMessageId = optional($activeChat->messages->last())->id;
                        $latestAssistantId = optional($activeChat->messages->where('role', 'assistant')->last())->id;
                    @endphp
                    @foreach ($activeChat->messages as $message)
                        @include('chats.partials.message', ['message' => $message, 'latestMessageId' => $latestMessageId, 'latestAssistantId' => $latestAssistantId])
                    @endforeach
                </div>

                <form method="POST" action="{{ route('messages.store', $activeChat) }}" class="stack-form composer-card" data-async="chat-shell" data-chat-compose-form>
                    @csrf
                    <label>
                        <span>Message Luminary</span>
                        <textarea id="chat-composer" name="content" rows="4" required placeholder="Talk to Luminary..."></textarea>
                    </label>
                    <div class="button-row">
                        <button type="submit" class="primary-button" data-submit-label="Send">Send</button>
                        <span class="composer-hint">Shortcut: Ctrl/Cmd + Enter to send</span>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="empty-state luminary-welcome-state">
            <div class="luminary-avatar large" aria-hidden="true">
                @if (auth()->user()->avatar_url)
                    <img src="{{ auth()->user()->avatar_url }}" alt="">
                @endif
            </div>
            <p class="eyebrow">Neural core ready</p>
            <h2>Hello, I am Luminary.</h2>
            <p class="panel-copy">Start a new conversation and I will shape the tone around what you need today.</p>
        </div>
    @endif
</section>
