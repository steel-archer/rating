<?php

declare(strict_types=1);

namespace App\Mapping\Venue;

use App\DTO\Response\Venue\VenueListItemDTO;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

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
