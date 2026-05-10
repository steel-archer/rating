<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\SquadPlayerEditDTO;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentSessionTeamPlayer::class, destination: SquadPlayerEditDTO::class)]
final class SquadPlayerEditMapping implements MappingInterface
{
    /**
     * @param TournamentSessionTeamPlayer $source
     * @return SquadPlayerEditDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();

        return new $destinationClass(
            id: $player->getId(),
            name: $player->getFullName(),
            isCaptain: $source->isCaptain(),
        );
    }
}
