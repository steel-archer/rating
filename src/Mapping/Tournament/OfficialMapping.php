<?php

declare(strict_types=1);

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\OfficialDTO;
use App\Entity\TournamentOfficial;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentOfficial::class, destination: OfficialDTO::class)]
final class OfficialMapping implements MappingInterface
{
    /**
     * @param TournamentOfficial $source
     * @return OfficialDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();

        return new $destinationClass(
            playerId: $player->getId(),
            playerName: $player->getFullName(),
            role: $source->getRole()->value,
            hasUser: $player->hasUser(),
        );
    }
}
