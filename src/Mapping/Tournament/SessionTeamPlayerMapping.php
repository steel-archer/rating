<?php

declare(strict_types=1);

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionTeamPlayerDTO;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentSessionTeamPlayer::class, destination: SessionTeamPlayerDTO::class)]
final class SessionTeamPlayerMapping implements MappingInterface
{
    /**
     * @param TournamentSessionTeamPlayer $source
     * @param array{squadInfo?: array{playerIds: list<int>, captainId: int|null}} $context
     * @return SessionTeamPlayerDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();
        $playerId = $player->getId();
        $squadInfo = $context['squadInfo'] ?? ['playerIds' => [], 'captainId' => null];

        return new $destinationClass(
            playerId: $playerId,
            playerName: $player->getFullName(),
            isBaseSquad: in_array($playerId, $squadInfo['playerIds'], true),
            isCaptain: $source->isCaptain(),
            hasUser: $player->hasUser(),
        );
    }
}
