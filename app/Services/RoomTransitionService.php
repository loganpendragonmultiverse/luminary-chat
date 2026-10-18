<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Room;

class RoomTransitionService
{
    public function apply(Chat $chat, string $content): ?Room
    {
        $chat->loadMissing('character.rooms');
        $normalized = strtolower((string) preg_replace('/\s+/', ' ', trim($content)));

        foreach ($chat->character->rooms as $room) {
            $aliases = array_unique([$room->slug, str_replace('-', ' ', $room->slug), strtolower($room->name)]);
            if ($room->slug === 'bed-room') {
                $aliases[] = 'bedroom';
            }

            foreach ($aliases as $alias) {
                if (preg_match('/\[room:\s*'.preg_quote($alias, '/').'\s*\]/i', $content)) {
                    return $this->move($chat, $room);
                }

                $destination = '(?:to|into|inside|in)\s+(?:the\s+)?'.preg_quote($alias, '/');
                $movement = '(?:go|goes|went|move|moves|moved|head|heads|headed|walk|walks|walked|come|comes|came|take|takes|took|bring|brings|brought|lead|leads|led|follow|follows|followed|enter|enters|entered|step|steps|stepped|return|returns|returned)';
                if (preg_match('/\b'.$movement.'.{0,120}\b'.$destination.'\b/i', $normalized)
                    || preg_match('/\b(?:we are|we\'re|i am|i\'m|you are|you\'re)\s+(?:now\s+)?'.$destination.'\b/i', $normalized)) {
                    return $this->move($chat, $room);
                }
            }
        }

        return null;
    }

    public function withoutControlDirectives(string $content): string
    {
        return trim((string) preg_replace('/\s*\[room:\s*[^\]\r\n]+\s*\]\s*/i', ' ', $content));
    }

    private function move(Chat $chat, Room $room): Room
    {
        if ($chat->room_id !== $room->id) {
            $chat->update(['room_id' => $room->id]);
        }

        return $room;
    }
}
