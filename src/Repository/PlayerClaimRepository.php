<?php

namespace App\Repository;

use App\Entity\PlayerClaim;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlayerClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerClaim::class);
    }

    /** @return list<PlayerClaim> */
    public function findPending(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', PlayerClaim::STATUS_PENDING)
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->leftJoin('c.player', 'p')
            ->addSelect('p')
            ->leftJoin('c.town', 't')
            ->addSelect('t')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function hasPendingClaim(User $user): bool
    {
        return $this->count(['user' => $user, 'status' => PlayerClaim::STATUS_PENDING]) > 0;
    }

    public function rejectOtherPendingClaims(PlayerClaim $approvedClaim): void
    {
        if ($approvedClaim->getPlayer() === null) {
            return;
        }

        $this->createQueryBuilder('c')
            ->update()
            ->set('c.status', ':rejected')
            ->where('c.status = :pending')
            ->andWhere('c.id != :id')
            ->andWhere('c.player = :player')
            ->setParameter('rejected', PlayerClaim::STATUS_REJECTED)
            ->setParameter('pending', PlayerClaim::STATUS_PENDING)
            ->setParameter('id', $approvedClaim->getId())
            ->setParameter('player', $approvedClaim->getPlayer())
            ->getQuery()
            ->execute();
    }
}
