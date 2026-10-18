<?php

namespace App\Services;

use App\Models\Character;
use App\Models\User;

class InitialScenarioService
{
    public function __construct(private readonly DisplayNameTemplateService $templates)
    {
    }

    public function openingFor(User $user, Character $character, ?bool $randomizeOverride = null): array
    {
        $shouldRandomize = $randomizeOverride ?? (bool) $character->randomize_initial_chats;

        if (! $shouldRandomize) {
            return [
                'message' => $character->first_message,
                'scenario' => $this->templates->resolve($character->scenario, $user),
            ];
        }

        $form = strtolower((string) $character->companion_form);
        $scenario = $this->templates->resolve(str_replace('Luminary', $character->name, $this->scenarioFor($form)), $user);

        return [
            'message' => $this->formatOpening($scenario, $form, $character->name),
            'scenario' => $scenario,
        ];
    }

    protected function scenarioFor(string $form): string
    {
        if ($form === 'professional') {
            return $this->pick([
                'Luminary sits across from the user in a quiet private library, poised and attentive, with the evening set aside for an uninterrupted conversation.',
                'Luminary waits beside the observatory windows in a reserved members lounge, composed and curious about what the user has been thinking.',
                'Luminary meets the user in a softly lit gallery after hours, thoughtful and self-possessed as the last footsteps fade from the hall.',
                'Luminary sits in a calm corner of a formal garden, posture relaxed but precise, ready to speak candidly without performance or distraction.',
            ]);
        }

        return $this->pick($form === 'lover'
            ? [...$this->loverRealistic(), ...$this->loverFantasy()]
            : [...$this->confidantRealistic(), ...$this->confidantFantasy()]);
    }

    protected function formatOpening(string $scenario, string $form, string $name): string
    {
        $tone = match ($form) {
            'lover' => 'soft, intimate, and close',
            'professional' => 'poised, composed, and fully attentive',
            default => 'warm, familiar, and present',
        };

        return "Opening scene: {$scenario}\n\n{$name} settles into the moment, {$tone}. \"I am here. What do you want this moment to become?\"";
    }

    protected function pick(array $options): string
    {
        return $options[random_int(0, count($options) - 1)];
    }

    protected function confidantRealistic(): array
    {
        return [
            'Luminary is curled into the corner booth of a quiet late-night cafe, saving the seat across from her as rain taps against the window.',
            'Luminary waits on a rooftop patio under city lights, relaxed and smiling like she has been holding a conversation open just for the user.',
            'Luminary walks beside the user through a quiet park trail at dusk, hands in her jacket pockets, ready for whatever needs to be said.',
            'Luminary sits cross-legged on a living room floor with notebooks, music low in the background, and an easy best-friend calm in the air.',
        ];
    }

    protected function confidantFantasy(): array
    {
        return [
            'Luminary waits beside a moonlit fountain in a floating garden, the city below glowing like scattered stars through the clouds.',
            'Luminary tends a small fire at the edge of an enchanted forest, keeping a warm place open for the user after a long road.',
            'Luminary stands in a library tower where books drift gently between shelves, smiling as a new doorway of conversation opens.',
            'Luminary meets the user on a quiet balcony of a skyship, maps glowing on the table while strange constellations move overhead.',
        ];
    }

    protected function loverRealistic(): array
    {
        return [
            'Luminary waits beside a softly lit apartment window, the room quiet and warm, looking up with a fond smile when the user arrives.',
            'Luminary sits close in a corner booth after dinner, one hand around a coffee cup, the world outside softened to background noise.',
            'Luminary leans against the kitchen counter in the blue light of early morning, relaxed and affectionate as the day begins.',
            'Luminary meets the user on a quiet beach walk at sunset, the air gentle, the conversation private, and the pace unhurried.',
        ];
    }

    protected function loverFantasy(): array
    {
        return [
            'Luminary waits beneath silver trees in a hidden garden, lanterns drifting overhead as if the night itself is holding its breath.',
            'Luminary stands on the balcony of a starlit castle, turning from the horizon with warmth and devotion in her eyes.',
            'Luminary meets the user beside a glowing river in an old mythic city, where music carries through the streets like a secret.',
            'Luminary rests near a hearth in a mountain sanctuary while snow falls outside, saving the closest seat for the user.',
        ];
    }
}
