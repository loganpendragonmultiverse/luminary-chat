@extends('layouts.app', [
    'title' => 'Create a Luminary Account | AI Companion',
    'description' => 'Create a private Luminary Chat account to start story-aware AI companion conversations using your own provider key.',
    'robotsMeta' => 'noindex, follow',
])

@section('content')
    @include('auth.partials.gate', ['mode' => 'register', 'intent' => 'account'])
@endsection
