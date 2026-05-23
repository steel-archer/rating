<?php

declare(strict_types=1);

namespace App\Common\Mapping\My;

use App\Common\DTO\Response\My\VenueListDTO;
use App\Common\Entity\Venue;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: Venue::class, destination: VenueListDTO::class)]
final class VenueListMapping implements MappingInterface
{
    /**
     * @param Venue $source
     * @return VenueListDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            townName: $source->getTown()->getName(),
            isApproved: $source->isApproved(),
        );
    }
}
