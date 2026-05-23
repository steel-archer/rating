<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\SquadPlayerEditDTO;
use App\Classic\Entity\TournamentSessionTeamPlayer;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
