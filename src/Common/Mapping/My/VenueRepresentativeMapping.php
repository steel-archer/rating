<?php

declare(strict_types=1);

namespace App\Common\Mapping\My;

use App\Common\DTO\Response\My\VenueRepresentativeDTO;
use App\Common\Entity\VenueRepresentative;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
