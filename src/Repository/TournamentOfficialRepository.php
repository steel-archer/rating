<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentOfficial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentOfficial> */
class TournamentOfficialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentOfficial::class);
    }

    /** @return list<TournamentOfficial> */
    public function findByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.player', 'p')
            ->addSelect('p')
            ->where('o.tournament = :t')
            ->setParameter('t', $tournament)
            ->orderBy('o.role')
            ->addOrderBy('p.lastName')
            ->getQuery()
            ->getResult();
    }
}
