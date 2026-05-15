<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\DisputeTournamentDTO;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: 'array', destination: DisputeTournamentDTO::class)]
final class DisputeTournamentMapping implements MappingInterface
{
    /**
     * @param array{tournamentId: int, tournamentName: string, total: int, resolved: int} $source
     * @return DisputeTournamentDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            tournamentId: $source['tournamentId'],
            tournamentName: $source['tournamentName'],
            total: $source['total'],
            resolved: $source['resolved'],
        );
    }
}
