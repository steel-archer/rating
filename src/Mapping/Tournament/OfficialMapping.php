<?php

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\OfficialDTO;
use App\Entity\TournamentOfficial;

final class OfficialMapping
{
    public static function toDTO(TournamentOfficial $official): OfficialDTO
    {
        return new OfficialDTO(
            playerName: $official->getPlayer()->getFullName(),
            role: $official->getRole()->value,
        );
    }

    /** @return array<string, list<OfficialDTO>> */
    public static function toDTOGrouped(array $officials): array
    {
        $grouped = [];
        foreach ($officials as $o) {
            $role = $o->getRole()->value;
            $grouped[$role][] = self::toDTO($o);
        }

        return $grouped;
    }
}
