<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\VenueEditDTO;
use App\Entity\Venue;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: Venue::class, destination: VenueEditDTO::class)]
final class VenueEditMapping implements MappingInterface
{
    /**
     * @param Venue $source
     * @return VenueEditDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            townName: $source->getTown()->getName(),
            createdByPlayerId: $source->getCreatedBy()->getPlayer()->getId(),
        );
    }
}
