<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\Tournament\My\EditRequestDTO;
use App\Entity\Tournament;
use App\Entity\TournamentModerationStatus;
use App\Entity\TournamentOfficial;
use App\Entity\TournamentOfficialRole;
use App\Repository\SeasonRepository;
use App\Repository\TournamentModerationClaimRepository;
use App\Repository\TournamentOfficialRepository;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

class TournamentValidator
{
    public function __construct(
        private TournamentModerationClaimRepository $claimRepository,
        private TournamentOfficialRepository $officialRepository,
        private SeasonRepository $seasonRepository,
        private ClockInterface $clock,
    ) {
    }

    /** @return list<string> */
    public function validateEdit(EditRequestDTO $dto): array
    {
        $startedAt = $dto->startedAt ? new DateTimeImmutable($dto->startedAt) : null;
        $endedAt = $dto->endedAt ? new DateTimeImmutable($dto->endedAt) : null;

        return $this->validateDates($startedAt, $endedAt);
    }

    /** @return list<string> */
    public function validatePublish(Tournament $tournament): array
    {
        $errors = [];

        $claim = $this->claimRepository->findByTournament($tournament);
        if ($claim === null || $claim->getStatus() !== TournamentModerationStatus::Approved) {
            $errors[] = 'tournament.publish_error.not_approved';
        }

        if ($tournament->getStartedAt() === null) {
            $errors[] = 'tournament.publish_error.no_start_date';
        }
        if ($tournament->getEndedAt() === null) {
            $errors[] = 'tournament.publish_error.no_end_date';
        }

        $errors = [...$errors, ...$this->validateDates($tournament->getStartedAt(), $tournament->getEndedAt())];

        if ($tournament->getToursCount() === null) {
            $errors[] = 'tournament.publish_error.no_tours_count';
        }
        if ($tournament->getQuestionsPerTour() === null) {
            $errors[] = 'tournament.publish_error.no_questions_per_tour';
        }
        if ($tournament->getDifficulty() === null) {
            $errors[] = 'tournament.publish_error.no_difficulty';
        }

        $officials = $this->officialRepository->findByTournament($tournament);
        $roles = array_map(static fn(TournamentOfficial $official) => $official->getRole(), $officials);
        foreach (TournamentOfficialRole::cases() as $role) {
            if (!in_array($role, $roles, true)) {
                $errors[] = 'tournament.publish_error.missing_role.' . $role->value;
            }
        }

        return $errors;
    }

    /** @return list<string> */
    private function validateDates(?DateTimeImmutable $startedAt, ?DateTimeImmutable $endedAt): array
    {
        $errors = [];
        $now = DateTimeImmutable::createFromInterface($this->clock->now());

        if ($startedAt !== null && $startedAt <= $now) {
            $errors[] = 'tournament.error.start_in_past';
        }
        if ($endedAt !== null && $endedAt <= $now) {
            $errors[] = 'tournament.error.end_in_past';
        }
        if ($startedAt !== null && $endedAt !== null && $endedAt <= $startedAt) {
            $errors[] = 'tournament.error.end_before_start';
        }
        if ($startedAt !== null && $endedAt !== null) {
            $startSeason = $this->seasonRepository->findByDate($startedAt);
            $endSeason = $this->seasonRepository->findByDate($endedAt);
            if ($startSeason !== $endSeason) {
                $errors[] = 'tournament.error.spans_multiple_seasons';
            }
        }

        return $errors;
    }
}
