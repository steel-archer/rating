<?php

namespace App\Repository;

use App\Entity\Season;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Season> */
class SeasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Season::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findCurrent(): ?Season
    {
        return $this->createQueryBuilder('s')
            ->where('s.startedAt <= CURRENT_TIMESTAMP()')
            ->andWhere('s.endedAt >= CURRENT_TIMESTAMP()')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByDate(DateTime $date): ?Season
    {
        return $this->createQueryBuilder('s')
            ->where('s.startedAt <= :date')
            ->andWhere('s.endedAt >= :date')
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
