<?php

declare(strict_types=1);

namespace App\Mapping\Moderator;

use App\DTO\Response\Moderator\VenueClaimDTO;
use App\Entity\Venue;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: Venue::class, destination: VenueClaimDTO::class)]
final class VenueClaimMapping implements MappingInterface
{
    /**
     * @param Venue $source
     * @return VenueClaimDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getCreatedBy();

        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            townName: $source->getTown()->getName(),
            createdByPlayerId: $player?->getId(),
            createdByPlayerName: $player?->getFullName(),
            createdAt: $source->getCreatedAt(),
        );
    }
}
