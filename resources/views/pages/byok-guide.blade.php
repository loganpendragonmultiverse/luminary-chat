@extends('layouts.app', [
    'title' => 'BYOK Guide for Venice and OpenRouter | Luminary Chat',
    'description' => 'Set up a user-owned Venice or OpenRouter API key in Luminary Chat, understand model access and billing, and follow practical key-safety steps.',
])

@section('content')
    <main class="stack-layout legal-page">
        <section class="panel">
            <p class="eyebrow">Bring Your Own Key</p>
            <h1>Connect Luminary to Venice or OpenRouter safely</h1>
            <p class="panel-copy">In Luminary Chat, BYOK means supplying your own Venice inference key, OpenRouter inference API key, or both. Your provider account controls access, usage limits, pricing, and the models available to you; Luminary does not issue provider credentials.</p>
        </section>

        <section class="panel">
            <h2>Before you add a key</h2>
            <ol class="panel-copy">
                <li>Create a separate key for Luminary instead of reusing a broadly shared credential.</li>
                <li>Choose a normal inference key rather than a provider-management credential. For Venice, prefer an inference-only key; Luminary does not need Admin access.</li>
                <li>Set provider spending or consumption limits when available.</li>
                <li>Never paste a key into a chat message, contact form, feedback report, screenshot, repository, or support email.</li>
            </ol>
        </section>

        <section class="legal-grid">
            <article class="panel nested-panel">
                <h2>Venice setup</h2>
                <ol class="panel-copy">
                    <li>Open Venice API settings and generate a new key.</li>
                    <li>Prefer an inference-only key and set an expiration or consumption limit if appropriate.</li>
                    <li>Paste it into Luminary Settings under Venice API Key and save.</li>
                    <li>Choose one of the models Luminary discovers from that key.</li>
                </ol>
                <p><a href="https://docs.venice.ai/guides/getting-started/generating-api-key" rel="noopener noreferrer">Official Venice key instructions</a></p>
            </article>
            <article class="panel nested-panel">
                <h2>OpenRouter setup</h2>
                <ol class="panel-copy">
                    <li>Create a normal OpenRouter API key with a useful label and an appropriate credit limit. Do not use a Management API Key, which is intended to administer other keys rather than call models.</li>
                    <li>Review OpenRouter privacy settings and the data policy for the model/provider endpoints you intend to use.</li>
                    <li>Paste the key into Luminary Settings under OpenRouter API Key and save.</li>
                    <li>Choose one of the text-chat models returned for your account.</li>
                </ol>
                <p><a href="https://openrouter.ai/docs/api/api-reference/api-keys/create-keys" rel="noopener noreferrer">Official OpenRouter key instructions</a></p>
            </article>
        </section>

        <section class="panel">
            <h2>What happens when you save</h2>
            <p class="panel-copy">Luminary validates a saved credential with the selected provider's key and model endpoints, then rebuilds the available text-chat model catalog from the keys that remain connected. Valid keys are encrypted at rest and are not displayed back after saving. Removing a key removes it from your Luminary account and refreshes the catalog.</p>
        </section>

        <section class="legal-grid">
            <article class="panel nested-panel">
                <h2>Billing and limits</h2>
                <p class="panel-copy">Luminary does not sell model credits or add a Luminary usage charge. Venice or OpenRouter usage is billed and rate-limited under your provider account. Model pricing can change, so confirm it with the provider before starting a long session.</p>
            </article>
            <article class="panel nested-panel">
                <h2>Provider privacy</h2>
                <p class="panel-copy">The character prompt, selected memory/context, and recent conversation needed for a reply are sent to the chosen provider. Venice documents privacy modes and private API inference. OpenRouter says it does not store prompts and outputs unless the account opts in, but an underlying model provider can have different retention or training rules. Luminary does not currently add a per-request Zero Data Retention flag, so review your OpenRouter account privacy settings and confirm the policy of each endpoint you select.</p>
                <p><a href="https://docs.venice.ai/welcome/privacy" rel="noopener noreferrer">Venice API privacy</a> &middot; <a href="https://openrouter.ai/docs/guides/privacy/data-collection" rel="noopener noreferrer">OpenRouter data collection</a> &middot; <a href="https://openrouter.ai/docs/guides/privacy/provider-logging" rel="noopener noreferrer">provider logging policies</a> &middot; <a href="https://openrouter.ai/docs/guides/features/zdr" rel="noopener noreferrer">Zero Data Retention</a></p>
            </article>
        </section>

        <section class="panel">
            <h2>If a key or model does not appear</h2>
            <p class="panel-copy">Confirm that the credential is an inference-capable key, that it has not expired or reached its provider limit, and that the provider account can access at least one text-chat model. Then save it again in <a href="{{ route('settings.index') }}">Settings</a>. A provider can change model availability independently of Luminary.</p>
            <p class="panel-copy">For what Luminary stores and sends, review the <a href="{{ route('memory-and-privacy') }}">Memory &amp; Privacy guide</a> and <a href="{{ route('privacy') }}">Privacy Policy</a>.</p>
        </section>

        <section class="panel">
            <h2>Rotate or revoke immediately if a key leaks</h2>
            <p class="panel-copy">Remove the exposed key from Luminary, revoke it in the provider dashboard, create a replacement with a new limit, and save the replacement in Settings. Do not rely on hiding the message or screenshot where the key appeared.</p>
            <p><a href="{{ route('memory-and-privacy') }}">Continue to the Memory & Privacy guide</a>.</p>
        </section>
    </main>
@endsection
