@extends('layouts.app', ['title' => 'Settings | Luminary Chat'])

@section('content')
    <main class="original-settings-page">
        <section class="original-settings-wrap">
            <div class="original-settings-heading">
                <h1>Settings</h1>
                <p>Customize your Luminary experience and manage your preferences.</p>
            </div>

            <form method="POST" action="{{ route('settings.profile') }}" class="original-settings-layout">
                @csrf
                @method('PUT')

                <input type="radio" name="settings_tab" id="settings-tab-profile" checked>
                <input type="radio" name="settings_tab" id="settings-tab-companion">
                <input type="radio" name="settings_tab" id="settings-tab-memory">
                <input type="radio" name="settings_tab" id="settings-tab-privacy">

                <nav class="original-settings-tabs" aria-label="Settings sections">
                    <label for="settings-tab-profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z"></path><path d="M12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"></path></svg>
                        Profile
                    </label>
                    <label for="settings-tab-companion">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.7 17h4.6M12 3v1m6.4 1.6-.7.7M21 12h-1M4 12H3m3.3-5.7-.7-.7m2.8 9.9a5 5 0 1 1 7.1 0l-.6.6c-.6.6-.9 1.4-.9 2.3V19a2 2 0 1 1-4 0v-.5c0-.9-.4-1.8-1-2.4l-.6-.6z"></path></svg>
                        Providers
                    </label>
                    <label for="settings-tab-memory">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7v10c0 2.2 3.6 4 8 4s8-1.8 8-4V7"></path><path d="M4 7c0 2.2 3.6 4 8 4s8-1.8 8-4-3.6-4-8-4-8 1.8-8 4z"></path><path d="M4 12c0 2.2 3.6 4 8 4s8-1.8 8-4"></path></svg>
                        Memory
                    </label>
                    <label for="settings-tab-privacy">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15v2"></path><path d="M6 21h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2z"></path><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
                        Privacy
                    </label>
                </nav>

                <div class="original-settings-content">
                    <section class="original-settings-panel original-profile-panel">
                        <div class="original-settings-card">
                            <h2>Profile Information</h2>
                            <div class="original-profile-row">
                                <div class="original-profile-avatar">
                                    @if ($profileUser->avatar_url)
                                        <img src="{{ $profileUser->avatar_url }}" alt="">
                                    @else
                                        {{ strtoupper(substr($profileUser->name ?? 'U', 0, 1)) }}
                                    @endif
                                </div>
                                <label class="original-avatar-url">
                                    <span>Avatar URL</span>
                                    <input type="url" name="avatar_url" value="{{ old('avatar_url', $profileUser->avatar_url) }}" placeholder="https://example.com/avatar.jpg">
                                </label>
                            </div>

                            <div class="original-field-grid">
                                <label><span>Display Name</span><input type="text" name="name" value="{{ old('name', $profileUser->name) }}" required></label>
                                <label><span>Email</span><input type="email" name="email" value="{{ old('email', $profileUser->email) }}" required></label>
                                <label><span>New Password</span><input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current password"></label>
                                <label><span>Confirm Password</span><input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat new password"></label>
                            </div>
                        </div>
                    </section>

                    <section class="original-settings-panel original-companion-panel">
                        <div class="original-settings-card">
                            <h2>Bring Your Own Key (BYOK)</h2>
                            <p>Add a Venice key, an OpenRouter key, or both. Luminary reconciles each provider's live catalog and makes every available text-chat model selectable.</p>
                            <div class="original-field-grid provider-key-grid">
                                <label>
                                    <span>Venice API Key · {{ $profileUser->venice_api_key ? 'Saved' : 'Not configured' }}</span>
                                    <input type="password" name="venice_api_key" value="" autocomplete="new-password" placeholder="{{ $profileUser->venice_api_key ? 'Leave blank to keep saved key' : 'Paste your Venice API key' }}">
                                    <small>{{ $providerModelCounts->get('venice', 0) }} models available</small>
                                </label>
                                <label>
                                    <span>OpenRouter API Key · {{ $profileUser->openrouter_api_key ? 'Saved' : 'Not configured' }}</span>
                                    <input type="password" name="openrouter_api_key" value="" autocomplete="new-password" placeholder="{{ $profileUser->openrouter_api_key ? 'Leave blank to keep saved key' : 'Paste your OpenRouter API key' }}">
                                    <small>{{ $providerModelCounts->get('openrouter', 0) }} models available</small>
                                </label>
                            </div>
                            <div class="provider-key-actions">
                                @if ($profileUser->venice_api_key)
                                    <label class="checkbox-row"><input type="checkbox" name="remove_venice_api_key" value="1"><span>Remove saved Venice key</span></label>
                                @endif
                                @if ($profileUser->openrouter_api_key)
                                    <label class="checkbox-row"><input type="checkbox" name="remove_openrouter_api_key" value="1"><span>Remove saved OpenRouter key</span></label>
                                @endif
                            </div>
                            <p class="original-help-text">Keys are encrypted at rest and are never shown again. Save changes to validate the keys and refresh model access.@if ($profileUser->ai_models_synced_at) Last reconciled {{ $profileUser->ai_models_synced_at->diffForHumans() }}.@endif</p>
                        </div>

                    </section>

                    <section class="original-settings-panel original-memory-panel">
                        <div class="original-settings-card">
                            <h2>Neural Memory</h2>
                            <p>Configure how your characters remember conversations.</p>
                            <label class="original-toggle-row">
                                <span>
                                    <strong>Enable Neural Memory</strong>
                                    <small>Allow each character to remember useful details across its conversations. When off, memory stays within each chat.</small>
                                </span>
                                <input type="checkbox" name="memory_enabled" value="1" @checked(old('memory_enabled', $profileUser->memory_enabled ?? true))>
                                <i></i>
                            </label>
                            <label class="original-toggle-row">
                                <span>
                                    <strong>Automatic Memory Extraction</strong>
                                    <small>Let the active character save continuity notes after replies</small>
                                </span>
                                <input type="checkbox" name="memory_auto_extract_enabled" value="1" @checked(old('memory_auto_extract_enabled', $profileUser->memory_auto_extract_enabled ?? true))>
                                <i></i>
                            </label>
                            <div class="original-context-card">
                                <div>
                                    <strong>Context Depth</strong>
                                    <small>How much recent conversation the active character sends with each request</small>
                                </div>
                                <div class="context-depth-options">
                                    @foreach ([
                                        'low' => ['Low', 'Shorter context'],
                                        'medium' => ['Medium', 'More continuity'],
                                        'high' => ['High', 'Up to 100 recent messages'],
                                    ] as $depth => [$label, $description])
                                        <label>
                                            <input type="radio" name="context_depth" value="{{ $depth }}" @checked(old('context_depth', $profileUser->context_depth ?? 'high') === $depth)>
                                            <span>{{ $label }}</span>
                                            <small>{{ $description }}</small>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="original-stat-grid">
                                <div><strong>{{ number_format($conversationCount) }}</strong><span>Conversations</span></div>
                                <div><strong>{{ number_format($memoryCount) }}</strong><span>Memories</span></div>
                            </div>
                        </div>

                        <div class="original-danger-card">
                            <h2>Clear Memory</h2>
                            <p>This will permanently delete conversation history and memories. This action cannot be undone.</p>
                            <button type="submit" form="clear-memory-form">Clear All Memory</button>
                        </div>
                    </section>

                    <section class="original-settings-panel original-privacy-panel">
                        <div class="original-settings-card">
                            <h2>Privacy & Security</h2>
                            <label class="original-toggle-row">
                                <span>
                                    <strong>Anonymous diagnostics</strong>
                                    <small>Allow diagnostics needed to troubleshoot Venice request failures</small>
                                </span>
                                <input type="checkbox" name="privacy_allow_diagnostics" value="1" @checked(old('privacy_allow_diagnostics', $profileUser->privacy_allow_diagnostics ?? true))>
                                <i></i>
                            </label>
                        </div>

                        <div class="original-settings-card">
                            <h2>Export Data</h2>
                            <p>Download a JSON copy of your Luminary chat history, saved messages, summaries, and memory notes.</p>
                            <a href="{{ route('account.export') }}" class="original-outline-button">Export All Data</a>
                        </div>

                        <div class="original-danger-card">
                            <h2>Delete Account</h2>
                            <p>Permanently delete your account, chats, companions, messages, memories, and settings. Export your data first if you want a copy.</p>
                            <label><span>Current password</span><input type="password" name="current_password" form="delete-account-form" autocomplete="current-password" required></label>
                            <label><span>Type DELETE</span><input type="text" name="confirmation" form="delete-account-form" autocomplete="off" required></label>
                            <button type="submit" form="delete-account-form">Delete My Account</button>
                        </div>
                    </section>

                    <div class="original-settings-save">
                        <button type="submit">Save Changes</button>
                    </div>
                </div>
            </form>

            <form id="clear-memory-form" method="POST" action="{{ route('settings.memory.clear') }}" onsubmit="return confirm('Clear all Luminary chats and memory? This cannot be undone.');">
                @csrf
                @method('DELETE')
            </form>
            <form id="delete-account-form" method="POST" action="{{ route('account.destroy') }}" onsubmit="return confirm('Permanently delete your Luminary account and all stored data?');">
                @csrf
                @method('DELETE')
            </form>

        </section>
    </main>
@endsection
