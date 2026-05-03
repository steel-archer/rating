<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentSessionTeamPlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TournamentSessionTeamPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSessionTeamPlayer::class);
    }

    /** @return list<TournamentSessionTeamPlayer> */
    public function findByTournamentWithPlayer(Tournament $tournament): array
    {
        return $this->createQueryBuilder('stp')
            ->join('stp.player', 'p')
            ->addSelect('p')
            ->join('stp.tournamentSessionTeam', 'st')
            ->join('st.tournamentSession', 'ts')
            ->where('ts.tournament = :t')
            ->setParameter('t', $tournament)
            ->orderBy('p.lastName')
            ->getQuery()
            ->getResult();
    }
}
