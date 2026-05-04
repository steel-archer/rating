<?php

namespace App\Mapping;

use App\DTO\Response\VenueDTO;
use App\Entity\Venue;

#[AsMapper(source: Venue::class, destination: VenueDTO::class)]
final class VenueMapping implements MappingInterface
{
    /**
     * @param Venue $source
     * @return VenueDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            townName: $source->getTown()->getName(),
            tournamentCount: $context['tournamentCount'] ?? 0,
            representatives: $context['representatives'] ?? [],
        );
    }
}
