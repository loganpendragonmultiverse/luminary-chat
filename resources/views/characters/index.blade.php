@extends('layouts.app', ['title' => 'Characters | Luminary Chat'])

@section('content')
    <main class="stack-layout character-roster-page">
        <section class="panel character-roster-hero">
            <div>
                <p class="eyebrow">Characters</p>
                <h1>Choose your companion</h1>
                <p class="panel-copy">Choose a character, then open Chat Mode, Companion Mode, or Manage Character. Their rooms, memories, and identity remain separate.</p>
            </div>
            <span class="tiny-pill">{{ $characters->count() }} available</span>
        </section>

        <section class="character-choice-grid" aria-label="Available characters">
                @foreach ($characters as $character)
                    @php
                        $activeMemories = $character->memoryEntries->where('status', '!=', 'deleted');
                        $isMira = $character->template_key === 'mira';
                    @endphp
                    <article class="panel character-choice-card {{ $isMira ? 'mira-instance' : 'luminary-instance' }}">
                        <div class="character-instance-heading">
                            <div class="character-instance-avatar {{ $character->template_key }}" aria-hidden="true">{{ strtoupper(substr($character->name, 0, 1)) }}</div>
                            <div>
                                <h2>{{ $character->name }}</h2>
                                <p>{{ $character->short_description }}</p>
                            </div>
                        </div>
                        <p class="character-instance-copy">{{ $character->long_bio }}</p>
                        <dl class="character-summary-list">
                            <div><dt>Rooms</dt><dd>{{ $character->rooms->count() }}</dd></div>
                            <div><dt>Memories</dt><dd>{{ $activeMemories->count() }}</dd></div>
                        </dl>
                        <div class="character-choice-actions">
                            <a href="{{ route('characters.chats', $character) }}" class="primary-button" aria-label="Chat Mode for {{ $character->name }}">Chat Mode</a>
                            <a href="{{ '/companion/cottage-20261012/?character='.rawurlencode($character->template_key) }}" class="secondary-button" aria-label="Companion Mode for {{ $character->name }}">Companion Mode</a>
                            <a href="{{ route('characters.index', ['manage' => $character->id, 'section' => $character->template_key === 'luminary' ? 'defaults' : 'markdown']) }}#character-management" class="ghost-button" aria-label="Manage Character for {{ $character->name }}">Manage Character</a>
                        </div>
                    </article>
                @endforeach
        </section>

        <section class="instance-reset-panel character-total-reset" aria-labelledby="reset-all-characters-title">
            <div>
                <p class="eyebrow">Start over</p>
                <h2 id="reset-all-characters-title">Reset all characters</h2>
                <p>This permanently deletes every chat and memory in this account, removes custom characters, and restores Luminary and Mira's original Souls, rooms, roles, and character settings. Provider keys, model visibility, profile details, and account access stay unchanged.</p>
            </div>
            <form method="POST" action="{{ route('characters.reset-all') }}" class="stack-form compact-form" onsubmit="return confirm('Permanently delete every chat and memory and reset every character to the product defaults? This cannot be undone.');">
                @csrf
                <label><span>Type RESET ALL CHARACTERS to confirm</span><input name="confirmation" required autocomplete="off" pattern="RESET ALL CHARACTERS"></label>
                <button type="submit" class="danger-button">Reset all characters</button>
            </form>
        </section>

        @if ($managedCharacter)
            @php
                $character = $managedCharacter;
                $activeMemories = $character->memoryEntries->where('status', '!=', 'deleted');
            @endphp

            <section id="character-management" class="panel character-management-workspace">
                <header class="character-management-heading">
                    <div class="character-instance-heading">
                        <div class="character-instance-avatar {{ $character->template_key }}" aria-hidden="true">{{ strtoupper(substr($character->name, 0, 1)) }}</div>
                        <div>
                            <p class="eyebrow">Character settings</p>
                            <h2>{{ $character->name }}</h2>
                            <p>{{ $character->short_description }}</p>
                        </div>
                    </div>
                    <div class="character-management-actions">
                        <a href="{{ route('models.index') }}" class="ghost-button">Manage models</a>
                        <a href="{{ route('characters.chats', $character) }}" class="primary-button" aria-label="Chat Mode for {{ $character->name }}">Chat Mode</a>
                    </div>
                </header>

                <section class="character-default-model-panel" aria-labelledby="character-default-model-title">
                    <div>
                        <p class="eyebrow">New-chat default</p>
                        <h3 id="character-default-model-title">Default model</h3>
                        <p>Every new {{ $character->name }} conversation starts with this model unless you choose another model while creating that chat.</p>
                        <small>Current: {{ $modelOptions[$defaultModelSelection]['label'] ?? $character->default_model }}</small>
                    </div>
                    @if ($modelOptions)
                        <form method="POST" action="{{ route('characters.default-model.update', $character) }}" class="character-default-model-form">
                            @csrf
                            @method('PUT')
                            <label><span>Model for new {{ $character->name }} chats</span><select name="default_model" required>
                                @unless($defaultModelSelection)<option value="" selected>Choose a visible model</option>@endunless
                                @include('chats.partials.model-option-groups', ['selectedModel' => $defaultModelSelection])
                            </select></label>
                            <button type="submit" class="secondary-button">Save default model</button>
                        </form>
                    @else
                        <div class="character-default-model-empty"><p>No visible provider models are available.</p><a href="{{ route('models.index') }}">Open Model Management</a></div>
                    @endif
                </section>

                @php
                    $managementSections = $character->template_key === 'luminary'
                        ? [
                            'defaults' => ['Relationship role', 'Best Friend, Lover, or Professional'],
                            'rooms' => ['Rooms', $character->rooms->count().' conversation spaces'],
                            'memory' => ['Memory', $activeMemories->count().' saved memories'],
                        ]
                        : [
                            'markdown' => ['Soul', 'Compare the default and edit your copy'],
                            'rooms' => ['Rooms', $character->rooms->count().' conversation spaces'],
                            'memory' => ['Memory', $activeMemories->count().' saved memories'],
                        ];
                @endphp
                <nav class="character-management-nav" aria-label="{{ $character->name }} settings">
                    @foreach ($managementSections as $section => [$label, $sectionDescription])
                        <a
                            href="{{ route('characters.index', ['manage' => $character->id, 'section' => $section]) }}#character-management"
                            class="{{ $managementSection === $section ? 'active' : '' }}"
                            @if ($managementSection === $section) aria-current="page" @endif
                        >
                            <strong>{{ $label }}</strong>
                            <span>{{ $sectionDescription }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="character-management-content">
                    @if ($managementSection === 'defaults')
                        <div class="management-section-heading">
                            <div><p class="eyebrow">Luminary relationship</p><h3>What role do you want her to play?</h3></div>
                            <p>Luminary's personality stays natural. This choice only establishes the relationship she carries into conversations.</p>
                        </div>
                        <form method="POST" action="{{ route('characters.defaults.update', $character) }}" class="stack-form compact-form focused-character-form">
                            @csrf
                            @method('PUT')
                            <div class="original-visual-grid">
                                @foreach (['confidant' => ['Best Friend', 'Deep connection without romance', 'holographic'], 'lover' => ['Lover', 'Romantic closeness and devotion', 'core'], 'professional' => ['Professional', 'Business-like focus and clarity', 'robotic']] as $form => [$optionLabel, $optionDescription, $visual])
                                    <label class="original-visual-option"><input type="radio" name="companion_form" value="{{ $form }}" @checked(($character->companion_form ?: 'confidant') === $form)><span class="visual {{ $visual }}"></span><strong>{{ $optionLabel }}</strong><small>{{ $optionDescription }}</small></label>
                                @endforeach
                            </div>
                            <button type="submit" class="secondary-button">Save Luminary role</button>
                        </form>
                    @elseif ($managementSection === 'markdown' && $character->template_key !== 'luminary')
                        <div class="management-section-heading">
                            <div><p class="eyebrow">Character identity</p><h3>{{ $character->name }} Soul</h3></div>
                            <p>The original Soul is the read-only foundation. Your personal Soul is separate, editable, and the only Soul used in your {{ $character->name }} chats.</p>
                        </div>
                        <form method="POST" action="{{ route('characters.persona.restore', $character) }}" class="instance-reset-panel" onsubmit="return confirm('Permanently replace your personal {{ $character->name }} Soul with the current original Soul? All personal Soul edits will be lost. Your rooms, memories, and chat history will not change. This cannot be undone.');">
                            @csrf
                            <div>
                                <strong>Restore your Soul from the original</strong>
                                <small>This permanently discards every personal Soul edit and makes your copy match the current default. Rooms, memories, chats, and other users remain unchanged.</small>
                            </div>
                            <button type="submit" class="danger-button">Restore original Soul</button>
                        </form>
                        <div class="soul-comparison-grid">
                            <section class="soul-editor-panel default-soul-panel" aria-labelledby="default-soul-title">
                                <div class="card-heading">
                                    <strong id="default-soul-title">Original {{ $character->name }} Soul</strong>
                                    <span class="tiny-pill">Default · read only</span>
                                </div>
                                <p>This is the protected product default. It is shown for comparison and is never changed by your edits.</p>
                                <label><span>Default Soul.md</span><textarea rows="28" readonly aria-readonly="true">{{ $miraDefaultSoul }}</textarea></label>
                            </section>
                            <form method="POST" action="{{ route('characters.persona.update', [$character, 'fileName' => 'Soul.md']) }}" class="soul-editor-panel personal-soul-panel compact-form">
                                @csrf
                                @method('PUT')
                                <div class="card-heading">
                                    <strong>Your {{ $character->name }} Soul</strong>
                                    <span class="tiny-pill">Used in chats</span>
                                </div>
                                <p>Edit this private account copy. New model requests use this Soul; the default and every other user's {{ $character->name }} remain unchanged.</p>
                                <label><span>Personal Soul.md</span><textarea name="content" rows="28" required>{{ ($character->persona_documents ?? [])['Soul.md'] ?? $miraDefaultSoul }}</textarea></label>
                                <button type="submit" class="ghost-button">Save personal Soul</button>
                            </form>
                        </div>
                    @elseif ($managementSection === 'rooms')
                        <div class="management-section-heading">
                            <div><p class="eyebrow">Conversation spaces</p><h3>Rooms</h3></div>
                            <p>Customize the spaces available to {{ $character->name }}, choose a default, or add a new room.</p>
                        </div>
                        <form method="POST" action="{{ route('rooms.restore', $character) }}" class="instance-reset-panel" onsubmit="return confirm('Permanently reset every {{ $character->name }} room? This replaces all room edits, restores deleted default rooms, removes every custom room, and moves affected conversations to the original default room. This cannot be undone.');">
                            @csrf
                            <div>
                                <strong>Restore the original rooms</strong>
                                <small>This permanently discards room edits and custom rooms, then recreates the original room set. It cannot be undone.</small>
                            </div>
                            <button type="submit" class="danger-button">Restore original room defaults</button>
                        </form>
                        <div class="room-card-list">
                            @foreach ($character->rooms as $room)
                                <article class="room-management-card">
                                    <form method="POST" action="{{ route('rooms.update', $room) }}" class="room-editor compact-form">
                                        @csrf
                                        @method('PUT')
                                        <div class="card-heading">
                                            <strong>{{ $room->name }}</strong>
                                            @if ($room->is_default)<span class="tiny-pill">Default</span>@endif
                                        </div>
                                        <label><span>Name</span><input name="name" value="{{ $room->name }}" required></label>
                                        <label><span>Description</span><textarea name="description" rows="2" required>{{ $room->description }}</textarea></label>
                                        <label><span>Contents</span><textarea name="contents" rows="2">{{ $room->contents }}</textarea></label>
                                        <label><span>Expectations</span><textarea name="expectations" rows="2">{{ $room->expectations }}</textarea></label>
                                        <label class="checkbox-row"><input type="checkbox" name="is_default" value="1" @checked($room->is_default)><span>Default room for new chats</span></label>
                                        <button type="submit" class="ghost-button">Update room</button>
                                    </form>
                                    @unless ($room->is_default)
                                        <form method="POST" action="{{ route('rooms.destroy', $room) }}" class="room-delete-form" onsubmit="return confirm('Delete this room? Existing conversations must be moved first.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="danger-button">Delete {{ $room->name }}</button>
                                        </form>
                                    @endunless
                                </article>
                            @endforeach
                        </div>
                        <form method="POST" action="{{ route('rooms.store', $character) }}" class="stack-form compact-form add-room-form">
                            @csrf
                            <h3>Add a room</h3>
                            <label><span>Name</span><input name="name" required placeholder="Library"></label>
                            <label><span>Description</span><textarea name="description" rows="2" required></textarea></label>
                            <label><span>Contents</span><textarea name="contents" rows="2"></textarea></label>
                            <label><span>Expectations</span><textarea name="expectations" rows="2"></textarea></label>
                            <button type="submit" class="ghost-button">Add room</button>
                        </form>
                    @else
                        <div class="management-section-heading">
                            <div><p class="eyebrow">Cross-conversation context</p><h3>Memory</h3></div>
                            <p>Review durable context saved for {{ $character->name }}. Revisions preserve prior text.</p>
                        </div>
                        <form method="POST" action="{{ route('memory.character.store', $character) }}" class="stack-form compact-form focused-character-form">
                            @csrf
                            <h3>Add a memory</h3>
                            <label><span>Title</span><input type="text" name="title" required></label>
                            <label><span>Category</span><input type="text" name="category" value="character_fact" required></label>
                            <label><span>Memory</span><textarea name="content" rows="3" required></textarea></label>
                            <label><span>Importance</span><input type="range" name="importance" min="0" max="1" step="0.1" value="0.7"></label>
                            <label class="checkbox-row"><input type="checkbox" name="is_pinned" value="1"><span>Always prioritize this memory</span></label>
                            <button type="submit" class="ghost-button">Save memory</button>
                        </form>
                        <div class="memory-instance-list">
                            @forelse ($activeMemories->sortByDesc('importance') as $memory)
                                <form method="POST" action="{{ route('memory.update', $memory) }}" class="memory-instance-card compact-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="card-heading">
                                        <strong>{{ $memory->title }}</strong>
                                        <span class="tiny-pill">{{ $memory->category }} · {{ number_format($memory->importance, 1) }}</span>
                                    </div>
                                    <textarea name="content" rows="3" required>{{ $memory->content }}</textarea>
                                    <div class="triple-grid">
                                        <label><span>Status</span><select name="status"><option value="active" @selected($memory->status === 'active')>Active</option><option value="review" @selected($memory->status === 'review')>Review</option><option value="conflicted" @selected($memory->status === 'conflicted')>Conflicted</option></select></label>
                                        <label><span>Importance</span><input type="number" name="importance" min="0" max="1" step="0.1" value="{{ $memory->importance }}"></label>
                                        <label><span>Revision note</span><input type="text" name="reason" placeholder="What changed?"></label>
                                    </div>
                                    <div class="button-row">
                                        <label class="checkbox-row"><input type="checkbox" name="is_pinned" value="1" @checked($memory->is_pinned)><span>Pinned</span></label>
                                        <label class="checkbox-row"><input type="checkbox" name="is_locked" value="1" @checked($memory->is_locked)><span>Locked</span></label>
                                        <button type="submit" class="ghost-button">Revise</button>
                                        <span class="meta-copy">{{ $memory->revisions->count() }} prior revisions</span>
                                    </div>
                                </form>
                            @empty
                                <p class="empty-note">No persistent memories yet.</p>
                            @endforelse
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </main>
@endsection
