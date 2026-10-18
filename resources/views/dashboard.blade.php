@extends('layouts.app', ['title' => 'Luminary Chat'])

@section('content')
    <main class="app-grid">
        <aside class="panel sidebar-panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Characters</p>
                    <h2>Roster</h2>
                </div>
            </div>

            <form method="POST" action="{{ route('characters.store') }}" class="stack-form">
                @csrf
                <h3>Create Character</h3>
                <label><span>Name</span><input type="text" name="name" required></label>
                <label><span>Short Description</span><input type="text" name="short_description" required></label>
                <label><span>Personality</span><textarea name="personality" rows="3" required></textarea></label>
                <label><span>Scenario</span><textarea name="scenario" rows="3" required></textarea></label>
                <label><span>First Message</span><textarea name="first_message" rows="4" required></textarea></label>
                <label><span>Long Bio</span><textarea name="long_bio" rows="3"></textarea></label>
                <label><span>Writing Style</span><input type="text" name="writing_style"></label>
                <label><span>Tone</span><input type="text" name="tone"></label>
                <label><span>Tags</span><input type="text" name="tags" placeholder="fantasy, companion, roleplay"></label>
                <label><span>Lore Notes</span><textarea name="lore_notes" rows="3"></textarea></label>
                <label>
                    <span>Default Model</span>
                    <select name="default_model" required>
                        @foreach ($modelOptions as $modelId => $model)
                            <option value="{{ $modelId }}" @disabled(($model['status'] ?? 'active') !== 'active')>
                                {{ $model['label'] }}@if (($model['status'] ?? 'active') !== 'active') (planned)@endif
                            </option>
                        @endforeach
                    </select>
                </label>
                <div class="triple-grid">
                    <label><span>Temp</span><input type="number" name="temperature" step="0.01" min="0" max="2" value="0.90" required></label>
                    <label><span>Max Tokens</span><input type="number" name="max_tokens" min="128" max="4096" value="900" required></label>
                    <label><span>Top P</span><input type="number" name="top_p" step="0.01" min="0" max="1" value="0.95" required></label>
                </div>
                <button type="submit" class="primary-button">Save Character</button>
            </form>

            <div class="character-list">
                @forelse ($characters as $character)
                    <section class="character-card">
                        <div class="card-heading">
                            <div>
                                <h3>{{ $character->name }}</h3>
                                <p>{{ $character->short_description }}</p>
                            </div>
                            <span class="tiny-pill">{{ $modelOptions[$character->default_model]['label'] ?? $character->default_model }}</span>
                        </div>

                        <div class="button-row compact-form">
                            <form method="POST" action="{{ route('chats.store', $character) }}" data-single-submit>
                                @csrf
                                <input type="hidden" name="creation_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                <input type="hidden" name="title" value="{{ $character->name }} Session">
                                <button type="submit" class="secondary-button">Start New Chat</button>
                            </form>
                            <form method="POST" action="{{ route('characters.duplicate', $character) }}">
                                @csrf
                                <button type="submit" class="ghost-button">Duplicate</button>
                            </form>
                        </div>

                        <details class="editor-toggle">
                            <summary>Edit Character</summary>
                            <form method="POST" action="{{ route('characters.update', $character) }}" class="stack-form compact-form">
                                @csrf
                                @method('PUT')
                                <label><span>Name</span><input type="text" name="name" value="{{ $character->name }}" required></label>
                                <label><span>Short Description</span><input type="text" name="short_description" value="{{ $character->short_description }}" required></label>
                                <label><span>Personality</span><textarea name="personality" rows="3" required>{{ $character->personality }}</textarea></label>
                                <label><span>Scenario</span><textarea name="scenario" rows="3" required>{{ $character->scenario }}</textarea></label>
                                <label><span>First Message</span><textarea name="first_message" rows="4" required>{{ $character->first_message }}</textarea></label>
                                <label><span>Long Bio</span><textarea name="long_bio" rows="3">{{ $character->long_bio }}</textarea></label>
                                <label><span>Writing Style</span><input type="text" name="writing_style" value="{{ $character->writing_style }}"></label>
                                <label><span>Tone</span><input type="text" name="tone" value="{{ $character->tone }}"></label>
                                <label><span>Tags</span><input type="text" name="tags" value="{{ $character->tags }}"></label>
                                <label><span>Lore Notes</span><textarea name="lore_notes" rows="3">{{ $character->lore_notes }}</textarea></label>
                                <label>
                                    <span>Default Model</span>
                                    <select name="default_model" required>
                                        @foreach ($modelOptions as $modelId => $model)
                                            <option value="{{ $modelId }}" @selected($character->default_model === $modelId) @disabled(($model['status'] ?? 'active') !== 'active')>
                                                {{ $model['label'] }}@if (($model['status'] ?? 'active') !== 'active') (planned)@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="triple-grid">
                                    <label><span>Temp</span><input type="number" name="temperature" step="0.01" min="0" max="2" value="{{ $character->temperature }}" required></label>
                                    <label><span>Max Tokens</span><input type="number" name="max_tokens" min="128" max="4096" value="{{ $character->max_tokens }}" required></label>
                                    <label><span>Top P</span><input type="number" name="top_p" step="0.01" min="0" max="1" value="{{ $character->top_p }}" required></label>
                                </div>
                                <button type="submit" class="secondary-button">Update</button>
                            </form>
                            <form method="POST" action="{{ route('characters.destroy', $character) }}" class="compact-form" onsubmit="return confirm('Delete this character and all associated chats?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="danger-button">Delete Character</button>
                            </form>
                        </details>

                        <div class="chat-links">
                            @forelse ($character->chats as $chat)
                                <a href="{{ route('chats.show', $chat) }}" class="{{ optional($activeChat)->id === $chat->id ? 'chat-link active' : 'chat-link' }}">
                                    <span>{{ $chat->title }}</span>
                                    <small>{{ $chat->updated_at->diffForHumans() }}@if($chat->is_pinned) • pinned @endif</small>
                                </a>
                            @empty
                                <p class="empty-note">No chats yet.</p>
                            @endforelse
                        </div>
                    </section>
                @empty
                    <p class="empty-note">Create the first character to start the lab.</p>
                @endforelse
            </div>
        </aside>

        <section class="panel conversation-panel">
            @if ($activeChat)
                <div class="conversation-header">
                    <div>
                        <p class="eyebrow">Active Thread</p>
                        <h2>{{ $activeChat->title }}</h2>
                        <p class="panel-copy">{{ $activeChat->character->name }} using {{ $modelOptions[$activeChat->model]['label'] ?? $activeChat->model }}</p>
                    </div>
                    <div class="button-row">
                        <form method="POST" action="{{ route('chats.destroy', $activeChat) }}" class="compact-form" onsubmit="return confirm('Delete this whole conversation?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger-button">Delete Chat</button>
                        </form>
                    </div>
                </div>

                <div class="conversation-layout">
                    <div class="message-column">
                        <div class="message-stream">
                            @php
                                $latestAssistantId = optional($activeChat->messages->where('role', 'assistant')->last())->id;
                            @endphp
                            @foreach ($activeChat->messages as $message)
                                <article class="message-bubble {{ $message->role === 'user' ? 'message-user' : 'message-assistant' }}">
                                    <div class="message-head">
                                        <p class="message-role">{{ strtoupper($message->role) }}</p>
                                        <p class="message-meta">
                                            {{ $message->created_at->diffForHumans() }}
                                            @if($message->edited_at)
                                                • edited
                                            @endif
                                        </p>
                                    </div>
                                    <div class="message-content">{{ $message->content }}</div>

                                    <details class="editor-toggle compact-form">
                                        <summary>Message Tools</summary>
                                        <form method="POST" action="{{ route('messages.update', $message) }}" class="stack-form compact-form">
                                            @csrf
                                            @method('PUT')
                                            <label><span>Edit Message</span><textarea name="content" rows="4" required>{{ $message->content }}</textarea></label>
                                            <button type="submit" class="secondary-button">Save Edit</button>
                                        </form>
                                        <form method="POST" action="{{ route('messages.destroy', $message) }}" class="compact-form" onsubmit="return confirm('Delete this message?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="danger-button">Delete Message</button>
                                        </form>
                                        @if ($message->role === 'assistant' && $latestAssistantId === $message->id)
                                            <form method="POST" action="{{ route('messages.regenerate', $message->chat_id) }}" class="compact-form" onsubmit="return confirm('Regenerate the latest assistant response?');">
                                                @csrf
                                                <button type="submit" class="secondary-button">Regenerate Response</button>
                                            </form>
                                        @endif
                                    </details>
                                </article>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('messages.store', $activeChat) }}" class="stack-form">
                            @csrf
                            <label>
                                <span>Next message</span>
                                <textarea name="content" rows="5" required placeholder="Continue the conversation, add instructions, or deepen the roleplay."></textarea>
                            </label>
                            <button type="submit" class="primary-button">Send To Venice</button>
                        </form>
                    </div>

                    <div class="memory-column">
                        <section class="panel nested-panel">
                            <h3>Chat Settings</h3>
                            <form method="POST" action="{{ route('chats.update', $activeChat) }}" class="stack-form compact-form">
                                @csrf
                                @method('PUT')
                                <label><span>Title</span><input type="text" name="title" value="{{ $activeChat->title }}" required></label>
                                <label>
                                    <span>Model</span>
                                    <select name="model" required>
                                        @foreach ($modelOptions as $modelId => $model)
                                            <option value="{{ $modelId }}" @selected($activeChat->model === $modelId) @disabled(($model['status'] ?? 'active') !== 'active')>
                                                {{ $model['label'] }}@if (($model['status'] ?? 'active') !== 'active') (planned)@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="triple-grid">
                                    <label><span>Temp</span><input type="number" name="temperature" step="0.01" min="0" max="2" value="{{ $activeChat->temperature }}" required></label>
                                    <label><span>Max Tokens</span><input type="number" name="max_tokens" min="128" max="4096" value="{{ $activeChat->max_tokens }}" required></label>
                                    <label><span>Top P</span><input type="number" name="top_p" step="0.01" min="0" max="1" value="{{ $activeChat->top_p }}" required></label>
                                </div>
                                <label><span>Pinned Memory</span><textarea name="pinned_memory" rows="4">{{ $activeChat->pinned_memory }}</textarea></label>
                                <label class="checkbox-row">
                                    <input type="checkbox" name="is_pinned" value="1" @checked($activeChat->is_pinned)>
                                    <span>Pin this chat to the top of the roster</span>
                                </label>
                                <button type="submit" class="secondary-button">Update Chat</button>
                            </form>
                            <form method="POST" action="{{ route('chats.destroy', $activeChat) }}" class="compact-form" onsubmit="return confirm('Delete this chat thread?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="danger-button">Delete Chat</button>
                            </form>
                        </section>

                        <section class="panel nested-panel">
                            <h3>Rolling Summary</h3>
                            <form method="POST" action="{{ route('summaries.update', $activeChat) }}" class="stack-form compact-form">
                                @csrf
                                @method('PUT')
                                <label><span>Auto Summary</span><textarea name="summary" rows="8">{{ optional($activeChat->summary)->summary }}</textarea></label>
                                <label><span>Manual Notes</span><textarea name="manual_notes" rows="6">{{ optional($activeChat->summary)->manual_notes }}</textarea></label>
                                <button type="submit" class="secondary-button">Save Memory Notes</button>
                            </form>
                        </section>

                        <section class="panel nested-panel">
                            <h3>Prompt Preview</h3>
                            <pre class="code-block">{{ $promptPreview }}</pre>
                        </section>

                        <section class="panel nested-panel">
                            <h3>Raw Turn Logs</h3>
                            <div class="log-stack">
                                @forelse ($activeChat->logs->take(5) as $log)
                                    <details class="editor-toggle">
                                        <summary>{{ strtoupper($log->event_type) }}@if($log->status_code) • {{ $log->status_code }} @endif</summary>
                                        <div class="stack-form compact-form">
                                            <label><span>Error</span><textarea rows="2" readonly>{{ $log->error_message }}</textarea></label>
                                            <label><span>Request Payload</span><textarea rows="8" readonly>{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea></label>
                                            <label><span>Response Payload</span><textarea rows="8" readonly>{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea></label>
                                        </div>
                                    </details>
                                @empty
                                    <p class="empty-note">No request logs yet.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <p class="eyebrow">Phase 2 In Motion</p>
                    <h2>No active chat yet</h2>
                    <p class="panel-copy">
                        Create a character in the left column, start a thread, and the lab will unlock message editing, prompt preview, and raw Venice logs.
                    </p>
                </div>
            @endif
        </section>
    </main>
@endsection
