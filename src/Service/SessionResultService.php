<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\Tournament\SessionTeamDTO;
use App\Entity\TournamentSession;
use App\Enum\CacheTag;
use App\Enum\DisputeStatus;
use App\Helper\SessionTeamResultBuilder;
use App\Repository\TournamentSessionTeamAnswerRepository;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class SessionResultService
{
    public function __construct(
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionTeamAnswerRepository $answerRepository,
        private SessionTeamResultBuilder $resultBuilder,
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @return list<SessionTeamDTO>
     *
     * @throws DbalException
     * @throws InvalidArgumentException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getSessionResults(TournamentSession $session): array
    {
        if ($this->hasUnsubmittedResults($session)) {
            return $this->buildSessionResults($session);
        }

        $sessionId = $session->getId();
        $tournamentId = $session->getTournament()->getId();

        return $this->cache->get("session_results_$sessionId", function (ItemInterface $item) use ($session, $tournamentId) {
            $item->tag([CacheTag::tournament($tournamentId)]);
            $item->expiresAfter(86400);

            return $this->buildSessionResults($session);
        });
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function hasUnsubmittedResults(TournamentSession $session): bool
    {
        $unsubmittedIds = $this->sessionTeamRepository->findUnsubmittedIdsBySession($session);

        return $this->answerRepository->hasAnswersForTeams($unsubmittedIds);
    }

    /**
     * @return array<int, array{answers: list<int|string>, tourScores: list<int>}> sessionTeamId => data
     */
    public function getAnswerBreakdown(TournamentSession $session): array
    {
        $questionsPerTour = $session->getTournament()->getQuestionsPerTour() ?? 0;
        if ($questionsPerTour === 0) {
            return [];
        }

        $toursCount = $session->getTournament()->getToursCount() ?? 0;
        $totalQuestions = $toursCount * $questionsPerTour;
        $sessionTeams = $this->sessionTeamRepository->findBy(['tournamentSession' => $session]);

        $sessionTeamIds = array_map(static fn($st) => $st->getId(), $sessionTeams);
        if ($sessionTeamIds === []) {
            return [];
        }

        $rows = $this->answerRepository->findBySessionTeamIds($sessionTeamIds);

        $answersByTeam = [];
        foreach ($rows as $row) {
            $answersByTeam[(int) $row['teamId']][] = $row;
        }

        $unresolvedStatuses = [DisputeStatus::Created, DisputeStatus::Submitted];

        $result = [];
        foreach ($sessionTeams as $sessionTeam) {
            $teamAnswers = $answersByTeam[$sessionTeam->getId()] ?? [];
            if ($teamAnswers === []) {
                continue;
            }

            $answersByQuestion = array_fill(1, $totalQuestions, 0);
            $tourTotals = array_fill(0, $toursCount, 0);

            foreach ($teamAnswers as $row) {
                $qNum = (int) $row['questionNumber'];
                $isCorrect = $row['isCorrect'] ? 1 : 0;

                $rawStatus = $row['disputeStatus'];
                /** @var DisputeStatus|string|null $rawStatus */
                $disputeStatus = $rawStatus instanceof DisputeStatus
                    ? $rawStatus
                    : ($rawStatus !== null ? DisputeStatus::from($rawStatus) : null);

                if ($disputeStatus !== null && in_array($disputeStatus, $unresolvedStatuses, true)) {
                    $answersByQuestion[$qNum] = '?';
                } else {
                    $answersByQuestion[$qNum] = $isCorrect;
                }

                $tourIndex = (int) ceil($qNum / $questionsPerTour) - 1;
                $tourTotals[$tourIndex] += $isCorrect;
            }

            $result[$sessionTeam->getId()] = [
                'answers' => array_values($answersByQuestion),
                'tourScores' => $tourTotals,
            ];
        }

        return $result;
    }

    /**
     * Returns all teams for a session including those without results.
     *
     * @return list<SessionTeamDTO>
     *
     * @throws DbalException
     */
    public function getAllSessionTeams(TournamentSession $session): array
    {
        return $this->buildSessionResults($session);
    }

    /**
     * @return list<SessionTeamDTO>
     * @throws DbalException
     */
    private function buildSessionResults(TournamentSession $session): array
    {
        $sessionTeams = $this->sessionTeamRepository->findBy(
            ['tournamentSession' => $session],
            ['score' => 'DESC'],
        );

        return $this->resultBuilder->build($sessionTeams, $session->getTournament()->getSeason());
    }
}
