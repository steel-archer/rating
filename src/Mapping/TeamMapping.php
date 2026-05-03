<?php

namespace App\Mapping;

use App\DTO\Response\Team\SeasonSquadDTO;
use App\DTO\Response\Team\SquadPlayerDTO;
use App\DTO\Response\TeamDTO;
use App\Entity\Team;
use App\Entity\TeamPlayer;

#[AsMapper(source: Team::class, destination: TeamDTO::class)]
final class TeamMapping implements MappingInterface
{
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var Team $source */
        $mapper = $context['mapper'];

        $seasonGroups = [];
        foreach ($context['teamPlayers'] ?? [] as $tp) {
            $seasonName = $tp->getSeason()->getName();
            $seasonGroups[$seasonName][] = $mapper->map($tp, SquadPlayerDTO::class);
        }

        $squads = [];
        foreach ($seasonGroups as $seasonName => $players) {
            $squads[] = new SeasonSquadDTO($seasonName, $players);
        }

        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            townName: $source->getTown()->getName(),
            tournamentCount: $context['tournamentCount'] ?? 0,
            squads: $squads,
        );
    }
}
