<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PlayerClaim;
use App\Enum\PlayerClaimStatus;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PlayerClaim> */
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
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.player', 'p')
            ->leftJoin('c.town', 't')
            ->addSelect('u', 'p', 't')
            ->andWhere('c.status = :status')
            ->setParameter('status', PlayerClaimStatus::Pending)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function hasPendingClaim(User $user): bool
    {
        return $this->count(['user' => $user, 'status' => PlayerClaimStatus::Pending]) > 0;
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
            ->setParameter('rejected', PlayerClaimStatus::Rejected->value)
            ->setParameter('pending', PlayerClaimStatus::Pending->value)
            ->setParameter('id', $approvedClaim->getId())
            ->setParameter('player', $approvedClaim->getPlayer())
            ->getQuery()
            ->execute();
    }
}
