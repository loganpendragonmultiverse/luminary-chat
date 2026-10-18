@extends('layouts.app', ['title' => 'Version Preview | Luminary Chat'])

@section('content')
    <main class="stack-layout">
        <section class="panel">
            <div class="panel-heading split-heading">
                <div>
                    <p class="eyebrow">Version Preview</p>
                    <h2>{{ $character->name }}</h2>
                    <p class="panel-copy">Review what would change before restoring this version.</p>
                </div>
                <a href="{{ route('characters.index') }}" class="nav-link">Back To Characters</a>
            </div>
        </section>

        <section class="panel">
            <div class="card-heading">
                <div>
                    <h3>{{ $version->label ?: 'Saved version' }}</h3>
                    <p>{{ $version->created_at->diffForHumans() }}</p>
                </div>
                <form method="POST" action="{{ route('characters.versions.restore', [$character, $version]) }}" onsubmit="return confirm('Restore this version? Current character settings will be replaced.');">
                    @csrf
                    <button type="submit" class="primary-button">Restore This Version</button>
                </form>
            </div>
        </section>

        <section class="comparison-grid">
            @forelse ($diff as $row)
                <article class="panel nested-panel comparison-card">
                    <h3>{{ str($row['field'])->replace('_', ' ')->title() }}</h3>
                    <label>
                        <span>Current</span>
                        <textarea rows="5" readonly>{{ $row['current'] }}</textarea>
                    </label>
                    <label>
                        <span>Version</span>
                        <textarea rows="5" readonly>{{ $row['version'] }}</textarea>
                    </label>
                </article>
            @empty
                <p class="empty-note">This version matches the current character state.</p>
            @endforelse
        </section>
    </main>
@endsection
