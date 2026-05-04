<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentSession;
use App\Entity\Venue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentSession> */
class TournamentSessionRepository extends ServiceEntityRepository
{
    private const int PER_PAGE = 50;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSession::class);
    }

    /**
     * @return list<TournamentSession>
     */
    public function findByTournamentPaginated(Tournament $tournament, int $page): array
    {
        return $this->createQueryBuilder('ts')
            ->join('ts.venue', 'v')
            ->join('v.town', 'town')
            ->join('ts.representative', 'rep')
            ->leftJoin('ts.host', 'host')
            ->addSelect('v', 'town', 'rep', 'host')
            ->where('ts.tournament = :t')
            ->setParameter('t', $tournament)
            ->orderBy('town.name', 'ASC')
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPageNumberByTournament(Tournament $tournament): int
    {
        return max(1, (int) ceil($this->countByTournament($tournament) / self::PER_PAGE));
    }

    public function countByTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->where('ts.tournament = :t')
            ->setParameter('t', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{tournamentId: int, tournamentName: string, playedAt: ?\DateTime}>
     */
    public function findByVenuePaginated(Venue $venue, int $page): array
    {
        return $this->createQueryBuilder('ts')
            ->join('ts.tournament', 't')
            ->select('t.id AS tournamentId', 't.name AS tournamentName', 'ts.playedAt')
            ->where('ts.venue = :venue')
            ->setParameter('venue', $venue)
            ->orderBy('ts.playedAt', 'DESC')
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPageNumberByVenue(Venue $venue): int
    {
        $total = (int) $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->where('ts.venue = :venue')
            ->setParameter('venue', $venue)
            ->getQuery()
            ->getSingleScalarResult();

        return max(1, (int) ceil($total / self::PER_PAGE));
    }

    public function countByVenue(Venue $venue): int
    {
        return (int) $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->where('ts.venue = :venue')
            ->setParameter('venue', $venue)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
