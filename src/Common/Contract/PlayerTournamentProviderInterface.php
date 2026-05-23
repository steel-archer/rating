<?php

declare(strict_types=1);

namespace App\Common\Contract;

use App\Common\Entity\Player;

interface PlayerTournamentProviderInterface
{
    /**
     * @return list<object>
     */
    public function getTournaments(Player $player, int $page): array;

    public function getLastPageNumber(Player $player): int;
}
