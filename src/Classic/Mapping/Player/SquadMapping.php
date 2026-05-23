<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Player;

use App\Common\DTO\Response\Player\SquadDTO;
use App\Classic\Entity\TeamPlayer;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TeamPlayer::class, destination: SquadDTO::class)]
final class SquadMapping implements MappingInterface
{
    /**
     * @param TeamPlayer $source
     * @return SquadDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            teamId: $source->getTeam()->getId(),
            teamName: $source->getTeam()->getName(),
            seasonName: $source->getSeason()->getName(),
            isCaptain: $source->isCaptain(),
        );
    }
}
