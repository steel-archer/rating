<?php

declare(strict_types=1);

namespace App\Common\Mapping\Venue;

use App\Common\DTO\Response\Venue\VenueListItemDTO;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: 'array', destination: VenueListItemDTO::class)]
final class VenueListItemMapping implements MappingInterface
{
    /**
     * @param array<string, mixed> $source
     * @return VenueListItemDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new VenueListItemDTO(
            id: $source['id'],
            name: $source['name'],
            townName: $source['townName'],
            countryName: $source['countryName'],
        );
    }
}
