<?php

namespace App\Helper;

use App\Entity\TournamentSessionTeamPlayer;

final class SessionTeamPlayerGrouper
{
    /**
     * @param list<TournamentSessionTeamPlayer> $players
     * @return array<int, list<TournamentSessionTeamPlayer>> sessionTeamId => players
     */
    public static function group(array $players): array
    {
        $index = [];
        foreach ($players as $player) {
            $index[$player->getTournamentSessionTeam()->getId()][] = $player;
        }

        return $index;
    }
}
