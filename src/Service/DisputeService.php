<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeamAnswer;
use App\Enum\DisputeStatus;
use App\Repository\TournamentSessionTeamAnswerRepository;
use App\Repository\TournamentSessionTeamRepository;
use App\Service\Cache\CacheInvalidator;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Cache\InvalidArgumentException;

class DisputeService
{
    public function __construct(
        private TournamentSessionTeamAnswerRepository $answerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private EntityManagerInterface $em,
        private CacheInvalidator $cacheInvalidator,
    ) {
    }

    /**
     * @throws LogicException
     */
    public function createDispute(
        TournamentSession $session,
        int $sessionTeamId,
        int $questionNumber,
        string $text,
    ): void {
        $sessionTeam = $this->sessionTeamRepository->find($sessionTeamId);
        if ($sessionTeam === null || $sessionTeam->getTournamentSession()->getId() !== $session->getId()) {
            throw new LogicException('common.not_found');
        }

        $existingAnswer = $this->answerRepository->findOneBy([
            'tournamentSessionTeam' => $sessionTeam,
            'questionNumber' => $questionNumber,
        ]);

        if ($existingAnswer === null) {
            throw new LogicException('common.not_found');
        }

        if ($existingAnswer->getDisputeStatus() !== null) {
            throw new LogicException('dispute.error.already_exists');
        }

        if ($existingAnswer->isCorrect()) {
            throw new LogicException('dispute.error.already_correct');
        }

        $existingAnswer->setDisputeText($text);
        $existingAnswer->setDisputeStatus(DisputeStatus::Created);

        $this->em->flush();
    }

    /**
     * @param list<int> $answerIds
     *
     * @throws LogicException
     */
    public function submitDisputes(TournamentSession $session, array $answerIds): void
    {
        $disputes = $this->answerRepository->findDisputesBySessionAndIds($session, $answerIds);

        if ($disputes === []) {
            throw new LogicException('dispute.error.nothing_to_submit');
        }

        foreach ($disputes as $dispute) {
            if ($dispute->getDisputeStatus() !== DisputeStatus::Created) {
                throw new LogicException('dispute.error.invalid_status');
            }
            $dispute->setDisputeStatus(DisputeStatus::Submitted);
        }

        $this->em->flush();
    }

    /**
     * @throws LogicException
     */
    public function deleteDispute(TournamentSessionTeamAnswer $answer): void
    {
        if ($answer->getDisputeStatus() !== DisputeStatus::Created) {
            throw new LogicException('dispute.error.cannot_delete');
        }

        $answer->setDisputeText(null);
        $answer->setDisputeStatus(null);
        $answer->setDisputeComment(null);

        $this->em->flush();
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function acceptDispute(TournamentSessionTeamAnswer $answer, ?string $comment): void
    {
        if ($answer->getDisputeStatus() !== DisputeStatus::Submitted) {
            throw new LogicException('dispute.error.already_resolved');
        }

        $answer->setDisputeStatus(DisputeStatus::Accepted);
        $answer->setDisputeComment($comment);
        $answer->setIsCorrect(true);

        $sessionTeam = $answer->getTournamentSessionTeam();
        $sessionTeam->recalculateScore();

        $this->em->flush();
        $this->cacheInvalidator->invalidateTournamentWithParticipants(
            $sessionTeam->getTournamentSession()->getTournament(),
        );
    }

    /**
     * @throws LogicException
     */
    public function rejectDispute(TournamentSessionTeamAnswer $answer, ?string $comment): void
    {
        if ($answer->getDisputeStatus() !== DisputeStatus::Submitted) {
            throw new LogicException('dispute.error.already_resolved');
        }

        $answer->setDisputeStatus(DisputeStatus::Rejected);
        $answer->setDisputeComment($comment);

        $this->em->flush();
    }
}
