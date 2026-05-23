<?php

declare(strict_types=1);

namespace App\Common\Contract;

use App\Common\Entity\Venue;

interface VenueTournamentProviderInterface
{
    public function countByVenue(Venue $venue): int;

    /**
     * @return list<object>
     */
    public function findByVenuePaginated(Venue $venue, int $page): array;

    public function getLastPageNumberByVenue(Venue $venue): int;
}
