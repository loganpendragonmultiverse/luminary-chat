@extends('layouts.app', [
    'title' => 'Forgot Password | Luminary',
    'description' => 'Request a secure Luminary Chat password reset link.',
    'robotsMeta' => 'noindex,follow',
])

@section('content')
    <main class="auth-shell auth-stage recovery-stage">
        <section class="auth-copy-panel recovery-copy-panel">
            <p class="eyebrow">Account Recovery</p>
            <h1>Find your way back.</h1>
            <p>
                Enter the email connected to your Luminary account. If it matches an account, we will send a time-limited link for choosing a new password.
            </p>
            <div class="auth-proof-list" aria-label="Password recovery details">
                <span>Private recovery flow</span>
                <span>Time-limited reset link</span>
                <span>No account details exposed</span>
            </div>
        </section>

        <section class="auth-card recovery-card" aria-labelledby="forgot-password-title">
            <div class="recovery-card-heading">
                <span class="recovery-mark" aria-hidden="true">↗</span>
                <div>
                    <p class="eyebrow">Reset access</p>
                    <h2 id="forgot-password-title">Reset your password</h2>
                </div>
            </div>
            <p class="panel-copy">
                Enter your account email. For privacy, the response is the same whether or not an account exists.
            </p>

            <form method="POST" action="{{ route('password.email') }}" class="stack-form recovery-form" data-single-submit>
                @csrf
                <label>
                    <span>Email address</span>
                    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="you@example.com" required autofocus>
                </label>
                <label class="recovery-captcha-field">
                    <span>Bot check <strong>{{ $captchaQuestion }}</strong></span>
                    <input name="captcha_answer" value="{{ old('captcha_answer') }}" inputmode="numeric" autocomplete="off" placeholder="Enter the answer" required>
                </label>
                <button type="submit" class="primary-button">Send reset link</button>
            </form>

            <div class="recovery-card-foot">
                <span>Remembered your password?</span>
                <a href="{{ route('login') }}">Back to log in</a>
            </div>
        </section>
    </main>
@endsection
