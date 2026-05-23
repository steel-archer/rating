<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\DTO\Request\TournamentListRequestDTO;
use App\Classic\DTO\Response\Tournament\TournamentListItemDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentModerationClaim;
use App\Classic\Entity\TournamentSession;
use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Enum\TournamentStatus;
use App\Common\Entity\Player;
use App\Common\Helper\LikeEscape;
use App\Common\Mapping\Mapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Tournament> */
class TournamentRepository extends ServiceEntityRepository
{
    private const int PER_PAGE = 50;

    public function __construct(ManagerRegistry $registry, private Mapper $mapper)
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
     * @return list<TournamentListItemDTO>
     */
    public function findForList(TournamentListRequestDTO $requestDto): array
    {
        $rows = $this->buildFilteredQuery($requestDto)
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

        return $this->mapper->mapMultiple($rows, TournamentListItemDTO::class);
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

    /**
     * @return list<Tournament>
     */
    public function findByCreator(Player $player, string $sort = 'DESC', int $page = 1): array
    {
        $direction = strtoupper($sort) === 'ASC' ? 'ASC' : 'DESC';

        return $this->createQueryBuilder('t')
            ->leftJoin(
                TournamentModerationClaim::class,
                'c',
                'WITH',
                'c.tournament = t',
            )
            ->where('t.createdBy = :player')
            ->setParameter('player', $player)
            ->orderBy('CASE WHEN c.resolvedAt IS NOT NULL THEN c.resolvedAt WHEN c.createdAt IS NOT NULL THEN c.createdAt ELSE t.startedAt END', $direction)
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getResult();
    }

    public function countByCreator(Player $player): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.createdBy = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildFilteredQuery(TournamentListRequestDTO $requestDto): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.status = :published')
            ->setParameter('published', TournamentStatus::Published->value);

        if ($requestDto->name !== null && $requestDto->name !== '') {
            $qb->andWhere('t.name LIKE :name')
                ->setParameter('name', LikeEscape::contains($requestDto->name));
        }

        return $qb;
    }
}
