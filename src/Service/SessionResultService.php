<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\Tournament\SessionTeamDTO;
use App\Entity\TournamentSession;
use App\Enum\CacheTag;
use App\Helper\SessionTeamResultBuilder;
use App\Repository\TournamentSessionTeamAnswerRepository;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception as DbalException;
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
     */
    public function getSessionResults(TournamentSession $session): array
    {
        if ($this->hasUnsubmittedResults($session)) {
            return $this->buildSessionResults($session);
        }

        $sessionId = $session->getId();
        $tournamentId = $session->getTournament()->getId();

        return $this->cache->get("session_results_{$sessionId}", function (ItemInterface $item) use ($session, $tournamentId) {
            $item->tag([CacheTag::tournament($tournamentId)]);
            $item->expiresAfter(86400);

            return $this->buildSessionResults($session);
        });
    }

    /**
     * @return list<SessionTeamDTO>
     */
    private function buildSessionResults(TournamentSession $session): array
    {
        $sessionTeams = $this->sessionTeamRepository->findBy(
            ['tournamentSession' => $session],
            ['score' => 'DESC'],
        );

        return $this->resultBuilder->build($sessionTeams, $session->getTournament()->getSeason());
    }

    public function hasUnsubmittedResults(TournamentSession $session): bool
    {
        $unsubmittedIds = $this->getUnsubmittedTeamIds($session);
        if ($unsubmittedIds === []) {
            return false;
        }

        return (int) $this->answerRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $unsubmittedIds)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @return array<int, array{answers: list<int>, tourScores: list<int>}> sessionTeamId => data
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

        $rows = $this->answerRepository->createQueryBuilder('a')
            ->select('IDENTITY(a.tournamentSessionTeam) AS teamId', 'a.questionNumber', 'a.isCorrect')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->getQuery()
            ->getArrayResult();

        $answersByTeam = [];
        foreach ($rows as $row) {
            $answersByTeam[(int) $row['teamId']][] = $row;
        }

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
                $answersByQuestion[$qNum] = $isCorrect;
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
     * @return list<int>
     */
    private function getUnsubmittedTeamIds(TournamentSession $session): array
    {
        $sessionTeams = $this->sessionTeamRepository->findBy(['tournamentSession' => $session]);

        $ids = [];
        foreach ($sessionTeams as $sessionTeam) {
            if (!$sessionTeam->isResultsSubmitted()) {
                $ids[] = $sessionTeam->getId();
            }
        }

        return $ids;
    }
}
