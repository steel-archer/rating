<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Venue;

use App\Classic\DTO\Response\Venue\VenueTournamentDTO;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;
use DateTimeImmutable;

#[AsMapper(source: 'array', destination: VenueTournamentDTO::class)]
final class VenueTournamentMapping implements MappingInterface
{
    /**
     * @param array<string, mixed> $source
     * @return VenueTournamentDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new VenueTournamentDTO(
            tournamentId: $source['tournamentId'],
            tournamentName: $source['tournamentName'],
            playedAt: $source['playedAt'] instanceof DateTimeImmutable ? $source['playedAt'] : null,
            teamsCount: (int) $source['teamsCount'],
        );
    }
}
