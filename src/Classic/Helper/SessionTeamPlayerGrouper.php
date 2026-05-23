<?php

declare(strict_types=1);

namespace App\Classic\Helper;

use App\Classic\Entity\TournamentSessionTeamPlayer;

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
