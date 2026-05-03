<?php

namespace App\Mapping;

use App\DTO\Response\Tournament\OfficialDTO;
use App\DTO\Response\Tournament\SessionDTO;
use App\DTO\Response\Tournament\SessionTeamDTO;
use App\DTO\Response\Tournament\SessionTeamPlayerDTO;
use App\DTO\Response\TournamentDTO;
use App\Entity\Tournament;
use App\Entity\TournamentOfficial;
use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamPlayer;

#[AsMapper(source: Tournament::class, destination: TournamentDTO::class)]
final class TournamentMapping implements MappingInterface
{
    /**
     * @param array{
     *     mapper: Mapper,
     *     officials: list<TournamentOfficial>,
     *     sessions: list<TournamentSession>,
     *     sessionTeams: list<TournamentSessionTeam>,
     *     sessionTeamPlayers: list<TournamentSessionTeamPlayer>,
     *     squadMap: array<int, array{playerIds: list<int>, captainId: int|null}>,
     * } $context
     * @return TournamentDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var Tournament $source */
        $mapper = $context['mapper'] ?? throw new \InvalidArgumentException('Mapper is required in context');
        $playerMap = self::groupBySessionTeam($context['sessionTeamPlayers'] ?? []);
        $teamMap = self::groupBySession($context['sessionTeams'] ?? []);
        $squadMap = $context['squadMap'] ?? [];

        $sessionDTOs = [];
        $allSessionTeams = []; // collect for place calculation

        foreach ($context['sessions'] ?? [] as $session) {
            $venue = $session->getVenue();
            $venueId = $venue->getId();
            $venueName = $venue->getName();
            $townName = $venue->getTown()->getName();

            foreach ($teamMap[$session->getId()] ?? [] as $sessionTeam) {
                $allSessionTeams[] = [
                    'sessionTeam' => $sessionTeam,
                    'venueId' => $venueId,
                    'venueName' => $venueName,
                    'townName' => $townName,
                    'session' => $session,
                ];
            }
        }

        // Calculate fractional places across entire tournament
        $scores = array_map(
            fn($entry) => $entry['sessionTeam']->getScore() ?? 0,
            $allSessionTeams,
        );
        rsort($scores);
        $places = self::fractionalRanks($scores);

        // Build DTOs with places
        $allTeamDTOs = [];
        $sessionTeamDTOs = []; // sessionId => list of teamDTOs

        foreach ($allSessionTeams as $entry) {
            $sessionTeam = $entry['sessionTeam'];
            $score = $sessionTeam->getScore() ?? 0;
            $place = $places[$score] ?? null;
            $teamId = $sessionTeam->getTeam()->getId();
            $squadInfo = $squadMap[$teamId] ?? ['playerIds' => [], 'captainId' => null];
            $players = $playerMap[$sessionTeam->getId()] ?? [];

            $playerDTOs = array_map(
                fn($player) => $mapper->map($player, SessionTeamPlayerDTO::class, ['squadInfo' => $squadInfo]),
                $players,
            );

            $teamDTO = $mapper->map($sessionTeam, SessionTeamDTO::class, [
                'venueId' => $entry['venueId'],
                'venueName' => $entry['venueName'],
                'townName' => $entry['townName'],
                'place' => $place,
                'players' => $playerDTOs,
            ]);

            $allTeamDTOs[] = $teamDTO;
            $sessionTeamDTOs[$entry['session']->getId()][] = $teamDTO;
        }

        foreach ($context['sessions'] ?? [] as $session) {
            $teamDTOs = $sessionTeamDTOs[$session->getId()] ?? [];
            $sessionDTOs[] = $mapper->map($session, SessionDTO::class, ['teams' => $teamDTOs]);
        }

        usort($allTeamDTOs, static fn($teamA, $teamB) => ($teamB->score ?? 0) <=> ($teamA->score ?? 0));

        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            seasonName: $source->getSeason()?->getName(),
            startedAt: $source->getStartedAt(),
            endedAt: $source->getEndedAt(),
            toursCount: $source->getToursCount(),
            questionsPerTour: $source->getQuestionsPerTour(),
            difficulty: $source->getDifficulty(),
            trueDl: $source->getTrueDl(),
            officials: self::groupOfficials($mapper, $context['officials'] ?? []),
            sessions: $sessionDTOs,
            allTeams: $allTeamDTOs,
        );
    }

    /**
     * Fractional ranking: score => average position for that score.
     *
     * @param list<int> $sortedScoresDesc
     * @return array<int, float> score => place
     */
    private static function fractionalRanks(array $sortedScoresDesc): array
    {
        $result = [];
        $position = 1;

        while ($position <= count($sortedScoresDesc)) {
            $score = $sortedScoresDesc[$position - 1];
            $count = 0;

            while ($position + $count <= count($sortedScoresDesc) && $sortedScoresDesc[$position + $count - 1] === $score) {
                $count++;
            }

            $rank = $position + ($count - 1) / 2;
            $result[$score] = $rank;
            $position += $count;
        }

        return $result;
    }

    /**
     * @return array<string, list<OfficialDTO>>
     */
    private static function groupOfficials(Mapper $mapper, array $officials): array
    {
        $grouped = [];
        foreach ($officials as $official) {
            $dto = $mapper->map($official, OfficialDTO::class);
            $grouped[$dto->role][] = $dto;
        }

        return $grouped;
    }

    /**
     * @return array<int, list<TournamentSessionTeamPlayer>> sessionTeamId => players
     */
    private static function groupBySessionTeam(array $sessionTeamPlayers): array
    {
        $index = [];
        foreach ($sessionTeamPlayers as $player) {
            $index[$player->getTournamentSessionTeam()->getId()][] = $player;
        }

        return $index;
    }

    /**
     * @return array<int, list<TournamentSessionTeam>> sessionId => teams
     */
    private static function groupBySession(array $sessionTeams): array
    {
        $index = [];
        foreach ($sessionTeams as $team) {
            $index[$team->getTournamentSession()->getId()][] = $team;
        }

        return $index;
    }
}
