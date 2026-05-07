<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Response\SuggestItemDTO;
use App\Entity\Town;
use App\Helper\LikeEscape;
use App\Mapping\Mapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Town> */
class TownRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Mapper $mapper,
    ) {
        parent::__construct($registry, Town::class);
    }

    /**
     * @return list<SuggestItemDTO>
     */
    public function suggest(string $query): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.id', 't.name')
            ->where('t.name LIKE :q')
            ->setParameter('q', LikeEscape::contains($query))
            ->orderBy('t.name')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();

        return $this->mapper->mapMultiple($rows, SuggestItemDTO::class);
    }
}
