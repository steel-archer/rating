<?php

declare(strict_types=1);

namespace App\Common\Contract;

use App\Common\Entity\Season;

interface PlayerTeamProviderInterface
{
    /**
     * Returns a map of playerId => team info for the given season.
     *
     * @param list<int> $playerIds
     * @return array<int, array{teamId: int, teamName: string}>
     */
    public function getTeamsByPlayerIds(array $playerIds, Season $season): array;
}
