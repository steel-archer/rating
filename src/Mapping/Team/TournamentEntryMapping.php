<?php

declare(strict_types=1);

namespace App\Mapping\Team;

use App\DTO\Response\Team\TournamentEntryDTO;
use App\DTO\Response\Team\TournamentPlayerDTO;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\AsMapper;
use App\Mapping\Mapper;
use App\Mapping\MappingInterface;

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

        $players = array_map(
            static fn(TournamentSessionTeamPlayer $a) => $mapper->map($a, TournamentPlayerDTO::class, ['squadInfo' => $context['squadInfo']]),
            $context['players'] ?? [],
        );

        return new $destinationClass(
            tournamentId: $tournament->getId(),
            tournamentName: $tournament->getName(),
            playedAt: $session->getPlayedAt(),
            score: $source->getScore(),
            place: $context['place'] ?? null,
            players: $players,
        );
    }
}
