<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Team;

use App\Classic\DTO\Response\Team\TeamListItemDTO;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
