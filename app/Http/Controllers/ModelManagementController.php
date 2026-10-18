<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Services\ModelCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModelManagementController extends Controller
{
    public function index(Request $request, ModelCatalogService $catalog): View
    {
        $models = collect($catalog->allForUser($request->user()));

        return view('models.index', [
            'modelsByProvider' => $models->groupBy('provider'),
            'modelCount' => $models->count(),
            'visibleCount' => $models->where('is_hidden', false)->count(),
            'favoriteCount' => $models->where('is_favorite', true)->count(),
        ]);
    }

    public function update(Request $request, ModelCatalogService $catalog): RedirectResponse
    {
        $data = $request->validate([
            'visible_models' => ['nullable', 'array'],
            'visible_models.*' => ['string', 'max:255'],
            'favorite_models' => ['nullable', 'array'],
            'favorite_models.*' => ['string', 'max:255'],
        ]);

        $visibleModels = $data['visible_models'] ?? [];
        $allModels = $catalog->allForUser($request->user());
        $visibleLookup = array_fill_keys($visibleModels, true);

        $hiddenDefaults = Character::query()
            ->where('user_id', $request->user()->id)
            ->get(['name', 'default_model'])
            ->filter(function (Character $character) use ($allModels, $visibleLookup, $catalog) {
                $storedId = isset($allModels[$character->default_model])
                    ? $character->default_model
                    : $catalog->storedId(ModelCatalogService::VENICE, $character->default_model);

                return isset($allModels[$storedId]) && ! isset($visibleLookup[$storedId]);
            })
            ->pluck('name');

        if ($hiddenDefaults->isNotEmpty()) {
            return back()->withErrors([
                'visible_models' => 'Choose a different default model for '.$hiddenDefaults->join(', ', ' and ').' before hiding the current default.',
            ]);
        }

        $catalog->savePreferences($request->user(), $visibleModels, $data['favorite_models'] ?? []);

        return back()->with('status', 'Model preferences updated. New model menus now use your visible and favorite choices.');
    }
}
