<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\VenueRepresentativeDTO;
use App\Entity\VenueRepresentative;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: VenueRepresentative::class, destination: VenueRepresentativeDTO::class)]
final class VenueRepresentativeMapping implements MappingInterface
{
    /**
     * @param VenueRepresentative $source
     * @return VenueRepresentativeDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();

        return new $destinationClass(
            playerId: $player->getId(),
            playerName: $player->getFullName(),
            hasUser: $player->hasUser(),
        );
    }
}
