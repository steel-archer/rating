<?php

declare(strict_types=1);

namespace App\Mapping;

use App\DTO\Response\Player\SquadDTO;
use App\DTO\Response\PlayerDTO;
use App\Entity\Player;
use App\Entity\TeamPlayer;

#[AsMapper(source: Player::class, destination: PlayerDTO::class)]
final class PlayerMapping implements MappingInterface
{
    /**
     * @param Player $source
     * @return PlayerDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $mapper = $context['mapper'];

        $squads = array_map(
            static fn(TeamPlayer $tp) => $mapper->map($tp, SquadDTO::class),
            $context['squads'] ?? [],
        );

        return new $destinationClass(
            id: $source->getId(),
            fullName: $source->getFullName(),
            townName: $source->getTown()?->getName(),
            hasUser: $source->hasUser(),
            tournamentCount: $context['tournamentCount'] ?? 0,
            squads: $squads,
        );
    }
}
