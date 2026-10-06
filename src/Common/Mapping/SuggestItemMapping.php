<?php

declare(strict_types=1);

namespace App\Common\Mapping;

use App\Common\DTO\Response\SuggestItemDTO;

#[AsMapper(source: 'array', destination: SuggestItemDTO::class)]
final class SuggestItemMapping implements MappingInterface
{
    /**
     * @param array{id: int, name: string, townName?: string|null, countryName?: string|null} $source
     * @return SuggestItemDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source['id'],
            name: $source['name'],
            townName: $source['townName'] ?? null,
            countryName: $source['countryName'] ?? null,
        );
    }
}
