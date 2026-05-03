<?php

namespace App\Repository;

use App\DTO\Request\TournamentListRequestDTO;
use App\Entity\Tournament;
use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use App\Helper\LikeEscape;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TournamentRepository extends ServiceEntityRepository
{
    private const int PER_PAGE = 50;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    /**
     * @throws NonUniqueResultException
     */
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

    /**
     * @return list<array{id: int, name: string, startedAt: ?\DateTime, endedAt: ?\DateTime, difficulty: ?float, trueDl: ?float, teamCount: int}>
     */
    public function findForList(TournamentListRequestDTO $requestDto): array
    {
        return $this->buildFilteredQuery($requestDto)
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
            ->setFirstResult(($requestDto->page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPageNumber(TournamentListRequestDTO $requestDto): int
    {
        $total = (int) $this->buildFilteredQuery($requestDto)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return max(1, (int) ceil($total / self::PER_PAGE));
    }

    private function buildFilteredQuery(TournamentListRequestDTO $requestDto): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        if ($requestDto->name !== null && $requestDto->name !== '') {
            $qb->andWhere('t.name LIKE :name')
                ->setParameter('name', LikeEscape::contains($requestDto->name));
        }

        return $qb;
    }
}
