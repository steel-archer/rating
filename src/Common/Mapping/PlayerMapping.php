<?php

declare(strict_types=1);

namespace App\Common\Mapping;

use App\Common\DTO\Response\Player\SquadDTO;
use App\Common\DTO\Response\PlayerDTO;
use App\Common\Entity\Player;

#[AsMapper(source: Player::class, destination: PlayerDTO::class)]
final class PlayerMapping implements MappingInterface
{
    /**
     * @param Player $source
     * @return PlayerDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var list<SquadDTO> $squads */
        $squads = $context['squads'] ?? [];

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
