@php
    $activeMode = $mode ?? (request()->routeIs('register') ? 'register' : 'login');
@endphp

<main class="auth-shell auth-stage dual-auth-stage">
    <section class="auth-copy-panel">
        <p class="eyebrow">Luminary AI Companion</p>
        <h1>Return to your companion space.</h1>
        <p>
            Log in or register from one screen. Luminary saves conversations, memory, companion settings, privacy choices, and your encrypted provider keys to your account.
        </p>
        <div class="auth-proof-list">
            <span>Live registration</span>
            <span>Adults 18+ only</span>
            <span>Venice + OpenRouter BYOK</span>
            <span>Private memory controls</span>
        </div>
    </section>

    <section class="auth-card dual-auth-card">
        <input type="radio" name="auth_mode" id="auth-mode-login" class="auth-mode-input" {{ $activeMode !== 'register' ? 'checked' : '' }}>
        <input type="radio" name="auth_mode" id="auth-mode-register" class="auth-mode-input" {{ $activeMode === 'register' ? 'checked' : '' }}>

        <div class="auth-switch" role="tablist" aria-label="Account access">
            <label for="auth-mode-login" class="auth-switch-tab auth-switch-login" role="tab">Log in</label>
            <label for="auth-mode-register" class="auth-switch-tab auth-switch-register" role="tab">Register</label>
        </div>

        <div class="auth-panel auth-panel-login">
            <p class="eyebrow">Account Access</p>
            <h2>Log in</h2>
            <p class="panel-copy">Continue your conversations, memory, companion profile, and settings.</p>

            <form method="POST" action="{{ route('login.store') }}" class="stack-form" data-ga-event="login" data-ga-label="Luminary login form">
                @csrf
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required>
                </label>
                <label class="checkbox-row">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me signed in on this device</span>
                </label>
                <button type="submit" class="primary-button">Log in</button>
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </form>
        </div>

        <div class="auth-panel auth-panel-register">
            <p class="eyebrow">Live Release</p>
            <h2>Create your Luminary account</h2>
            <p class="panel-copy">Create an account, add a Venice or OpenRouter API key in Settings, and start chatting with Luminary.</p>

            <div class="adult-content-warning" role="note" aria-label="Adults-only content warning">
                <strong>Adults 18+ only.</strong>
                <span>Some characters can use uncensored AI models. Responses may become explicit, intense, offensive, or otherwise unsuitable for minors.</span>
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="stack-form" data-single-submit data-ga-event="sign_up" data-ga-label="Luminary registration form">
                @csrf
                <label>
                    <span>Name</span>
                    <input type="text" name="name" value="{{ old('name') }}" required>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </label>
                <label>
                    <span>Date of birth</span>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" max="{{ today()->subYears(18)->toDateString() }}" autocomplete="bday" required>
                    <small>You must be at least 18 years old to create an account.</small>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required>
                </label>
                <label>
                    <span>Confirm password</span>
                    <input type="password" name="password_confirmation" required>
                </label>
                <label class="checkbox-row terms-acceptance-row">
                    <input type="checkbox" name="terms_accepted" value="1" required @checked(old('terms_accepted'))>
                    <span>I have read and agree to the <a href="{{ route('terms') }}">Terms of Service</a>.</span>
                </label>
                <button type="submit" class="primary-button">Register</button>
            </form>
        </div>

        <div class="auth-card-foot">
            <a href="{{ route('welcome') }}" class="ghost-button subtle-link">Back to Luminary</a>
        </div>
    </section>
</main>
