<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Response\Venue\VenueTournamentDTO;
use App\Entity\SessionClaim;
use App\Enum\SessionClaimStatus;
use App\Entity\Tournament;
use App\Entity\TournamentSession;
use App\Entity\Venue;
use App\Mapping\Mapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentSession> */
class TournamentSessionRepository extends ServiceEntityRepository
{
    private const int PER_PAGE = 50;

    public function __construct(ManagerRegistry $registry, private Mapper $mapper)
    {
        parent::__construct($registry, TournamentSession::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findWithRepresentativeUser(int $id): ?TournamentSession
    {
        return $this->createQueryBuilder('ts')
            ->join('ts.representative', 'rep')
            ->leftJoin('rep.user', 'u')
            ->join('ts.tournament', 't')
            ->addSelect('rep', 'u', 't')
            ->where('ts.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
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
            ->leftJoin('rep.user', 'repUser')
            ->leftJoin('ts.host', 'host')
            ->leftJoin('host.user', 'hostUser')
            ->join(SessionClaim::class, 'sc', 'WITH', 'sc.session = ts')
            ->addSelect('v', 'town', 'rep', 'repUser', 'host', 'hostUser')
            ->where('ts.tournament = :t')
            ->andWhere('sc.status = :status')
            ->setParameter('t', $tournament)
            ->setParameter('status', SessionClaimStatus::Approved->value)
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
            ->join(SessionClaim::class, 'sc', 'WITH', 'sc.session = ts')
            ->where('ts.tournament = :t')
            ->andWhere('sc.status = :status')
            ->setParameter('t', $tournament)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<VenueTournamentDTO>
     */
    public function findByVenuePaginated(Venue $venue, int $page): array
    {
        $rows = $this->createQueryBuilder('ts')
            ->join('ts.tournament', 't')
            ->join(SessionClaim::class, 'sc', 'WITH', 'sc.session = ts')
            ->select('t.id AS tournamentId', 't.name AS tournamentName', 'ts.playedAt')
            ->where('ts.venue = :venue')
            ->andWhere('sc.status = :status')
            ->setParameter('venue', $venue)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->orderBy('ts.playedAt', 'DESC')
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getArrayResult();

        return $this->mapper->mapMultiple($rows, VenueTournamentDTO::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPageNumberByVenue(Venue $venue): int
    {
        return max(1, (int) ceil($this->countByVenue($venue) / self::PER_PAGE));
    }

    public function countByVenue(Venue $venue): int
    {
        return (int) $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->join(SessionClaim::class, 'sc', 'WITH', 'sc.session = ts')
            ->where('ts.venue = :venue')
            ->andWhere('sc.status = :status')
            ->setParameter('venue', $venue)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
