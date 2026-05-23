<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Tournament;

use App\Classic\DTO\Response\Tournament\TournamentListItemDTO;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;
use DateTimeImmutable;

#[AsMapper(source: 'array', destination: TournamentListItemDTO::class)]
final class TournamentListItemMapping implements MappingInterface
{
    /**
     * @param array<string, mixed> $source
     * @return TournamentListItemDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new TournamentListItemDTO(
            id: $source['id'],
            name: $source['name'],
            startedAt: $source['startedAt'] instanceof DateTimeImmutable ? $source['startedAt'] : null,
            endedAt: $source['endedAt'] instanceof DateTimeImmutable ? $source['endedAt'] : null,
            difficulty: $source['difficulty'] !== null ? (float) $source['difficulty'] : null,
            trueDl: $source['trueDl'] !== null ? (float) $source['trueDl'] : null,
            teamCount: (int) $source['teamCount'],
        );
    }
}
