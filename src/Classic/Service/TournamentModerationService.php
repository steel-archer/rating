<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentModerationClaim;
use App\Classic\Enum\TournamentModerationStatus;
use App\Classic\Enum\TournamentStatus;
use App\Classic\Repository\TournamentModerationClaimRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class TournamentModerationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TournamentModerationClaimRepository $claimRepository,
    ) {
    }

    public function submitForModeration(Tournament $tournament): void
    {
        if ($tournament->getStatus() === TournamentStatus::Published) {
            throw new LogicException('Cannot submit published tournament');
        }

        $claim = $this->claimRepository->findByTournament($tournament);

        if ($claim !== null && $claim->getStatus() === TournamentModerationStatus::Approved) {
            throw new LogicException('Tournament is already approved');
        }

        $this->resetModeration($tournament);
    }

    public function approve(Tournament $tournament): void
    {
        $claim = $this->claimRepository->findByTournament($tournament)
            ?? throw new LogicException('No moderation claim found');

        if ($claim->getStatus() !== TournamentModerationStatus::Pending) {
            throw new LogicException('Claim is not pending');
        }

        $claim->setStatus(TournamentModerationStatus::Approved);
        $claim->setResolvedAt(new DateTimeImmutable());

        $this->em->flush();
    }

    public function reject(Tournament $tournament, ?string $comment): void
    {
        $claim = $this->claimRepository->findByTournament($tournament)
            ?? throw new LogicException('No moderation claim found');

        if ($claim->getStatus() !== TournamentModerationStatus::Pending) {
            throw new LogicException('Claim is not pending');
        }

        $claim->setStatus(TournamentModerationStatus::Rejected);
        $claim->setComment($comment);
        $claim->setResolvedAt(new DateTimeImmutable());

        $this->em->flush();
    }

    public function resetModeration(Tournament $tournament): void
    {
        $claim = $this->claimRepository->findByTournament($tournament);

        if ($claim === null) {
            $claim = new TournamentModerationClaim();
            $claim->setTournament($tournament);
            $this->em->persist($claim);
        } else {
            $claim->setStatus(TournamentModerationStatus::Pending);
            $claim->setComment(null);
            $claim->setCreatedAt(new DateTimeImmutable());
            $claim->setResolvedAt(null);
        }

        $this->em->flush();
    }
}
