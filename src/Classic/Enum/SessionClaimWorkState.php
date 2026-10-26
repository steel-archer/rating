<?php

declare(strict_types=1);

namespace App\Classic\Enum;

/**
 * Derived "what should the representative do now" state of a session claim,
 * combining the moderation status with the tournament registration and results
 * submission windows. Unlike SessionClaimStatus (persisted moderation outcome),
 * this is computed on the fly from deadlines and the play date.
 */
enum SessionClaimWorkState: string
{
    /** Claim is awaiting the organizer's moderation decision. */
    case AwaitingModeration = 'awaiting_moderation';

    /** Claim was rejected and registration is still open, so it can be resubmitted. */
    case Rejected = 'rejected';

    /** Claim is approved but the play date has not arrived yet. */
    case AwaitingGame = 'awaiting_game';

    /** Claim is approved, the game has been played and results can be entered now. */
    case ResultsNeeded = 'results_needed';

    /** Nothing more can be done: submission window closed, claim revoked or rejected past registration. */
    case Closed = 'closed';

    /**
     * Whether the claim still belongs to the "current" block, meaning there is
     * something to do or to wait for. Everything else is archived as "closed".
     */
    public function isCurrent(): bool
    {
        return $this !== self::Closed;
    }

    /**
     * Sort weight within the "current" block: actionable states float to the
     * top. Lower number sorts higher.
     */
    public function sortPriority(): int
    {
        return match ($this) {
            self::ResultsNeeded => 0,
            self::AwaitingModeration => 1,
            self::AwaitingGame => 2,
            self::Rejected => 3,
            self::Closed => 4,
        };
    }
}
