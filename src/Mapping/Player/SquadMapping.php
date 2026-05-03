<?php

namespace App\Mapping\Player;

use App\DTO\Response\Player\SquadDTO;
use App\Entity\TeamPlayer;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TeamPlayer::class, destination: SquadDTO::class)]
final class SquadMapping implements MappingInterface
{
    /**
     * @return SquadDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var TeamPlayer $source */
        return new $destinationClass(
            teamId: $source->getTeam()->getId(),
            teamName: $source->getTeam()->getName(),
            seasonName: $source->getSeason()->getName(),
            isCaptain: $source->isCaptain(),
        );
    }
}
