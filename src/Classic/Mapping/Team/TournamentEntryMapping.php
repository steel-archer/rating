<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Team;

use App\Classic\DTO\Response\Team\TournamentEntryDTO;
use App\Classic\DTO\Response\Team\TournamentPlayerDTO;
use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Entity\TournamentSessionTeamPlayer;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\Mapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TournamentSessionTeam::class, destination: TournamentEntryDTO::class)]
final class TournamentEntryMapping implements MappingInterface
{
    /**
     * @param TournamentSessionTeam $source
     * @param array{
     *     mapper: Mapper,
     *     place: float|null,
     *     players: list<TournamentSessionTeamPlayer>,
     *     squadInfo: array{playerIds: list<int>, captainId: int|null},
     * } $context
     * @return TournamentEntryDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $mapper = $context['mapper'];
        $session = $source->getTournamentSession();
        $tournament = $session->getTournament();
        $resultsHidden = $tournament->areResultsHidden();
        $maxScore = $tournament->getMaxScore();

        $players = array_map(
            static fn(TournamentSessionTeamPlayer $a) => $mapper->map($a, TournamentPlayerDTO::class, ['squadInfo' => $context['squadInfo']]),
            $context['players'] ?? [],
        );

        return new $destinationClass(
            tournamentId: $tournament->getId(),
            tournamentName: $tournament->getName(),
            tournamentFormat: $tournament->getFormat()->value,
            playedAt: $session->getPlayedAt(),
            score: $resultsHidden ? null : $source->getScore(),
            maxScore: $resultsHidden ? null : $maxScore,
            place: $resultsHidden ? null : ($context['place'] ?? null),
            players: $players,
            oneTimeName: $source->getOneTimeName(),
            isOnline: $session->isOnline(),
        );
    }
}
