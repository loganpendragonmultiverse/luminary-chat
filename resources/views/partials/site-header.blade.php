        <header class="luminary-site-header">
        <nav class="luminary-site-nav" aria-label="Luminary navigation">
            <a href="{{ route('welcome') }}" class="luminary-brand subtle-link">
                <span class="luminary-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </span>
                <span>
                    <strong>LUMINARY</strong>
                    <small>AI Companion</small>
                </span>
            </a>

            <div class="luminary-nav-links">
                <a href="{{ route('welcome') }}">Home</a>
                <a href="{{ route('characters.index') }}">Chat Mode</a>
                <a href="/companion/cottage-20261012/">Companion Mode</a>
                <a href="{{ route('change-log') }}">Change Log</a>
            </div>

            <div class="luminary-nav-actions">
                @auth
                    <a href="{{ route('characters.index') }}" class="nav-text-link">Characters</a><a href="{{ route('settings.entry') }}" class="nav-text-link">Account</a>@if(auth()->user()->is_admin)<a href="{{ route('admin.index') }}" class="nav-text-link">Admin</a>@endif<form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-text-button">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-text-link">Log In</a>
                    <a href="{{ route('register') }}" class="nav-text-link">Register</a>
                    <a href="{{ route('chat.start') }}" class="nav-start-button">Start Chat</a>
                @endauth
            </div>
        </nav>
        </header>
