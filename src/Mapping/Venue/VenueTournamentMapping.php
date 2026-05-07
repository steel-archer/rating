<?php

declare(strict_types=1);

namespace App\Mapping\Venue;

use App\DTO\Response\Venue\VenueTournamentDTO;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;
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
        );
    }
}
