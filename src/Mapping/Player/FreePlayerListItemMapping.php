<?php

declare(strict_types=1);

namespace App\Mapping\Player;

use App\DTO\Response\Player\FreePlayerListItemDTO;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: 'array', destination: FreePlayerListItemDTO::class)]
final class FreePlayerListItemMapping implements MappingInterface
{
    /**
     * @param array<string, mixed> $source
     * @return FreePlayerListItemDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new FreePlayerListItemDTO(
            id: $source['id'],
            fullName: trim($source['fullName']),
            townName: $source['townName'],
        );
    }
}
