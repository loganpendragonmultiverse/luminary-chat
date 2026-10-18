@extends('layouts.app', [
    'title' => 'Luminary Chat FAQ | Accounts, BYOK, Memory and Privacy',
    'description' => 'Answers to common questions about Luminary Chat, provider keys, model costs, story-aware memory, privacy controls, exports, and account deletion.',
])

@section('structured-data')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            ['@type' => 'Question', 'name' => 'What is Luminary Chat?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Luminary Chat is a live web application for ongoing character conversations using a user-owned Venice or OpenRouter API key.']],
            ['@type' => 'Question', 'name' => 'Do I need my own API key?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes. Add a Venice key, an OpenRouter key, or both. The provider account controls access, pricing, and usage limits.']],
            ['@type' => 'Question', 'name' => 'Does Luminary Chat charge for model usage?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Luminary does not sell model credits. Model usage is billed and rate-limited by Venice or OpenRouter under the user account.']],
            ['@type' => 'Question', 'name' => 'Can memory cross conversations?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Character memory can support later chats with the same character when Neural Memory is enabled. When it is off, memory stays scoped to each chat.']],
            ['@type' => 'Question', 'name' => 'Can I delete my data?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Users can delete messages and memories, clear chat history and memory, remove provider keys, export chat data, or permanently delete the account.']],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
    <main class="stack-layout legal-page">
        <section class="panel">
            <p class="eyebrow">Frequently Asked Questions</p>
            <h1>Common questions before starting Luminary Chat</h1>
            <p class="panel-copy">These answers describe the current live service. The roadmap may change as real usage and feedback arrive.</p>
        </section>

        <section class="legal-grid">
            <article class="panel nested-panel"><h2>What is Luminary Chat?</h2><p class="panel-copy">Luminary Chat is a live web application for ongoing character conversations. It combines user-owned AI-provider access with character identity, chat organization, rooms, summaries, and editable memory controls.</p></article>
            <article class="panel nested-panel"><h2>Who is it for?</h2><p class="panel-copy">It is intended for people who want recurring character conversations, story continuity, roleplay, reflective companionship, or a focused professional conversation mode.</p></article>
            <article class="panel nested-panel"><h2>Do I need my own API key?</h2><p class="panel-copy">Yes. Add a Venice key, an OpenRouter key, or both. Luminary validates the key and lists the text-chat models available to it.</p></article>
            <article class="panel nested-panel"><h2>Does Luminary charge for model usage?</h2><p class="panel-copy">Luminary does not sell model credits. Venice or OpenRouter bills and rate-limits usage under your provider account. Check model pricing and set provider limits before long sessions.</p></article>
            <article class="panel nested-panel"><h2>Can memory cross conversations?</h2><p class="panel-copy">Character memory can support later chats with the same character when Neural Memory is enabled. When it is off, extracted memory remains scoped to each chat.</p></article>
            <article class="panel nested-panel"><h2>Can I turn automatic memory off?</h2><p class="panel-copy">Yes. Automatic Memory Extraction and cross-chat Neural Memory are separate settings. You can disable either one and continue using ordinary chat history.</p></article>
            <article class="panel nested-panel"><h2>Where do messages go?</h2><p class="panel-copy">The context required for a reply is sent to the provider selected by the model. Venice models go to Venice. OpenRouter models go to OpenRouter and the underlying endpoint it selects. Provider policies apply.</p></article>
            <article class="panel nested-panel"><h2>Can I delete my data?</h2><p class="panel-copy">Yes. You can delete messages and memory entries, clear chat history and memory, remove provider keys, export account chat data, or permanently delete the account.</p></article>
            <article class="panel nested-panel"><h2>Will Luminary keep evolving?</h2><p class="panel-copy">Yes. Luminary Chat is live, and its core account, chat, model, character, memory, export, and deletion controls are available now. Reliability and product direction will continue to evolve.</p></article>
            <article class="panel nested-panel"><h2>Where should I start?</h2><p class="panel-copy">Read <a href="{{ route('how-it-works') }}">How It Works</a>, follow the <a href="{{ route('byok-guide') }}">BYOK guide</a>, then create an account and add a limited provider key in Settings.</p></article>
        </section>
    </main>
@endsection
