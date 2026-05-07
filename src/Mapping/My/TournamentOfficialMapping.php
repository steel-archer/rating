<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\TournamentOfficialDTO;
use App\Entity\TournamentOfficial;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentOfficial::class, destination: TournamentOfficialDTO::class)]
final class TournamentOfficialMapping implements MappingInterface
{
    /**
     * @param TournamentOfficial $source
     * @return TournamentOfficialDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();

        return new $destinationClass(
            playerId: $player->getId(),
            playerName: $player->getFullName(),
            hasUser: $player->hasUser(),
            role: $source->getRole()->value,
        );
    }
}
