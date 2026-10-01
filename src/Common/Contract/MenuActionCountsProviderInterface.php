<?php

declare(strict_types=1);

namespace App\Common\Contract;

use App\Common\Entity\Player;

/**
 * Supplies Classic-module action counts for the navigation menu badges.
 * Lets the Common aggregator stay unaware of Classic entities.
 */
interface MenuActionCountsProviderInterface
{
    /**
     * Pending tournament publication moderation claims (shared across all moderators).
     */
    public function countPendingTournamentModeration(): int;

    /**
     * Pending captain claims (shared across all moderators).
     */
    public function countPendingCaptainClaims(): int;

    /**
     * Pending session claims across tournaments the player organizes (personal).
     */
    public function countPendingOrganizerClaims(Player $player): int;

    /**
     * Unresolved disputes across tournaments where the player is game jury (personal).
     */
    public function countSubmittedDisputesForJury(Player $player): int;

    /**
     * Pending appeals across tournaments where the player is appeal jury (personal).
     */
    public function countPendingAppealsForJury(Player $player): int;

    /**
     * Ids of tournaments the player is involved in as organizer or jury.
     * Used to tag the player's personal counts cache for targeted invalidation.
     *
     * @return list<int>
     */
    public function getInvolvedTournamentIds(Player $player): array;
}
