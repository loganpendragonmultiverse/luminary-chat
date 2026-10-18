@extends('layouts.app', [
    'title' => 'Model Management | Luminary Chat',
    'description' => 'Choose which connected Venice and OpenRouter text models appear in Luminary Chat and favorite the models you use most.',
    'robotsMeta' => 'noindex, follow',
])

@section('content')
    <main class="stack-layout model-management-page">
        <section class="panel model-management-hero">
            <div>
                <p class="eyebrow">Model management</p>
                <h1>Choose the models you want to see</h1>
                <p class="panel-copy">This list comes from your connected Venice and OpenRouter keys. Visible models appear in new-chat and conversation menus. Favorites appear first in their own group.</p>
            </div>
            <a href="{{ route('settings.index') }}" class="ghost-button">Manage provider keys</a>
        </section>

        @if ($modelCount === 0)
            <section class="panel empty-model-catalog">
                <h2>No provider models are connected</h2>
                <p>Add a Venice key, an OpenRouter key, or both in Settings. Luminary will read the text-model catalog available to those keys.</p>
                <a href="{{ route('settings.index') }}" class="primary-button">Open Settings</a>
            </section>
        @else
            <form method="POST" action="{{ route('models.update') }}" class="model-management-form">
                @csrf
                @method('PUT')
                <section class="panel model-management-toolbar">
                    <div class="model-management-counts" aria-label="Model preference summary">
                        <span><strong>{{ $modelCount }}</strong> connected</span>
                        <span><strong>{{ $visibleCount }}</strong> visible</span>
                        <span><strong>{{ $favoriteCount }}</strong> favorites</span>
                    </div>
                    <label class="model-search-field"><span>Find a model</span><input type="search" placeholder="Search name, provider, or model ID" data-model-search></label>
                    <button type="submit" class="primary-button">Save model preferences</button>
                </section>

                @foreach ($modelsByProvider as $provider => $models)
                    @php($providerLabel = $models->first()['provider_label'] ?? ucfirst($provider))
                    <section class="panel provider-model-section" data-model-provider>
                        <header>
                            <div><p class="eyebrow">{{ $providerLabel }}</p><h2>{{ $models->count() }} text models</h2></div>
                            <button type="button" class="ghost-button compact-action" data-show-provider-models>Show all</button>
                        </header>
                        <div class="model-preference-list">
                            @foreach ($models as $modelId => $model)
                                <article class="model-preference-row" data-model-row data-model-search-text="{{ strtolower($model['label'].' '.$providerLabel.' '.$modelId) }}">
                                    <div class="model-preference-copy">
                                        <strong>{{ $model['label'] }}</strong>
                                        <code>{{ $model['raw_id'] ?? $modelId }}</code>
                                        @if (! empty($model['description']))<small>{{ $model['description'] }}</small>@endif
                                    </div>
                                    <div class="model-preference-controls">
                                        <label class="checkbox-row"><input type="checkbox" name="visible_models[]" value="{{ $modelId }}" data-model-visible @checked(! $model['is_hidden'])><span>Show</span></label>
                                        <label class="checkbox-row favorite-model-toggle"><input type="checkbox" name="favorite_models[]" value="{{ $modelId }}" data-model-favorite @checked($model['is_favorite']) @disabled($model['is_hidden'])><span>Favorite</span></label>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
                <div class="sticky-model-save"><button type="submit" class="primary-button">Save model preferences</button></div>
            </form>
        @endif
    </main>
@endsection
