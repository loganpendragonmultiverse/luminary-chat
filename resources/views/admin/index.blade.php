@extends('layouts.app', ['title' => 'Luminary Admin'])

@section('content')
    <main class="luminary-admin-page">
        <section class="original-settings-wrap">
            <div class="original-settings-heading admin-heading-row">
                <div>
                    <h1>Admin</h1>
                    <p>Moderate Luminary accounts, pause access, and grant admin status when needed.</p>
                </div>
                <div class="admin-metric-row">
                    <div><span>Total users</span><strong>{{ $users->count() }}</strong></div>
                    <div><span>Active</span><strong>{{ $users->where('is_active', true)->count() }}</strong></div>
                    <div><span>Admins</span><strong>{{ $users->where('is_admin', true)->count() }}</strong></div>
                </div>
            </div>

            <section class="admin-user-panel">
                <div class="admin-table-header">
                    <h2>User moderation</h2>
                    <p>Provider keys are user-owned and are only shown here as saved or missing.</p>
                </div>

                <div class="admin-user-table">
                    <div class="admin-user-row admin-user-head">
                        <span>User</span>
                        <span>Status</span>
                        <span>Usage</span>
                        <span>Moderation</span>
                    </div>

                    @foreach ($users as $user)
                        <article class="admin-user-row">
                            <div class="admin-user-cell">
                                <strong>{{ $user->name }}</strong>
                                <small>{{ $user->email }}</small>
                                <small>Joined {{ $user->created_at->format('M j, Y') }}</small>
                            </div>
                            <div class="admin-user-badges">
                                <span>{{ $user->is_admin ? 'Admin' : 'Member' }}</span>
                                <span>{{ $user->is_active ? 'Active' : 'Paused' }}</span>
                                <span>{{ $user->venice_api_key ? 'Venice key saved' : 'No Venice key' }}</span>
                                <span>{{ $user->openrouter_api_key ? 'OpenRouter key saved' : 'No OpenRouter key' }}</span>
                            </div>
                            <div class="admin-user-stats">
                                <span>{{ $user->chats_count }} chats</span>
                                <span>{{ $user->characters_count }} companion records</span>
                                <span>{{ $user->chat_folders_count }} folders</span>
                            </div>
                            <div class="admin-user-actions">
                                <details>
                                    <summary>Manage</summary>
                                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-moderation-form">
                                        @csrf
                                        @method('PUT')
                                        <label>
                                            <span>Name</span>
                                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                                        </label>
                                        <label>
                                            <span>Email</span>
                                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                                        </label>
                                        <label class="checkbox-row">
                                            <input type="checkbox" name="is_admin" value="1" @checked($user->is_admin)>
                                            <span>Admin</span>
                                        </label>
                                        <label class="checkbox-row">
                                            <input type="checkbox" name="is_active" value="1" @checked($user->is_active)>
                                            <span>Active account</span>
                                        </label>
                                        <button type="submit">Save user</button>
                                    </form>

                                    @unless ($user->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user and their Luminary data?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-delete-button">Delete user</button>
                                        </form>
                                    @endunless
                                </details>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </section>
    </main>
@endsection
