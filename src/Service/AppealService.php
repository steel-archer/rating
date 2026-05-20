<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\My\AppealTournamentDTO;
use App\Entity\Appeal;
use App\Entity\Player;
use App\Entity\Tournament;
use App\Entity\TournamentSessionTeamAnswer;
use App\Enum\AppealStatus;
use App\Enum\AppealType;
use App\Enum\DisputeStatus;
use App\Repository\AppealRepository;
use App\Repository\TournamentSessionTeamRepository;
use App\Service\Cache\CacheInvalidator;
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
        if ($text === '') {
            throw new LogicException('appeal.error.empty_text');
        }

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

        if ($type === AppealType::Accept) {
            if ($answer->getDisputeStatus() !== DisputeStatus::Rejected) {
                throw new LogicException('appeal.error.no_rejected_dispute');
            }
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
        $tournament = $answer->getTournamentSessionTeam()
            ->getTournamentSession()
            ->getTournament();

        if ($appeal->getType() === AppealType::Accept) {
            $answer->setIsCorrect(true);
            $answer->getTournamentSessionTeam()->recalculateScore();
        } else {
            $this->removeQuestion($tournament, $answer->getQuestionNumber());
        }

        $this->em->flush();
        $this->cacheInvalidator->invalidateTournamentWithParticipants($tournament);
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

    private function removeQuestion(Tournament $tournament, int $questionNumber): void
    {
        $sessionTeams = $this->sessionTeamRepository->findByTournament($tournament);

        foreach ($sessionTeams as $sessionTeam) {
            foreach ($sessionTeam->getAnswers() as $answer) {
                if ($answer->getQuestionNumber() === $questionNumber) {
                    $answer->setIsQuestionRemoved(true);
                    $answer->setIsCorrect(false);
                }
            }
            $sessionTeam->recalculateScore();
        }
    }
}
