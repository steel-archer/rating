<?php

namespace App\Repository;

use App\Entity\Player;
use App\Entity\Team;
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
            ->addSelect('st')
            ->join('st.tournamentSession', 'ts')
            ->where('ts.tournament = :t')
            ->setParameter('t', $tournament)
            ->orderBy('p.lastName')
            ->getQuery()
            ->getResult();
    }

    /** @return list<TournamentSessionTeamPlayer> */
    public function findByTeamWithFullContext(Team $team): array
    {
        return $this->createQueryBuilder('stp')
            ->join('stp.player', 'p')
            ->addSelect('p')
            ->join('stp.tournamentSessionTeam', 'st')
            ->addSelect('st')
            ->join('st.tournamentSession', 'ts')
            ->addSelect('ts')
            ->join('ts.tournament', 'tournament')
            ->addSelect('tournament')
            ->where('st.team = :team')
            ->setParameter('team', $team)
            ->orderBy('ts.playedAt', 'DESC')
            ->addOrderBy('p.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<TournamentSessionTeamPlayer> */
    public function findByPlayerWithFullContext(Player $player): array
    {
        return $this->createQueryBuilder('stp')
            ->join('stp.tournamentSessionTeam', 'st')
            ->addSelect('st')
            ->join('st.team', 'team')
            ->addSelect('team')
            ->join('team.town', 'town')
            ->addSelect('town')
            ->join('st.tournamentSession', 'ts')
            ->addSelect('ts')
            ->join('ts.tournament', 'tournament')
            ->addSelect('tournament')
            ->where('stp.player = :player')
            ->setParameter('player', $player)
            ->orderBy('ts.playedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
