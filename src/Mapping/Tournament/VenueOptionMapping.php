<?php

declare(strict_types=1);

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\VenueOptionDTO;
use App\Entity\Venue;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: Venue::class, destination: VenueOptionDTO::class)]
final class VenueOptionMapping implements MappingInterface
{
    /**
     * @param Venue $source
     * @return VenueOptionDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            townName: $source->getTown()->getName(),
        );
    }
}
