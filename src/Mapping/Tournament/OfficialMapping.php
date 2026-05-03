<?php

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\OfficialDTO;
use App\Entity\TournamentOfficial;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentOfficial::class, destination: OfficialDTO::class)]
final class OfficialMapping implements MappingInterface
{
    /**
     * @return OfficialDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var TournamentOfficial $source */
        $player = $source->getPlayer();

        return new $destinationClass(
            playerId: $player->getId(),
            playerName: $player->getFullName(),
            role: $source->getRole()->value,
        );
    }
}
