<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\SessionClaim;
use App\Classic\Enum\SessionClaimStatus;
use App\Classic\Enum\SessionClaimWorkState;
use App\Classic\Enum\SubmissionDeadlineUrgency;
use DateTimeImmutable;

/**
 * Derives the SessionClaimWorkState of a claim from its moderation status and
 * the tournament registration/submission windows. Kept out of the mapper
 * because it needs the current date and tournament deadlines, which must not be
 * read from the entity inside a mapper.
 */
final class SessionClaimWorkStateResolver
{
    /**
     * Resolve the work state of a single claim. The play date is compared by day
     * (not time), mirroring the edit page so the list and the claim page agree.
     *
     * @param SessionClaim $claim
     * @return SessionClaimWorkState
     */
    public function resolve(SessionClaim $claim): SessionClaimWorkState
    {
        $session = $claim->getSession();
        $tournament = $session->getTournament();
        $status = $claim->getStatus();

        if ($status === SessionClaimStatus::Pending) {
            return SessionClaimWorkState::AwaitingModeration;
        }

        if ($status === SessionClaimStatus::Rejected) {
            return $tournament->isRegistrationOpen()
                ? SessionClaimWorkState::Rejected
                : SessionClaimWorkState::Closed;
        }

        if ($status === SessionClaimStatus::Approved) {
            if (!$tournament->isSubmissionOpen()) {
                return SessionClaimWorkState::Closed;
            }

            $playedAt = $session->getPlayedAt();
            $today = new DateTimeImmutable('today');

            if ($playedAt !== null && $playedAt <= $today) {
                return SessionClaimWorkState::ResultsNeeded;
            }

            return SessionClaimWorkState::AwaitingGame;
        }

        // Revoked and any other terminal status: nothing left to do.
        return SessionClaimWorkState::Closed;
    }

    /**
     * How close the results submission deadline is. Returns null when there is
     * no dated deadline (centralized tournaments keep submission open while the
     * tournament is published) or the window is already closed. The deadline is
     * stored at 00:00, meaning results are accepted until midnight before it, so
     * one whole day left is the last day to submit.
     *
     * @param Tournament $tournament
     * @return SubmissionDeadlineUrgency|null
     */
    public function resolveDeadlineUrgency(Tournament $tournament): ?SubmissionDeadlineUrgency
    {
        $deadline = $tournament->getSubmissionDeadline();
        if ($deadline === null || !$tournament->isSubmissionOpen()) {
            return null;
        }

        $today = new DateTimeImmutable('today');
        $daysLeft = (int) $today->diff($deadline)->format('%r%a');

        if ($daysLeft <= 1) {
            return SubmissionDeadlineUrgency::LastDay;
        }

        if ($daysLeft <= 3) {
            return SubmissionDeadlineUrgency::Soon;
        }

        return SubmissionDeadlineUrgency::Calm;
    }
}
