<?php

namespace App\Mapping;

use App\DTO\Response\Team\SeasonSquadDTO;
use App\DTO\Response\Team\SquadPlayerDTO;
use App\DTO\Response\Team\TournamentEntryDTO;
use App\DTO\Response\Team\TournamentPlayerDTO;
use App\DTO\Response\TeamDTO;
use App\Entity\Team;
use App\Entity\TeamPlayer;
use App\Entity\TournamentSessionTeamPlayer;

#[AsMapper(source: Team::class, destination: TeamDTO::class)]
final class TeamMapping implements MappingInterface
{
    /**
     * @param array{
     *     mapper: Mapper,
     *     teamPlayers: list<TeamPlayer>,
     *     appearances: list<TournamentSessionTeamPlayer>,
     *     places: array<int, float>,
     * } $context
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var Team $source */
        $mapper = $context['mapper'];
        $places = $context['places'] ?? [];

        // Group players by season
        $seasonGroups = [];
        foreach ($context['teamPlayers'] ?? [] as $tp) {
            $seasonName = $tp->getSeason()->getName();
            $seasonGroups[$seasonName][] = $mapper->map($tp, SquadPlayerDTO::class);
        }

        $squads = [];
        foreach ($seasonGroups as $seasonName => $players) {
            $squads[] = new SeasonSquadDTO($seasonName, $players);
        }

        // Group appearances by session team (tournament)
        $tournamentGroups = [];
        foreach ($context['appearances'] ?? [] as $appearance) {
            $stId = $appearance->getTournamentSessionTeam()->getId();
            $tournamentGroups[$stId][] = $appearance;
        }

        $tournaments = [];
        foreach ($tournamentGroups as $stId => $appearances) {
            $first = $appearances[0];
            $st = $first->getTournamentSessionTeam();
            $session = $st->getTournamentSession();

            $players = array_map(
                static fn(TournamentSessionTeamPlayer $a) => $mapper->map($a, TournamentPlayerDTO::class),
                $appearances,
            );

            $tournaments[] = new TournamentEntryDTO(
                tournamentId: $session->getTournament()->getId(),
                tournamentName: $session->getTournament()->getName(),
                playedAt: $session->getPlayedAt(),
                score: $st->getScore(),
                place: $places[$stId] ?? null,
                players: $players,
            );
        }

        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            townName: $source->getTown()->getName(),
            squads: $squads,
            tournaments: $tournaments,
        );
    }
}
