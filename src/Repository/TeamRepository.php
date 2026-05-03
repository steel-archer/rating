<?php

namespace App\Repository;

use App\DTO\Request\TeamListRequestDTO;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TeamRepository extends ServiceEntityRepository
{
    private const int PER_PAGE = 5;

    public function __construct(ManagerRegistry $registry)
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
     * @return list<array{id: int, name: string, townName: string, countryName: string}>
     */
    public function findForList(TeamListRequestDTO $requestDto): array
    {
        $orderField = $requestDto->sort === 'town' ? 'town.name' : 't.name';

        return $this->buildFilteredQuery($requestDto)
            ->select('t.id', 't.name', 'town.name AS townName', 'country.name AS countryName')
            ->orderBy($orderField, strtoupper($requestDto->dir))
            ->setFirstResult(($requestDto->page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPage(TeamListRequestDTO $requestDto): int
    {
        $total = (int) $this->buildFilteredQuery($requestDto)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return max(1, (int) ceil($total / self::PER_PAGE));
    }

    private function buildFilteredQuery(TeamListRequestDTO $requestDto): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.town', 'town')
            ->join('town.country', 'country');

        if ($requestDto->name !== null && $requestDto->name !== '') {
            $qb->andWhere('t.name LIKE :name')
                ->setParameter('name', '%' . $requestDto->name . '%');
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
