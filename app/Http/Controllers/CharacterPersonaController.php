<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Services\CharacterRosterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CharacterPersonaController extends Controller
{
    public function update(Request $request, Character $character, string $fileName, CharacterRosterService $roster): RedirectResponse
    {
        $this->authorizeInstance($request, $character);
        $defaults = $roster->defaultPersonaDocuments($character->template_key);
        abort_unless(array_key_exists($fileName, $defaults), 404);

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);
        $documents = $character->persona_documents ?? $defaults;
        $prior = (string) ($documents[$fileName] ?? $defaults[$fileName]);

        if (trim($prior) === trim($data['content'])) {
            return back()->with('status', $fileName.' is unchanged.');
        }

        $documents[$fileName] = trim($data['content']);
        $character->update(['persona_documents' => $documents]);

        return back()->with('status', $character->name.' personal Soul updated. New replies will use this version.');
    }

    public function restore(Request $request, Character $character, CharacterRosterService $roster): RedirectResponse
    {
        $this->authorizeInstance($request, $character);
        $defaults = $roster->defaultPersonaDocuments($character->template_key);
        $character->update(['persona_documents' => $defaults]);

        return back()->with('status', $character->name.' personal Soul restored to the original default.');
    }

    private function authorizeInstance(Request $request, Character $character): void
    {
        abort_unless(
            $character->user_id === $request->user()->id
                && $character->is_system
                && $character->template_key !== 'luminary',
            404
        );
    }
}
