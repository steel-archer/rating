<?php

declare(strict_types=1);

namespace App\Mapping\Moderator;

use App\DTO\Response\Moderator\PlayerClaimDTO;
use App\Entity\PlayerClaim;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: PlayerClaim::class, destination: PlayerClaimDTO::class)]
final class PlayerClaimMapping implements MappingInterface
{
    /**
     * @param PlayerClaim $source
     * @return PlayerClaimDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();
        $town = $source->getTown();
        $townName = $town?->getName() ?? $source->getTownName() ?? $player?->getTown()?->getName();

        return new $destinationClass(
            id: $source->getId(),
            userEmail: $source->getUser()->getEmail(),
            isNew: $source->isNew(),
            playerId: $player?->getId(),
            playerName: $player?->getFullName(),
            playerHasUser: $player?->hasUser() ?? false,
            lastName: $source->getLastName(),
            firstName: $source->getFirstName(),
            patronymic: $source->getPatronymic(),
            townName: $townName,
            townId: $town?->getId(),
            townIsNew: $town === null && $townName !== null,
        );
    }
}
