<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\TournamentOfficialDTO;
use App\Classic\Entity\TournamentOfficial;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
