<?php

namespace App\Repository;

use App\DTO\Request\PlayerListRequestDTO;
use App\Entity\Player;
use App\Entity\Season;
use App\Entity\TeamPlayer;
use App\Entity\User;
use App\Helper\LikeEscape;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Player> */
class PlayerRepository extends ServiceEntityRepository
{
    private const int PER_PAGE = 50;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findWithTown(int $id): ?Player
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.town', 't')
            ->addSelect('t')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<array{id: int, fullName: string, townName: ?string, teamId: ?int, teamName: ?string}>
     */
    public function findForList(PlayerListRequestDTO $requestDto, ?Season $currentSeason = null): array
    {
        $qb = $this->buildFilteredQuery($requestDto)
            ->select(
                'p.id',
                "CONCAT(p.lastName, ' ', p.firstName, ' ', COALESCE(p.patronymic, '')) AS fullName",
                'town.name AS townName',
            )
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->setFirstResult(($requestDto->page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE);

        if ($currentSeason !== null) {
            $qb->leftJoin(TeamPlayer::class, 'tp', 'WITH', 'tp.player = p AND tp.season = :season')
                ->leftJoin('tp.team', 'team')
                ->addSelect('team.id AS teamId', 'team.name AS teamName')
                ->setParameter('season', $currentSeason);
        } else {
            $qb->addSelect('0 AS teamId', "'' AS teamName");
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPageNumber(PlayerListRequestDTO $requestDto): int
    {
        $total = (int) $this->buildFilteredQuery($requestDto)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return max(1, (int) ceil($total / self::PER_PAGE));
    }

    /**
     * @return list<array{id: int, fullName: string, townName: ?string}>
     */
    public function findFreeForList(PlayerListRequestDTO $requestDto): array
    {
        return $this->buildFilteredQuery($requestDto)
            ->select(
                'p.id',
                "CONCAT(p.lastName, ' ', p.firstName, ' ', COALESCE(p.patronymic, '')) AS fullName",
                'town.name AS townName',
            )
            ->leftJoin(User::class, 'u', 'WITH', 'u.player = p')
            ->andWhere('u.id IS NULL')
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->setFirstResult(($requestDto->page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getFreeLastPageNumber(PlayerListRequestDTO $requestDto): int
    {
        $total = (int) $this->buildFilteredQuery($requestDto)
            ->select('COUNT(p.id)')
            ->leftJoin(User::class, 'u', 'WITH', 'u.player = p')
            ->andWhere('u.id IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return max(1, (int) ceil($total / self::PER_PAGE));
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function suggest(string $query): array
    {
        $rows = $this->createQueryBuilder('p')
            ->leftJoin('p.town', 'town')
            ->select(
                'p.id',
                "CONCAT(p.lastName, ' ', p.firstName, ' ', COALESCE(p.patronymic, '')) AS name",
                'town.name AS townName',
            )
            ->where("CONCAT(p.lastName, ' ', p.firstName, ' ', COALESCE(p.patronymic, '')) LIKE :q")
            ->setParameter('q', LikeEscape::contains($query))
            ->setMaxResults(10)
            ->orderBy('p.lastName')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row) => [
            'id' => $row['id'],
            'name' => trim($row['name']) . ($row['townName'] ? ' (' . $row['townName'] . ')' : ''),
        ], $rows);
    }

    private function buildFilteredQuery(PlayerListRequestDTO $requestDto): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.town', 'town')
            ->leftJoin('town.country', 'country');

        if ($requestDto->lastName !== null && $requestDto->lastName !== '') {
            $qb->andWhere('p.lastName LIKE :lastName')
                ->setParameter('lastName', LikeEscape::contains($requestDto->lastName));
        }

        if ($requestDto->firstName !== null && $requestDto->firstName !== '') {
            $qb->andWhere('p.firstName LIKE :firstName')
                ->setParameter('firstName', LikeEscape::contains($requestDto->firstName));
        }

        if ($requestDto->patronymic !== null && $requestDto->patronymic !== '') {
            $qb->andWhere('p.patronymic LIKE :patronymic')
                ->setParameter('patronymic', LikeEscape::contains($requestDto->patronymic));
        }

        if ($requestDto->townId !== null) {
            $qb->andWhere('town.id = :townId')
                ->setParameter('townId', $requestDto->townId);
        }

        if ($requestDto->countryId !== null) {
            $qb->andWhere('country.id = :countryId')
                ->setParameter('countryId', $requestDto->countryId);
        }

        return $qb;
    }
}
