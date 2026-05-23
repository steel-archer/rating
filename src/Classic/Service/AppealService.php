<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\DTO\Response\My\AppealTournamentDTO;
use App\Classic\Entity\Appeal;
use App\Common\Entity\Player;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentSessionTeamAnswer;
use App\Classic\Enum\AppealStatus;
use App\Classic\Enum\AppealType;
use App\Classic\Enum\DisputeStatus;
use App\Classic\Repository\AppealRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
use App\Classic\Service\Cache\CacheInvalidator;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Cache\InvalidArgumentException;

class AppealService
{
    public function __construct(
        private AppealRepository $appealRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private EntityManagerInterface $em,
        private CacheInvalidator $cacheInvalidator,
    ) {
    }

    /**
     * @return list<AppealTournamentDTO>
     */
    public function getJuryTournamentList(Player $player): array
    {
        $rows = $this->appealRepository->findJuryTournamentStats($player);

        return array_map(
            static fn(array $row) => new AppealTournamentDTO(
                tournamentId: $row['tournamentId'],
                tournamentName: $row['tournamentName'],
                total: $row['total'],
                resolved: $row['resolved'],
            ),
            $rows,
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function create(
        TournamentSessionTeamAnswer $answer,
        AppealType $type,
        string $text,
    ): void {
        $tournament = $answer->getTournamentSessionTeam()
            ->getTournamentSession()
            ->getTournament();

        if (!$tournament->isAppealOpen()) {
            throw new LogicException('appeal.error.deadline_passed');
        }

        $existingAppeal = $this->appealRepository->findByAnswer($answer);
        if ($existingAppeal !== null) {
            throw new LogicException('appeal.error.already_exists');
        }

        if ($type === AppealType::Accept && $answer->getDisputeStatus() !== DisputeStatus::Rejected) {
            throw new LogicException('appeal.error.no_rejected_dispute');
        }

        $appeal = new Appeal();
        $appeal->setTournamentSessionTeamAnswer($answer);
        $appeal->setType($type);
        $appeal->setText($text);

        $this->em->persist($appeal);
        $this->em->flush();

        $this->cacheInvalidator->invalidateTournament($tournament);
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function accept(Appeal $appeal, ?string $verdict): void
    {
        if ($appeal->getStatus() !== AppealStatus::Pending) {
            throw new LogicException('appeal.error.already_resolved');
        }

        $appeal->setStatus(AppealStatus::Accepted);
        $appeal->setVerdict($verdict);

        $answer = $appeal->getTournamentSessionTeamAnswer();
        $tournamentId = $answer->getTournamentSessionTeam()
            ->getTournamentSession()
            ->getTournament()
            ->getId();

        if ($appeal->getType() === AppealType::Accept) {
            $answer->setIsCorrect(true);
            $this->em->flush();
            $sessionTeamId = $answer->getTournamentSessionTeam()->getId();
            $this->recalculateScore($sessionTeamId);
        } else {
            $this->em->flush();
            $this->sessionTeamRepository->markQuestionRemoved($tournamentId, $answer->getQuestionNumber());
            $this->recalculateAllScores($tournamentId);
        }

        $freshTournament = $this->em->getRepository(Tournament::class)->find($tournamentId);
        $this->cacheInvalidator->invalidateTournamentWithParticipants($freshTournament);
    }

    /**
     * @throws LogicException
     */
    public function reject(Appeal $appeal, ?string $verdict): void
    {
        if ($appeal->getStatus() !== AppealStatus::Pending) {
            throw new LogicException('appeal.error.already_resolved');
        }

        $appeal->setStatus(AppealStatus::Rejected);
        $appeal->setVerdict($verdict);

        $this->em->flush();
    }

    private function recalculateScore(int $sessionTeamId): void
    {
        $this->em->clear();

        $sessionTeam = $this->sessionTeamRepository->findWithAnswers($sessionTeamId);
        $sessionTeam->recalculateScore();

        $this->em->flush();
    }

    private function recalculateAllScores(int $tournamentId): void
    {
        $this->em->clear();

        $tournament = $this->em->getRepository(Tournament::class)->find($tournamentId);
        $sessionTeams = $this->sessionTeamRepository->findByTournament($tournament);
        foreach ($sessionTeams as $sessionTeam) {
            $sessionTeam->recalculateScore();
        }

        $this->em->flush();
    }
}
