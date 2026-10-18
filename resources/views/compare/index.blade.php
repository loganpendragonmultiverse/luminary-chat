@extends('layouts.app', ['title' => 'Compare | Luminary Chat'])

@section('content')
    <main class="stack-layout">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Model Testing</p>
                    <h2>Comparison Lab</h2>
                    <p class="panel-copy">Run the same prompt across enabled Venice models to compare tone, quality, and fit before changing Luminary defaults.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.compare.store') }}" class="stack-form">
                @csrf
                <div class="dual-grid">
                    <label>
                        <span>Optional Luminary Profile</span>
                        <select name="character_id">
                            <option value="">No companion context</option>
                            @foreach ($characters as $character)
                                <option value="{{ $character->id }}" @selected(($selectedCharacterId ?? null) == $character->id)>{{ $character->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Models</span>
                        <select name="models[]" multiple size="4" required>
                            @include('chats.partials.model-option-groups', ['selectedModels' => $selectedModels ?? []])
                        </select>
                    </label>
                </div>

                <label>
                    <span>Prompt</span>
                    <textarea name="prompt" rows="6" required>{{ $prompt ?? '' }}</textarea>
                </label>

                <button type="submit" class="primary-button">Run Comparison</button>
            </form>
        </section>

        @if (!empty($results))
            <section class="comparison-grid">
                @foreach ($results as $modelId => $result)
                    <article class="panel nested-panel comparison-card">
                        <div class="card-heading">
                            <div>
                                <h3>{{ $result['label'] }}</h3>
                                <p>{{ $modelId }}</p>
                            </div>
                            <span class="tiny-pill">
                                @if (!empty($result['usage']))
                                    {{ data_get($result, 'usage.total_tokens', 'n/a') }} tokens
                                @else
                                    no usage data
                                @endif
                            </span>
                        </div>
                        <div class="message-content">{{ $result['content'] }}</div>
                    </article>
                @endforeach
            </section>
        @endif
    </main>
@endsection
