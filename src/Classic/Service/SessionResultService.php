<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\DTO\Response\Tournament\SessionTeamDTO;
use App\Classic\DTO\Response\Tournament\TeamBreakdownDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentSession;
use App\Classic\Entity\TournamentSessionTeam;
use App\Common\Enum\CacheTag;
use App\Classic\Enum\DisputeStatus;
use App\Classic\Helper\SessionTeamResultBuilder;
use App\Classic\Repository\TournamentSessionTeamAnswerRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
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
     * @return array<int, TeamBreakdownDTO> sessionTeamId => breakdown
     */
    public function getAnswerBreakdown(TournamentSession $session): array
    {
        $tournament = $session->getTournament();
        $sessionTeams = $this->sessionTeamRepository->findBy(['tournamentSession' => $session]);

        return $this->buildBreakdown($sessionTeams, $tournament);
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
     * @return array<int, TeamBreakdownDTO> sessionTeamId => breakdown
     *
     * @throws InvalidArgumentException
     */
    public function getTournamentAnswerBreakdown(Tournament $tournament): array
    {
        $tournamentId = $tournament->getId();
        $cacheKey = "tournament_breakdown_$tournamentId";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($tournament, $tournamentId) {
            $item->tag([CacheTag::tournament($tournamentId)]);
            $item->expiresAfter(86400);

            $sessionTeams = $this->sessionTeamRepository->findByTournamentSubmitted($tournament);

            return $this->buildBreakdown($sessionTeams, $tournament);
        });
    }

    /**
     * @param list<TournamentSessionTeam> $sessionTeams
     * @return array<int, TeamBreakdownDTO> sessionTeamId => breakdown
     */
    private function buildBreakdown(array $sessionTeams, Tournament $tournament): array
    {
        $questionsPerTourMap = $tournament->getQuestionsPerTourMap();
        if ($questionsPerTourMap === null || $questionsPerTourMap === []) {
            return [];
        }

        $toursCount = $tournament->getToursCount() ?? 0;
        $totalQuestions = array_sum($questionsPerTourMap);

        // Build offset array for quick tour index lookup
        $tourOffsets = [0];
        for ($i = 1; $i < $toursCount; $i++) {
            $tourOffsets[$i] = $tourOffsets[$i - 1] + $questionsPerTourMap[$i - 1];
        }

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
                $isRemoved = (bool) $row['isQuestionRemoved'];
                $disputeStatus = $row['disputeStatus'];

                if ($isRemoved) {
                    $answersByQuestion[$qNum] = 'X';
                } elseif ($disputeStatus !== null && in_array($disputeStatus, $unresolvedStatuses, true)) {
                    $answersByQuestion[$qNum] = '?';
                } else {
                    $answersByQuestion[$qNum] = $isCorrect;
                }

                // Determine tour index from question number using offsets
                $tourIndex = 0;
                for ($t = $toursCount - 1; $t >= 0; $t--) {
                    if ($qNum > $tourOffsets[$t]) {
                        $tourIndex = $t;
                        break;
                    }
                }
                $tourTotals[$tourIndex] += $isCorrect;
            }

            $result[$sessionTeam->getId()] = new TeamBreakdownDTO(
                answers: array_values($answersByQuestion),
                tourScores: $tourTotals,
            );
        }

        return $result;
    }

    /**
     * @return list<SessionTeamDTO>
     * @throws DbalException
     */
    private function buildSessionResults(TournamentSession $session): array
    {
        $sessionTeams = $this->sessionTeamRepository->findBySessionOrderedByScore($session);

        return $this->resultBuilder->build($sessionTeams, $session->getTournament()->getSeason());
    }
}
