<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentModerationClaim;
use App\Entity\TournamentModerationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentModerationClaim> */
class TournamentModerationClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentModerationClaim::class);
    }

    public function findByTournament(Tournament $tournament): ?TournamentModerationClaim
    {
        return $this->findOneBy(['tournament' => $tournament]);
    }

    /**
     * @return list<TournamentModerationClaim>
     */
    public function findByStatus(TournamentModerationStatus $status): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.tournament', 't')
            ->addSelect('t')
            ->where('c.status = :status')
            ->setParameter('status', $status->value)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
