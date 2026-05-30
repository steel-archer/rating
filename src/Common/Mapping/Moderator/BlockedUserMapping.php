<?php

declare(strict_types=1);

namespace App\Common\Mapping\Moderator;

use App\Common\DTO\Response\Moderator\BlockedUserDTO;
use App\Common\Entity\User;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: User::class, destination: BlockedUserDTO::class)]
final class BlockedUserMapping implements MappingInterface
{
    /**
     * @param User $source
     * @return BlockedUserDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();

        return new $destinationClass(
            id: $source->getId(),
            email: $source->getEmail(),
            playerId: $player?->getId(),
            playerName: $player !== null
                ? $player->getLastName() . ' ' . $player->getFirstName()
                : null,
            isBlocked: $source->isBlocked(),
            blockedReason: $source->getBlockedReason(),
        );
    }
}
