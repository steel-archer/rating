<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Rollover;

/**
 * Aggregated result of a season rollover run.
 */
final readonly class RolloverResultDTO
{
    /**
     * @param list<RolloverTeamResultDTO> $teams
     */
    public function __construct(
        public string $sourceSeasonName,
        public string $targetSeasonName,
        public bool $dryRun,
        public int $skippedTeamCount,
        public array $teams,
    ) {
    }

    public function transferredTeamCount(): int
    {
        return count($this->teams);
    }

    public function transferredPlayerCount(): int
    {
        return array_sum(
            array_map(
                static fn(RolloverTeamResultDTO $team): int => $team->transferredPlayerCount,
                $this->teams,
            ),
        );
    }

    public function reassignedCaptaincyCount(): int
    {
        return count(
            array_filter(
                $this->teams,
                static fn(RolloverTeamResultDTO $team): bool => $team->captaincyReassigned,
            ),
        );
    }
}
