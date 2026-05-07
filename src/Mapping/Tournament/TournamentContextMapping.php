<?php

declare(strict_types=1);

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: Tournament::class, destination: TournamentContextDTO::class)]
final class TournamentContextMapping implements MappingInterface
{
    /**
     * @param Tournament $source
     * @return TournamentContextDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
        );
    }
}
