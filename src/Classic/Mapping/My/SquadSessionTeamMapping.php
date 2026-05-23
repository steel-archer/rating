<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\SquadSessionTeamDTO;
use App\Classic\Entity\TournamentSessionTeam;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TournamentSessionTeam::class, destination: SquadSessionTeamDTO::class)]
final class SquadSessionTeamMapping implements MappingInterface
{
    /**
     * @param TournamentSessionTeam $source
     * @return SquadSessionTeamDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $team = $source->getTeam();

        return new $destinationClass(
            id: $source->getId(),
            teamId: $team->getId(),
            teamName: $team->getName(),
            teamTownName: $team->getTown()->getName(),
            oneTimeName: $source->getOneTimeName(),
        );
    }
}
