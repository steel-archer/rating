<?php

declare(strict_types=1);

namespace App\Common\Mapping;

use App\Common\DTO\Response\Venue\RepresentativeDTO;
use App\Common\DTO\Response\VenueDTO;
use App\Common\Entity\Venue;
use App\Common\Entity\VenueRepresentative;

#[AsMapper(source: Venue::class, destination: VenueDTO::class)]
final class VenueMapping implements MappingInterface
{
    /**
     * @param Venue $source
     * @return VenueDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $representatives = array_map(
            static fn(VenueRepresentative $rep) => new RepresentativeDTO(
                playerId: $rep->getPlayer()->getId(),
                playerName: $rep->getPlayer()->getFullName(),
                hasUser: $rep->getPlayer()->hasUser(),
            ),
            $context['representatives'] ?? [],
        );

        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            townName: $source->getTown()->getName(),
            tournamentCount: $context['tournamentCount'] ?? 0,
            representatives: $representatives,
        );
    }
}
