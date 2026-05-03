<?php

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionTeamPlayerDTO;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\ListMappingInterface;

final class SessionTeamPlayerMapping implements ListMappingInterface
{
    /**
     * @param array{squadInfo?: array{playerIds: list<int>, captainId: int|null}} $context
     * @return SessionTeamPlayerDTO
     */
    public static function mapTo(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var TournamentSessionTeamPlayer $source */
        $player = $source->getPlayer();
        $playerId = $player->getId();
        $squadInfo = $context['squadInfo'] ?? ['playerIds' => [], 'captainId' => null];

        return new $destinationClass(
            playerId: $playerId,
            playerName: $player->getFullName(),
            isBaseSquad: in_array($playerId, $squadInfo['playerIds'], true),
            isCaptain: $playerId === $squadInfo['captainId'],
        );
    }

    /** @return list<SessionTeamPlayerDTO> */
    public static function mapList(array $sources, string $destinationClass, array $context = []): array
    {
        return array_map(
            static fn(TournamentSessionTeamPlayer $source) => self::mapTo($source, $destinationClass, $context),
            $sources,
        );
    }
}
