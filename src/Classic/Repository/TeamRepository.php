<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\DTO\Request\TeamListRequestDTO;
use App\Common\DTO\Response\SuggestItemDTO;
use App\Classic\DTO\Response\Team\TeamListItemDTO;
use App\Classic\Entity\Team;
use App\Common\Entity\Town;
use App\Common\Helper\LikeEscape;
use App\Common\Mapping\Mapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Team> */
class TeamRepository extends ServiceEntityRepository
{
    private const int PER_PAGE = 50;

    public function __construct(ManagerRegistry $registry, private Mapper $mapper)
    {
        parent::__construct($registry, Team::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findWithTown(int $id): ?Team
    {
        return $this->createQueryBuilder('t')
            ->join('t.town', 'town')
            ->addSelect('town')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByNameAndTown(string $name, Town $town): ?Team
    {
        return $this->createQueryBuilder('t')
            ->where('t.name = :name')
            ->andWhere('t.town = :town')
            ->setParameter('name', $name)
            ->setParameter('town', $town)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<TeamListItemDTO>
     */
    public function findForList(TeamListRequestDTO $requestDto): array
    {
        $orderField = $requestDto->sort === 'town' ? 'town.name' : 't.name';

        $rows = $this->buildFilteredQuery($requestDto)
            ->select('t.id', 't.name', 'town.name AS townName', 'country.name AS countryName')
            ->orderBy($orderField, strtoupper($requestDto->dir))
            ->setFirstResult(($requestDto->page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getArrayResult();

        return $this->mapper->mapMultiple($rows, TeamListItemDTO::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPageNumber(TeamListRequestDTO $requestDto): int
    {
        $total = (int) $this->buildFilteredQuery($requestDto)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return max(1, (int) ceil($total / self::PER_PAGE));
    }

    /**
     * @return list<SuggestItemDTO>
     */
    public function suggest(string $query): array
    {
        $rows = $this->createQueryBuilder('t')
            ->join('t.town', 'town')
            ->select('t.id', 't.name', 'town.name AS townName')
            ->where('t.name LIKE :q')
            ->setParameter('q', LikeEscape::contains($query))
            ->setMaxResults(10)
            ->orderBy('t.name')
            ->getQuery()
            ->getArrayResult();

        $rows = array_map(static fn(array $row) => [
            'id' => $row['id'],
            'name' => $row['name'] . ' (' . $row['townName'] . ')',
        ], $rows);

        return $this->mapper->mapMultiple($rows, SuggestItemDTO::class);
    }

    private function buildFilteredQuery(TeamListRequestDTO $requestDto): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.town', 'town')
            ->join('town.country', 'country');

        if ($requestDto->name !== null && $requestDto->name !== '') {
            $qb->andWhere('t.name LIKE :name')
                ->setParameter('name', LikeEscape::contains($requestDto->name));
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
