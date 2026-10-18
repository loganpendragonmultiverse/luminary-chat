@php
    $favoriteModels = collect($modelOptions)->filter(fn (array $model) => ($model['status'] ?? 'active') === 'active' && ($model['is_favorite'] ?? false));
    $otherModels = collect($modelOptions)->filter(fn (array $model) => ($model['status'] ?? 'active') === 'active' && ! ($model['is_favorite'] ?? false));
@endphp

@if ($favoriteModels->isNotEmpty())
    <optgroup label="Favorites">
        @foreach ($favoriteModels as $modelId => $model)
            <option value="{{ $modelId }}" @selected(($selectedModel ?? null) === $modelId || in_array($modelId, $selectedModels ?? [], true))>{{ $model['label'] }} · {{ $model['provider_label'] ?? ucfirst($model['provider']) }}</option>
        @endforeach
    </optgroup>
@endif
@if ($otherModels->isNotEmpty())
    <optgroup label="All models">
        @foreach ($otherModels as $modelId => $model)
            <option value="{{ $modelId }}" @selected(($selectedModel ?? null) === $modelId || in_array($modelId, $selectedModels ?? [], true))>{{ $model['label'] }} · {{ $model['provider_label'] ?? ucfirst($model['provider']) }}</option>
        @endforeach
    </optgroup>
@endif
