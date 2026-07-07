<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\Entity\CaptainClaim;
use App\Classic\Enum\CaptainClaimStatus;
use App\Common\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CaptainClaim> */
class CaptainClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CaptainClaim::class);
    }

    public function findPendingByPlayer(Player $player): ?CaptainClaim
    {
        return $this->findOneBy([
            'player' => $player,
            'status' => CaptainClaimStatus::Pending,
        ]);
    }

    /**
     * @return list<CaptainClaim>
     */
    public function findByStatus(CaptainClaimStatus $status): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.player', 'player')
            ->join('c.team', 'team')
            ->join('team.town', 'town')
            ->addSelect('player', 'team', 'town')
            ->where('c.status = :status')
            ->setParameter('status', $status)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CaptainClaim>
     */
    public function findResolved(int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.player', 'player')
            ->join('c.team', 'team')
            ->join('team.town', 'town')
            ->addSelect('player', 'team', 'town')
            ->where('c.status != :pending')
            ->setParameter('pending', CaptainClaimStatus::Pending)
            ->orderBy('c.resolvedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
