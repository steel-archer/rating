<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Town;
use App\Helper\LikeEscape;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Town> */
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
            ->setParameter('q', LikeEscape::contains($query))
            ->orderBy('t.name')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();
    }
}
