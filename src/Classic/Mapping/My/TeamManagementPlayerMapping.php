<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\TeamManagementPlayerDTO;
use App\Classic\Entity\TeamPlayer;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TeamPlayer::class, destination: TeamManagementPlayerDTO::class)]
final class TeamManagementPlayerMapping implements MappingInterface
{
    /**
     * @param TeamPlayer $source
     * @return TeamManagementPlayerDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();

        return new $destinationClass(
            playerId: $player->getId(),
            playerName: $player->getFullName(),
            isCaptain: $source->isCaptain(),
            hasUser: $player->hasUser(),
        );
    }
}
