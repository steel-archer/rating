<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

use App\Classic\Enum\SessionClaimWorkState;
use App\Classic\Enum\SubmissionDeadlineUrgency;
use DateTimeImmutable;

final readonly class SessionClaimListDTO
{
    public function __construct(
        public int $sessionId,
        public int $tournamentId,
        public string $tournamentName,
        public int $venueId,
        public string $venueName,
        public string $townName,
        public ?DateTimeImmutable $playedAt,
        public ?int $estimatedTeams,
        public int $actualTeams,
        public string $status,
        public SessionClaimWorkState $workState,
        public ?string $comment,
        public ?DateTimeImmutable $submissionDeadline = null,
        public ?SubmissionDeadlineUrgency $deadlineUrgency = null,
        public ?string $announcementUrl = null,
        public bool $isOnline = false,
    ) {
    }
}
