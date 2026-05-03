<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TournamentSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSession::class);
    }

    /** @return list<TournamentSession> */
    public function findByTournamentWithVenue(Tournament $tournament): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.venue', 'v')
            ->addSelect('v')
            ->join('v.town', 't')
            ->addSelect('t')
            ->join('s.representative', 'rep')
            ->addSelect('rep')
            ->leftJoin('s.host', 'host')
            ->addSelect('host')
            ->where('s.tournament = :t')
            ->setParameter('t', $tournament)
            ->orderBy('t.name')
            ->getQuery()
            ->getResult();
    }
}
