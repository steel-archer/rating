<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function findWithSeason(int $id): ?Tournament
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.season', 's')
            ->addSelect('s')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private const int PER_PAGE = 5;

    /**
     * @return list<array{id: int, name: string, startedAt: ?\DateTime, endedAt: ?\DateTime, difficulty: ?float, trueDl: ?float, teamCount: int}>
     */
    public function findForList(int $page): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin(TournamentSession::class, 'ts', 'WITH', 'ts.tournament = t')
            ->leftJoin(TournamentSessionTeam::class, 'tst', 'WITH', 'tst.tournamentSession = ts')
            ->select(
                't.id',
                't.name',
                't.startedAt',
                't.endedAt',
                't.difficulty',
                't.trueDl',
                'COUNT(DISTINCT tst.id) AS teamCount',
            )
            ->groupBy('t.id')
            ->orderBy('t.startedAt', 'DESC')
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getArrayResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getLastPage(): int
    {
        return max(1, (int) ceil($this->countAll() / self::PER_PAGE));
    }
}
