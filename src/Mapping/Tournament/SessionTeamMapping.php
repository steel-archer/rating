<?php

declare(strict_types=1);

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionTeamDTO;
use App\DTO\Response\Tournament\SessionTeamPlayerDTO;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\AsMapper;
use App\Mapping\Mapper;
use App\Mapping\MappingInterface;

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

        $playerDTOs = array_map(
            static fn(TournamentSessionTeamPlayer $p) => $mapper->map($p, SessionTeamPlayerDTO::class, ['squadInfo' => $context['squadInfo']]),
            $context['players'] ?? [],
        );

        return new $destinationClass(
            teamId: $team->getId(),
            teamName: $team->getName(),
            teamTownName: $team->getTown()->getName(),
            score: $source->getScore(),
            place: $context['place'] ?? null,
            players: $playerDTOs,
        );
    }
}
