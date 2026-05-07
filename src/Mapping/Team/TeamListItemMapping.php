<?php

declare(strict_types=1);

namespace App\Mapping\Team;

use App\DTO\Response\Team\TeamListItemDTO;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: 'array', destination: TeamListItemDTO::class)]
final class TeamListItemMapping implements MappingInterface
{
    /**
     * @param array<string, mixed> $source
     * @return TeamListItemDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new TeamListItemDTO(
            id: $source['id'],
            name: $source['name'],
            townName: $source['townName'],
            countryName: $source['countryName'],
        );
    }
}
