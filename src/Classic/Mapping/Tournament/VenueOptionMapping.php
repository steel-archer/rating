<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Tournament;

use App\Classic\DTO\Response\Tournament\VenueOptionDTO;
use App\Common\Entity\Venue;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
            isOnline: $source->isOnline(),
        );
    }
}
