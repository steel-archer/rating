<?php

namespace App\Mapping;

use App\DTO\Response\TournamentDTO;
use App\Entity\Tournament;
use App\Entity\TournamentOfficial;
use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\Tournament\OfficialMapping;
use App\Mapping\Tournament\SessionMapping;
use App\Mapping\Tournament\SessionTeamMapping;
use App\Mapping\Tournament\SessionTeamPlayerMapping;

final class TournamentMapping
{
    /**
     * @param list<TournamentOfficial>          $officials
     * @param list<TournamentSession>           $sessions
     * @param list<TournamentSessionTeam>       $sessionTeams
     * @param list<TournamentSessionTeamPlayer> $sessionTeamPlayers
     * @param array<int, array{playerIds: list<int>, captainId: int|null}> $squadMap teamId => squadInfo
     */
    public static function toDTO(
        Tournament $tournament,
        array $officials,
        array $sessions,
        array $sessionTeams,
        array $sessionTeamPlayers,
        array $squadMap,
    ): TournamentDTO {
        $playerIndex = self::indexBySessionTeam($sessionTeamPlayers);
        $teamIndex = self::indexBySession($sessionTeams);

        $sessionDTOs = [];
        $allTeamDTOs = [];

        foreach ($sessions as $session) {
            $venue = $session->getVenue();
            $venueName = $venue->getName();
            $townName = $venue->getTown()->getName();

            $teamDTOs = [];
            foreach ($teamIndex[$session->getId()] ?? [] as $sessionTeam) {
                $teamId = $sessionTeam->getTeam()->getId();
                $squadInfo = $squadMap[$teamId] ?? ['playerIds' => [], 'captainId' => null];
                $players = $playerIndex[$sessionTeam->getId()] ?? [];

                $teamDTO = SessionTeamMapping::toDTO(
                    $sessionTeam,
                    $venueName,
                    $townName,
                    SessionTeamPlayerMapping::toDTOList($players, $squadInfo),
                );
                $teamDTOs[] = $teamDTO;
                $allTeamDTOs[] = $teamDTO;
            }

            $sessionDTOs[] = SessionMapping::toDTO($session, $teamDTOs);
        }

        usort($allTeamDTOs, static fn($a, $b) => ($b->score ?? 0) <=> ($a->score ?? 0));

        return new TournamentDTO(
            id: $tournament->getId(),
            name: $tournament->getName(),
            seasonName: $tournament->getSeason()?->getName(),
            startedAt: $tournament->getStartedAt(),
            endedAt: $tournament->getEndedAt(),
            toursCount: $tournament->getToursCount(),
            questionsPerTour: $tournament->getQuestionsPerTour(),
            difficulty: $tournament->getDifficulty(),
            trueDl: $tournament->getTrueDl(),
            officials: OfficialMapping::toDTOGrouped($officials),
            sessions: $sessionDTOs,
            allTeams: $allTeamDTOs,
        );
    }

    /** @return array<int, list<TournamentSessionTeamPlayer>> sessionTeamId => players */
    private static function indexBySessionTeam(array $sessionTeamPlayers): array
    {
        $index = [];
        foreach ($sessionTeamPlayers as $player) {
            $index[$player->getTournamentSessionTeam()->getId()][] = $player;
        }

        return $index;
    }

    /** @return array<int, list<TournamentSessionTeam>> sessionId => teams */
    private static function indexBySession(array $sessionTeams): array
    {
        $index = [];
        foreach ($sessionTeams as $team) {
            $index[$team->getTournamentSession()->getId()][] = $team;
        }

        return $index;
    }
}
