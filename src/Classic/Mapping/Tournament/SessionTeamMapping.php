<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Tournament;

use App\Classic\DTO\Response\Tournament\SessionTeamDTO;
use App\Classic\DTO\Response\Tournament\SessionTeamPlayerDTO;
use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Entity\TournamentSessionTeamPlayer;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\Mapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TournamentSessionTeam::class, destination: SessionTeamDTO::class)]
final class SessionTeamMapping implements MappingInterface
{
    /**
     * @param TournamentSessionTeam $source
     * @param array{
     *     mapper: Mapper,
     *     place: float|null,
     *     players: list<TournamentSessionTeamPlayer>,
     *     squadInfo: array{playerIds: list<int>, captainId: int|null},
     * } $context
     * @return SessionTeamDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $mapper = $context['mapper'];
        $team = $source->getTeam();
        $tournament = $source->getTournamentSession()->getTournament();
        $maxScore = $tournament->getMaxScore();

        $playerDTOs = array_map(
            static fn(TournamentSessionTeamPlayer $p) => $mapper->map($p, SessionTeamPlayerDTO::class, ['squadInfo' => $context['squadInfo']]),
            $context['players'] ?? [],
        );

        return new $destinationClass(
            sessionTeamId: $source->getId(),
            teamId: $team->getId(),
            teamName: $team->getName(),
            teamTownName: $team->getTown()->getName(),
            score: $source->isResultsSubmitted() ? $source->getScore() : null,
            maxScore: $source->isResultsSubmitted() ? $maxScore : null,
            place: $context['place'] ?? null,
            players: $playerDTOs,
            oneTimeName: $source->getOneTimeName(),
        );
    }
}
