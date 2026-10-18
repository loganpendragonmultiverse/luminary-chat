@extends('layouts.app', ['title' => 'Luminary Chat App Entry'])

@section('content')
    <main class="auth-shell">
        <section class="auth-card">
            <p class="eyebrow">Live App Access</p>
            <h1>Step into Luminary Chat</h1>
            <p class="panel-copy">
                Register, add your own Venice or OpenRouter API key, choose from the models your keys unlock, shape Luminary's companion profile, and preserve story continuity across longer sessions.
            </p>

            <div class="info-block">
                <strong>Inside the app</strong>
                <p>Luminary at the core, with support for additional characters, conversation threads, editable memory, privacy controls, avatar settings, and a clean live product workflow.</p>
            </div>

            <div class="button-row">
                <a href="{{ route('chat.entry') }}" class="primary-button subtle-link">Start chat</a>
                <a href="{{ route('register') }}" class="secondary-button subtle-link">Register</a>
                <a href="{{ route('login') }}" class="ghost-button subtle-link">Log in</a>
                <a href="{{ route('welcome') }}" class="ghost-button subtle-link">Home</a>
            </div>
        </section>
    </main>
@endsection
