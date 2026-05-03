<?php

namespace App\Repository;

use App\Entity\Town;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TownRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Town::class);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function suggest(string $query): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.id', 't.name')
            ->where('t.name LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('t.name')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();
    }
}
