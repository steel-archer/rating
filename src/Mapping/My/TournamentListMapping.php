<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\TournamentListDTO;
use App\Entity\Tournament;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: Tournament::class, destination: TournamentListDTO::class)]
final class TournamentListMapping implements MappingInterface
{
    /**
     * @param Tournament $source
     * @return TournamentListDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            status: $source->getStatus()->value,
            startedAt: $source->getStartedAt(),
            endedAt: $source->getEndedAt(),
        );
    }
}
