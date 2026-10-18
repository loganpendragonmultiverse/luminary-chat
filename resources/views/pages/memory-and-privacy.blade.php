@extends('layouts.app', [
    'title' => 'Memory and Privacy Controls | Luminary Chat',
    'description' => 'Understand Luminary Chat memory scopes, provider processing, diagnostics, analytics, exports, clearing controls, and permanent account deletion.',
])

@section('content')
    <main class="stack-layout legal-page">
        <section class="panel">
            <p class="eyebrow">Memory & Privacy</p>
            <h1>Know what is stored, what is sent, and what you can remove</h1>
            <p class="panel-copy">Luminary separates application storage, AI-provider processing, diagnostics, and website analytics. These controls overlap, but they are not the same thing.</p>
        </section>

        <section class="legal-grid">
            <article class="panel nested-panel">
                <h2>Conversation history</h2>
                <p class="panel-copy">Your account stores chats and messages so you can return to them. Each chat belongs to one user and character. You can edit or delete messages, archive chats, export chat data, or clear the stored history.</p>
            </article>
            <article class="panel nested-panel">
                <h2>Recent context</h2>
                <p class="panel-copy">A model reply receives a limited amount of recent conversation. The Low, Medium, and High context-depth settings control how many recent messages may be included.</p>
            </article>
            <article class="panel nested-panel">
                <h2>Chat memory</h2>
                <p class="panel-copy">Memory tied to one chat can support continuity inside that conversation without automatically crossing into other chats.</p>
            </article>
            <article class="panel nested-panel">
                <h2>Character memory</h2>
                <p class="panel-copy">When Neural Memory is enabled, durable character-level memories can support later conversations with the same character. Memories from another user or character are not included.</p>
            </article>
            <article class="panel nested-panel">
                <h2>Automatic extraction</h2>
                <p class="panel-copy">When enabled, Luminary may ask the selected provider model to extract a small set of durable facts, preferences, goals, lore, constraints, plans, or emotional context from recent messages. Temporary filler should be skipped.</p>
            </article>
            <article class="panel nested-panel">
                <h2>Manual control</h2>
                <p class="panel-copy">Memory entries can be reviewed, edited, pinned, superseded, or deleted. Turning cross-chat memory off keeps extracted memory scoped to the current chat.</p>
            </article>
        </section>

        <section class="panel">
            <h2>What reaches the AI provider</h2>
            <p class="panel-copy">To generate a reply, Luminary sends the selected provider the character instructions plus relevant room context, recent messages, summaries, manual notes, and selected memory. The provider is determined by the model: Venice models use Venice; OpenRouter models use OpenRouter and an underlying endpoint selected by OpenRouter.</p>
            <p class="panel-copy">Do not enter secrets or content you are unwilling to send to the selected provider. Review the provider's current policies and model-level privacy options before use.</p>
        </section>

        <section class="legal-grid">
            <article class="panel nested-panel">
                <h2>Provider keys</h2>
                <p class="panel-copy">Saved Venice and OpenRouter keys are encrypted at rest and hidden from normal serialization. They are write-only in the interface after saving: keep them, replace them, or remove them, but Luminary does not reveal the stored value.</p>
            </article>
            <article class="panel nested-panel">
                <h2>Diagnostics</h2>
                <p class="panel-copy">If anonymous diagnostics are enabled, troubleshooting records can contain the provider request context and response. If disabled, normal chat-turn diagnostics omit those payloads but may retain status codes and error information.</p>
            </article>
            <article class="panel nested-panel">
                <h2>Website analytics</h2>
                <p class="panel-copy">GA4 measures page paths and basic engagement. The implementation strips query strings and reset tokens, and it does not intentionally send conversation text, memory contents, email addresses, or API keys. Add <code>?qa=1</code> once to disable Luminary GA4 for that browser; use <code>?qa=0</code> to turn it back on.</p>
            </article>
            <article class="panel nested-panel">
                <h2>Export and deletion</h2>
                <p class="panel-copy">Export creates a JSON copy of account chat data. Clear Memory permanently removes chat history, memories, and related chat-turn logs. Delete Account permanently removes the account and its dependent stored data after password and typed confirmation.</p>
            </article>
        </section>

        <section class="panel">
            <h2>A practical privacy checklist</h2>
            <ol class="panel-copy">
                <li>Use a separate, limited provider key.</li>
                <li>Choose provider/model privacy settings that fit the conversation.</li>
                <li>Turn off automatic extraction or cross-chat memory when continuity is not wanted.</li>
                <li>Disable diagnostics before especially sensitive sessions.</li>
                <li>Review and delete memories that should not persist.</li>
                <li>Export before clearing or deleting if you want a personal copy.</li>
            </ol>
            <p>For the legal policy, read <a href="{{ route('privacy') }}">Privacy</a>. For provider setup, read the <a href="{{ route('byok-guide') }}">BYOK guide</a>.</p>
        </section>
    </main>
@endsection
