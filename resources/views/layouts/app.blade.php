@php
    $pageTitle = $title ?? 'Luminary Chat';
    $pageDescription = $description ?? 'Luminary Chat is an self-hosted AI companion for private conversations, memory controls, and user-owned Venice and OpenRouter API keys.';
    $pageCanonical = $canonicalUrl ?? url()->current();
    $pageRobots = $robotsMeta ?? 'index, follow, max-image-preview:large';
    $isNoindex = str_contains(strtolower($pageRobots), 'noindex');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $pageRobots }}">
    @unless ($isNoindex)
        <link rel="canonical" href="{{ $pageCanonical }}">
    @endunless
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Luminary Chat">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    @unless ($isNoindex)
        <meta property="og:url" content="{{ $pageCanonical }}">
    @endunless
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Luminary Chat — a story-aware AI companion">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ asset('og-image.png') }}">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Luminary Chat',
        'url' => config('app.url'),
        'applicationCategory' => 'ChatApplication',
        'operatingSystem' => 'Web',
        'description' => 'A story-aware AI companion with private conversations and persistent memory controls.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @yield('structured-data')





    <link rel="stylesheet" href="{{ asset('luminary-chat.css') }}?v=recovery-forms-20260726-1">
    <link rel="stylesheet" href="{{ asset('luminary-chat-characters.css') }}?v=model-management-20260725-1">
    <script defer src="{{ asset('luminary-chat.js') }}?v=composer-polish4"></script>
    <script defer src="{{ asset('model-management.js') }}?v=model-management-20260725-1"></script>
<link rel="stylesheet" href="/mode-navigation.css?v=polish4">
<script defer src="/mode-navigation.js?v=polish5"></script></head>
@php($immersive = $immersive ?? false)
<body class="{{ $immersive ? 'immersive-app-body' : '' }}">
    @include('partials.site-header')
    <div id="main-content" class="{{ $immersive ? 'immersive-shell' : 'shell' }}" tabindex="-1">
        @guest
            <div class="app-intro-spacer" aria-hidden="true"></div>
        @endguest

        <div data-flash-region role="status" aria-live="polite">
            @if (session('status'))
                <div class="flash-message">{{ session('status') }}</div>
            @endif
        </div>

        <div data-error-region role="alert" aria-live="assertive">
            @if ($errors->any())
                <div class="error-stack">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
        </div>

        @yield('content')

        @unless($immersive)
            <footer class="lab-footer">
                <span>Luminary AI Companion</span>
                <nav>
                    <a href="{{ route('how-it-works') }}">How It Works</a>
                    <a href="{{ route('characters.index') }}">Characters</a>
                    <a href="{{ route('byok-guide') }}">BYOK Guide</a>
                    <a href="{{ route('memory-and-privacy') }}">Memory & Privacy</a>
                    <a href="{{ route('faq') }}">FAQ</a>
                    <a href="{{ route('terms') }}">Terms</a>
                    <a href="{{ route('privacy') }}">Privacy</a>
                    <a href="{{ route('contact') }}">Contact</a>
                </nav>
                <p>Self-hosted open-source software</p>
            </footer>
        @endunless
    </div>

</body>
</html>
