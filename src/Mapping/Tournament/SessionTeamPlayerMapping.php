<?php

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionTeamPlayerDTO;
use App\Entity\TournamentSessionTeamPlayer;

final class SessionTeamPlayerMapping
{
    /**
     * @param array{playerIds: list<int>, captainId: int|null} $squadInfo
     */
    public static function toDTO(TournamentSessionTeamPlayer $stp, array $squadInfo): SessionTeamPlayerDTO
    {
        $player = $stp->getPlayer();
        $playerId = $player->getId();

        return new SessionTeamPlayerDTO(
            playerId: $playerId,
            playerName: $player->getFullName(),
            isLegionary: $stp->isLegionary(),
            isCaptain: $playerId === $squadInfo['captainId'],
            isBaseSquad: in_array($playerId, $squadInfo['playerIds'], true),
        );
    }

    /**
     * @param array{playerIds: list<int>, captainId: int|null} $squadInfo
     * @return list<SessionTeamPlayerDTO>
     */
    public static function toDTOList(array $players, array $squadInfo): array
    {
        return array_map(
            static fn(TournamentSessionTeamPlayer $stp) => self::toDTO($stp, $squadInfo),
            $players,
        );
    }
}
