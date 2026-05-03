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
        $squadInfoBySeason = [];
        foreach ($context['teamPlayers'] ?? [] as $tp) {
            $seasonId = $tp->getSeason()->getId();
            $seasonName = $tp->getSeason()->getName();
            $seasonGroups[$seasonName][] = $mapper->map($tp, SquadPlayerDTO::class);

            if (!isset($squadInfoBySeason[$seasonId])) {
                $squadInfoBySeason[$seasonId] = ['playerIds' => [], 'captainId' => null];
            }
            $squadInfoBySeason[$seasonId]['playerIds'][] = $tp->getPlayer()->getId();
            if ($tp->isCaptain()) {
                $squadInfoBySeason[$seasonId]['captainId'] = $tp->getPlayer()->getId();
            }
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

            $seasonId = $session->getTournament()->getSeason()?->getId();
            $squadInfo = $squadInfoBySeason[$seasonId] ?? ['playerIds' => [], 'captainId' => null];

            $players = array_map(
                static fn(TournamentSessionTeamPlayer $a) => $mapper->map($a, TournamentPlayerDTO::class, ['squadInfo' => $squadInfo]),
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
