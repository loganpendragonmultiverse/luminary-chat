@extends('layouts.app', [
    'title' => 'Reset Password | Luminary',
    'description' => 'Choose a new password for your Luminary Chat account.',
    'robotsMeta' => 'noindex,follow',
])

@section('content')
    <main class="auth-shell auth-stage recovery-stage">
        <section class="auth-copy-panel recovery-copy-panel">
            <p class="eyebrow">Secure Reset</p>
            <h1>Choose a fresh password.</h1>
            <p>
                Set a new password for your Luminary account, then return to the conversations, characters, rooms, and memories that belong to you.
            </p>
            <div class="auth-proof-list" aria-label="Password requirements">
                <span>At least 12 characters</span>
                <span>Stored as a secure hash</span>
                <span>Private account access</span>
            </div>
        </section>

        <section class="auth-card recovery-card" aria-labelledby="reset-password-title">
            <div class="recovery-card-heading">
                <span class="recovery-mark" aria-hidden="true">✓</span>
                <div>
                    <p class="eyebrow">Almost there</p>
                    <h2 id="reset-password-title">Choose a new password</h2>
                </div>
            </div>
            <p class="panel-copy">Use a unique password with at least 12 characters.</p>

            <form method="POST" action="{{ route('password.update') }}" class="stack-form recovery-form" data-single-submit>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <label>
                    <span>Account email</span>
                    <input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required>
                </label>
                <label>
                    <span>New password</span>
                    <input type="password" name="password" autocomplete="new-password" minlength="12" placeholder="12 characters or more" required autofocus>
                </label>
                <label>
                    <span>Confirm new password</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" placeholder="Repeat your new password" required>
                </label>
                <label class="recovery-captcha-field">
                    <span>Bot check <strong>{{ $captchaQuestion }}</strong></span>
                    <input name="captcha_answer" value="{{ old('captcha_answer') }}" inputmode="numeric" autocomplete="off" placeholder="Enter the answer" required>
                </label>
                <button type="submit" class="primary-button">Update password</button>
            </form>

            <div class="recovery-card-foot">
                <span>Need a new reset email?</span>
                <a href="{{ route('password.request') }}">Request another link</a>
            </div>
        </section>
    </main>
@endsection
