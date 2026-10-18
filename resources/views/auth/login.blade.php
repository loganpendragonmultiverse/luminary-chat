@extends('layouts.app', [
    'title' => 'Sign In to Luminary | AI Companion',
    'description' => 'Sign in to continue private, story-aware conversations with your Luminary AI companion.',
    'robotsMeta' => 'noindex, follow',
])

@section('content')
    @include('auth.partials.gate', ['mode' => 'login', 'intent' => 'account'])
@endsection
