<?php

namespace App\Repository;

use App\Entity\Country;
use App\Helper\LikeEscape;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function suggest(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id', 'c.name')
            ->where('c.name LIKE :q')
            ->setParameter('q', LikeEscape::contains($query))
            ->orderBy('c.name')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();
    }
}
