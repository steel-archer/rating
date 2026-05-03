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
        $allTeamDTOs = [];

        foreach ($context['sessions'] ?? [] as $session) {
            $venue = $session->getVenue();
            $venueId = $venue->getId();
            $venueName = $venue->getName();
            $townName = $venue->getTown()->getName();

            $teamDTOs = [];
            foreach ($teamMap[$session->getId()] ?? [] as $sessionTeam) {
                $teamId = $sessionTeam->getTeam()->getId();
                $squadInfo = $squadMap[$teamId] ?? ['playerIds' => [], 'captainId' => null];
                $players = $playerMap[$sessionTeam->getId()] ?? [];

                $playerDTOs = array_map(
                    fn($player) => $mapper->map($player, SessionTeamPlayerDTO::class, ['squadInfo' => $squadInfo]),
                    $players,
                );

                $teamDTO = $mapper->map($sessionTeam, SessionTeamDTO::class, [
                    'venueId' => $venueId,
                    'venueName' => $venueName,
                    'townName' => $townName,
                    'players' => $playerDTOs,
                ]);
                $teamDTOs[] = $teamDTO;
                $allTeamDTOs[] = $teamDTO;
            }

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
