@extends('layouts.app', [
    'title' => 'How Luminary Chat Works | Story-Aware AI Companion',
    'description' => 'Learn how Luminary Chat combines user-owned AI access, character identity, conversation context, editable memory, rooms, and privacy controls.',
])

@section('content')
    <main class="stack-layout legal-page">
        <section class="panel">
            <p class="eyebrow">How It Works</p>
            <h1>A companion system built for continuity and user control</h1>
            <p class="panel-copy">Luminary Chat is a web application for ongoing character conversations. You bring access to an AI provider, choose the model, shape the character, and decide how much context and memory should carry forward.</p>
        </section>

        <section class="legal-grid">
            <article class="panel nested-panel">
                <p class="eyebrow">Step 1</p>
                <h2>Create your private account</h2>
                <p class="panel-copy">An account keeps your characters, conversations, settings, and memory separate from every other user. Public pages explain the product; chats and account surfaces stay out of search.</p>
            </article>
            <article class="panel nested-panel">
                <p class="eyebrow">Step 2</p>
                <h2>Connect a provider you control</h2>
                <p class="panel-copy">Add a Venice key, an OpenRouter key, or both. Luminary validates the keys, reads the models available to them, and lets you choose a provider model for new conversations.</p>
                <p><a href="{{ route('byok-guide') }}">Read the BYOK setup and safety guide</a>.</p>
            </article>
            <article class="panel nested-panel">
                <p class="eyebrow">Step 3</p>
                <h2>Shape the character and scene</h2>
                <p class="panel-copy">Character identity can include a name, description, personality, scenario, writing style, lore, relationship mode, tone, opening message, and model controls. Rooms provide current situational context without automatically forcing an activity.</p>
            </article>
            <article class="panel nested-panel">
                <p class="eyebrow">Step 4</p>
                <h2>Start a conversation with context</h2>
                <p class="panel-copy">Each reply can use the character definition, current room, recent messages, a rolling summary, manual notes, and relevant memory. Context depth controls how much recent conversation is included.</p>
            </article>
            <article class="panel nested-panel">
                <p class="eyebrow">Step 5</p>
                <h2>Choose what should persist</h2>
                <p class="panel-copy">Memory can remain inside one chat or, when cross-chat memory is enabled, support later conversations with the same character. Automatic extraction can save a small set of durable facts after replies; entries remain editable and deletable.</p>
                <p><a href="{{ route('memory-and-privacy') }}">See exactly how memory and privacy controls work</a>.</p>
            </article>
            <article class="panel nested-panel">
                <p class="eyebrow">Step 6</p>
                <h2>Keep an exit path</h2>
                <p class="panel-copy">You can export account chat data, delete individual messages and memories, clear chat history and memory together, remove provider keys, or permanently delete the account and its stored data.</p>
            </article>
        </section>

        <section class="panel">
            <h2>What Luminary does not do for you</h2>
            <p class="panel-copy">Luminary does not provide free model usage, choose a provider's privacy policy, guarantee that AI output is correct, or make provider charges disappear. It provides the character, continuity, organization, and control layer around the provider access you choose.</p>
            <p><a href="{{ route('faq') }}">Read common questions</a> or <a href="{{ route('register') }}">create an account</a>.</p>
        </section>
    </main>
@endsection
