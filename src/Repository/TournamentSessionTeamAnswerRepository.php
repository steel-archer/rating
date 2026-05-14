<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeamAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentSessionTeamAnswer> */
class TournamentSessionTeamAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSessionTeamAnswer::class);
    }

    /**
     * @param list<int> $sessionTeamIds
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function hasAnswersForTeams(array $sessionTeamIds): bool
    {
        if ($sessionTeamIds === []) {
            return false;
        }

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @param list<int> $sessionTeamIds
     *
     * @return list<array{teamId: int, questionNumber: int, isCorrect: bool}>
     */
    public function findBySessionTeamIds(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->select('IDENTITY(a.tournamentSessionTeam) AS teamId', 'a.questionNumber', 'a.isCorrect')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return list<array{teamId: int, questionNumber: int, isCorrect: bool}>
     */
    public function findBySession(TournamentSession $session): array
    {
        return $this->createQueryBuilder('a')
            ->select('IDENTITY(a.tournamentSessionTeam) AS teamId', 'a.questionNumber', 'a.isCorrect')
            ->join('a.tournamentSessionTeam', 'st')
            ->where('st.tournamentSession = :session')
            ->setParameter('session', $session)
            ->getQuery()
            ->getArrayResult();
    }
}
