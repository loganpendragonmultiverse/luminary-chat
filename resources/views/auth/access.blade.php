@extends('layouts.app', [
    'title' => 'Create a Luminary Account | AI Companion Access',
    'description' => 'Create an account to access Luminary, a story-aware AI companion with private chats, persistent memory controls, and user-owned API keys.',
    'robotsMeta' => 'noindex, follow',
])

@section('content')
    @include('auth.partials.gate', ['mode' => $mode ?? 'login', 'intent' => $intent ?? 'account'])
@endsection
