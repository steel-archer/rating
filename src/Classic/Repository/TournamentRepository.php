<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\DTO\Request\TournamentListRequestDTO;
use App\Classic\DTO\Response\Tournament\TournamentListItemDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentModerationClaim;
use App\Classic\Entity\TournamentOfficial;
use App\Classic\Entity\TournamentSession;
use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Enum\TournamentOfficialRole;
use App\Classic\Enum\TournamentPeriod;
use App\Classic\Enum\TournamentStatus;
use App\Common\Entity\Player;
use App\Common\Helper\LikeEscape;
use App\Common\Mapping\Mapper;
use DateTimeImmutable;
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
                't.format',
                't.onlineMode',
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
     * Tournaments the player organizes (creator and co-organizers alike),
     * matched by the organizer role in TournamentOfficial rather than by
     * Tournament.createdBy, so co-organizers see what they can edit.
     *
     * @return list<Tournament>
     */
    public function findByOrganizer(Player $player, string $sort = 'DESC', int $page = 1): array
    {
        $direction = strtoupper($sort) === 'ASC' ? 'ASC' : 'DESC';

        return $this->createQueryBuilder('t')
            ->join(
                TournamentOfficial::class,
                'o',
                'WITH',
                'o.tournament = t AND o.player = :player AND o.role = :role',
            )
            ->leftJoin(
                TournamentModerationClaim::class,
                'c',
                'WITH',
                'c.tournament = t',
            )
            ->setParameter('player', $player)
            ->setParameter('role', TournamentOfficialRole::Organizer->value)
            ->orderBy('CASE WHEN c.resolvedAt IS NOT NULL THEN c.resolvedAt WHEN c.createdAt IS NOT NULL THEN c.createdAt ELSE t.startedAt END', $direction)
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getResult();
    }

    public function countByOrganizer(Player $player): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.id)')
            ->join(
                TournamentOfficial::class,
                'o',
                'WITH',
                'o.tournament = t AND o.player = :player AND o.role = :role',
            )
            ->setParameter('player', $player)
            ->setParameter('role', TournamentOfficialRole::Organizer->value)
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

        if ($requestDto->period !== null) {
            $now = new DateTimeImmutable();

            match ($requestDto->period) {
                TournamentPeriod::Past => $qb
                    ->andWhere('t.endedAt < :now')
                    ->setParameter('now', $now),
                TournamentPeriod::Active => $qb
                    ->andWhere('t.startedAt <= :now')
                    ->andWhere('(t.endedAt IS NULL OR t.endedAt >= :now)')
                    ->setParameter('now', $now),
                TournamentPeriod::Future => $qb
                    ->andWhere('(t.startedAt IS NULL OR t.startedAt > :now)')
                    ->setParameter('now', $now),
            };
        }

        if ($requestDto->format !== null) {
            $qb->andWhere('t.format = :format')
                ->setParameter('format', $requestDto->format->value);
        }

        if ($requestDto->onlineMode !== null) {
            $qb->andWhere('t.onlineMode = :onlineMode')
                ->setParameter('onlineMode', $requestDto->onlineMode->value);
        }

        return $qb;
    }
}
