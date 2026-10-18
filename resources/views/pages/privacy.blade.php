@extends('layouts.app', ['title' => 'Privacy'])
@section('content')
<section class="shell narrow"><h1>Privacy</h1><p>Accounts, submitted content, sessions and necessary security records are stored by this instance operator. Use account settings to export or delete supported account data. Backups and hosting logs have separate retention. No bundled analytics or author feedback service is enabled. External email and AI providers process data when configured.</p><p>Contact this instance operator through the <a href="{{ route('contact') }}">contact form</a>. Operators must review these notices for their deployment.</p></section>
@endsection
