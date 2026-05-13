<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Player;
use App\Entity\Tournament;
use App\Entity\TournamentOfficial;
use App\Enum\TournamentOfficialRole;
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
            ->leftJoin('p.user', 'pu')
            ->addSelect('p', 'pu')
            ->where('o.tournament = :t')
            ->setParameter('t', $tournament)
            ->orderBy('o.role')
            ->addOrderBy('p.lastName')
            ->getQuery()
            ->getResult();
    }

    public function isOrganizer(Player $player, Tournament $tournament): bool
    {
        return $this->hasRole($player, $tournament, TournamentOfficialRole::Organizer);
    }

    public function hasRole(Player $player, Tournament $tournament, TournamentOfficialRole $role): bool
    {
        return (bool) $this->createQueryBuilder('o')
            ->select('1')
            ->where('o.tournament = :tournament')
            ->andWhere('o.player = :player')
            ->andWhere('o.role = :role')
            ->setParameter('tournament', $tournament)
            ->setParameter('player', $player)
            ->setParameter('role', $role)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
