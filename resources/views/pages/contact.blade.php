@extends('layouts.app', [
    'title' => 'Contact | Luminary Chat',
    'description' => 'Contact the Luminary Chat team with product feedback, account questions, privacy requests, or accessibility concerns.',
])

@section('content')
    <main class="contact-stage">
        <section class="contact-copy">
            <p class="eyebrow">Contact</p>
            <h1>Contact Luminary Chat</h1>
            <p>Use this for product feedback, bugs, account issues, Venice key questions, privacy requests, or feature ideas.</p>
            <div class="info-block">
                <strong>Prefer email?</strong>
                <p>Contact this instance operator using this form.</p>
            </div>
        </section>

        <form method="POST" action="{{ route('contact.store') }}" class="auth-card contact-form">
            @csrf
            <p class="eyebrow">Message</p>
            <h2>Send a note</h2>
            @if (session('status'))
                <div class="flash-message" data-ga-confirmed-event="generate_lead" data-ga-label="Luminary confirmed contact submission">{{ session('status') }}</div>
            @endif
            <div class="stack-form compact-form">
                <label><span>Name</span><input name="name" value="{{ old('name') }}" required></label>
                <label><span>Email</span><input type="email" name="email" value="{{ old('email') }}" required></label>
                <label><span>Subject</span><input name="subject" value="{{ old('subject') }}" required></label>
                <label><span>Message</span><textarea name="message" rows="7" required>{{ old('message') }}</textarea></label>
                <label aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden"><span>Address</span><input name="address" tabindex="-1" autocomplete="off"></label>
                <label><span>Bot check: {{ $captchaQuestion }}</span><input name="captcha_answer" required></label>
                <button class="primary-button" type="submit">Send Message</button>
            </div>
        </form>
    </main>
@endsection
