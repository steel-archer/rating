<?php

declare(strict_types=1);

namespace App\Common\Repository;

use App\Common\Entity\Season;
use DateTimeImmutable;
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
    public function findByDate(DateTimeImmutable $date): ?Season
    {
        return $this->createQueryBuilder('s')
            ->where('s.startedAt <= :date')
            ->andWhere('s.endedAt >= :date')
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findPrevious(Season $current): ?Season
    {
        return $this->createQueryBuilder('s')
            ->where('s.endedAt < :start')
            ->setParameter('start', $current->getStartedAt())
            ->orderBy('s.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns the season that starts immediately after the given one, if it exists.
     *
     * @throws NonUniqueResultException
     */
    public function findNext(Season $current): ?Season
    {
        return $this->createQueryBuilder('s')
            ->where('s.startedAt > :start')
            ->setParameter('start', $current->getStartedAt())
            ->orderBy('s.startedAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns the most recent season by start date, or null if none exist.
     *
     * @throws NonUniqueResultException
     */
    public function findLatest(): ?Season
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
