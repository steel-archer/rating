<?php

namespace App\Repository;

use App\DTO\Request\VenueListRequestDTO;
use App\Entity\Venue;
use App\Helper\LikeEscape;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Venue> */
class VenueRepository extends ServiceEntityRepository
{
    private const int PER_PAGE = 50;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Venue::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findWithTown(int $id): ?Venue
    {
        return $this->createQueryBuilder('v')
            ->join('v.town', 'town')
            ->addSelect('town')
            ->where('v.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<array{id: int, name: string, townName: string, countryName: string}>
     */
    public function findForList(VenueListRequestDTO $requestDto): array
    {
        return $this->buildFilteredQuery($requestDto)
            ->select('v.id', 'v.name', 'town.name AS townName', 'country.name AS countryName')
            ->orderBy('v.name', 'ASC')
            ->setFirstResult(($requestDto->page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPageNumber(VenueListRequestDTO $requestDto): int
    {
        $total = (int) $this->buildFilteredQuery($requestDto)
            ->select('COUNT(DISTINCT v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return max(1, (int) ceil($total / self::PER_PAGE));
    }

    private function buildFilteredQuery(VenueListRequestDTO $requestDto): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
            ->join('v.town', 'town')
            ->join('town.country', 'country');

        if ($requestDto->name !== null && $requestDto->name !== '') {
            $qb->andWhere('v.name LIKE :name')
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

        if ($requestDto->representative !== null && $requestDto->representative !== '') {
            $qb->join('App\Entity\VenueRepresentative', 'vr', 'WITH', 'vr.venue = v')
                ->join('vr.player', 'rep')
                ->andWhere("CONCAT(rep.lastName, ' ', rep.firstName, ' ', COALESCE(rep.patronymic, '')) LIKE :rep")
                ->setParameter('rep', LikeEscape::contains($requestDto->representative));
        }

        return $qb;
    }
}
