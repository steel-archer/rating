<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Rollover;

/**
 * Result of rolling over a single team's squad into the new season.
 */
final readonly class RolloverTeamResultDTO
{
    public function __construct(
        public int $teamId,
        public string $teamName,
        public int $transferredPlayerCount,
        public int $captainId,
        public string $captainName,
        public bool $captaincyReassigned,
    ) {
    }
}
