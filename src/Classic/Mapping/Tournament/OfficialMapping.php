<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Tournament;

use App\Classic\DTO\Response\Tournament\OfficialDTO;
use App\Classic\Entity\TournamentOfficial;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
