<?php

declare(strict_types=1);

namespace App\Common\Mapping\Player;

use App\Common\DTO\Response\Player\PlayerListItemDTO;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: 'array', destination: PlayerListItemDTO::class)]
final class PlayerListItemMapping implements MappingInterface
{
    /**
     * @param array<string, mixed> $source
     * @return PlayerListItemDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new PlayerListItemDTO(
            id: $source['id'],
            fullName: trim($source['fullName']),
            townName: $source['townName'],
            teamId: $source['teamId'] ? (int) $source['teamId'] : null,
            teamName: $source['teamName'] ?: null,
            hasUser: (bool) $source['hasUser'],
        );
    }
}
