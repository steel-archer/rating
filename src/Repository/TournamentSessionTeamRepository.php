<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TournamentSessionTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSessionTeam::class);
    }

    /** @return list<TournamentSessionTeam> */
    public function findBySessionWithTeam(TournamentSession $session): array
    {
        return $this->createQueryBuilder('st')
            ->join('st.team', 'team')
            ->addSelect('team')
            ->where('st.tournamentSession = :s')
            ->setParameter('s', $session)
            ->orderBy('st.score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<TournamentSessionTeam> */
    public function findByTournamentWithTeam(Tournament $tournament): array
    {
        return $this->createQueryBuilder('st')
            ->join('st.team', 'team')
            ->addSelect('team')
            ->join('st.tournamentSession', 'ts')
            ->where('ts.tournament = :t')
            ->setParameter('t', $tournament)
            ->orderBy('st.score', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
