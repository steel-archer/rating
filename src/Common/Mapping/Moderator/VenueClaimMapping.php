<?php

declare(strict_types=1);

namespace App\Common\Mapping\Moderator;

use App\Common\DTO\Response\Moderator\VenueClaimDTO;
use App\Common\Entity\Venue;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
