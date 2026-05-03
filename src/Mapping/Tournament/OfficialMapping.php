<?php

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\OfficialDTO;
use App\Entity\TournamentOfficial;
use App\Mapping\MappingInterface;

final class OfficialMapping implements MappingInterface
{
    /** @return OfficialDTO */
    public static function mapTo(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var TournamentOfficial $source */
        $player = $source->getPlayer();

        return new $destinationClass(
            playerId: $player->getId(),
            playerName: $player->getFullName(),
            role: $source->getRole()->value,
        );
    }

    /** @return array<string, list<OfficialDTO>> */
    public static function mapGrouped(array $officials): array
    {
        $grouped = [];
        foreach ($officials as $official) {
            $role = $official->getRole()->value;
            $grouped[$role][] = self::mapTo($official, OfficialDTO::class);
        }

        return $grouped;
    }
}
