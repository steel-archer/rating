<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Player;
use App\Entity\SessionClaim;
use App\Enum\SessionClaimStatus;
use App\Entity\TournamentSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SessionClaim> */
class SessionClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionClaim::class);
    }

    public function findBySession(TournamentSession $session): ?SessionClaim
    {
        return $this->findOneBy(['session' => $session]);
    }

    /**
     * @return list<SessionClaim>
     */
    public function findByTournamentAndStatus(int $tournamentId, SessionClaimStatus $status): array
    {
        return $this->createQueryBuilder('sc')
            ->join('sc.session', 's')
            ->join('s.venue', 'v')
            ->join('v.town', 'town')
            ->join('s.representative', 'rep')
            ->leftJoin('rep.user', 'repUser')
            ->leftJoin('s.host', 'host')
            ->leftJoin('host.user', 'hostUser')
            ->join('sc.player', 'p')
            ->leftJoin('p.user', 'pUser')
            ->addSelect('s', 'v', 'town', 'rep', 'repUser', 'host', 'hostUser', 'p', 'pUser')
            ->where('s.tournament = :tournamentId')
            ->andWhere('sc.status = :status')
            ->setParameter('tournamentId', $tournamentId)
            ->setParameter('status', $status->value)
            ->orderBy('sc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<SessionClaim>
     */
    public function findByTournamentAndPlayer(int $tournamentId, Player $player): array
    {
        return $this->createQueryBuilder('sc')
            ->join('sc.session', 's')
            ->join('s.venue', 'v')
            ->join('v.town', 'town')
            ->join('s.representative', 'rep')
            ->leftJoin('rep.user', 'repUser')
            ->leftJoin('s.host', 'host')
            ->leftJoin('host.user', 'hostUser')
            ->addSelect('s', 'v', 'town', 'rep', 'repUser', 'host', 'hostUser')
            ->where('s.tournament = :tournamentId')
            ->andWhere('sc.player = :player')
            ->setParameter('tournamentId', $tournamentId)
            ->setParameter('player', $player)
            ->orderBy('sc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<SessionClaim>
     */
    public function findByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('sc')
            ->join('sc.session', 's')
            ->join('s.tournament', 't')
            ->join('s.venue', 'v')
            ->join('v.town', 'town')
            ->addSelect('s', 't', 'v', 'town')
            ->where('sc.player = :player')
            ->setParameter('player', $player)
            ->orderBy('sc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
