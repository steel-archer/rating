<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\TournamentListDTO;
use App\Classic\Entity\Tournament;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
