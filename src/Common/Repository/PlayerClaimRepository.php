<?php

declare(strict_types=1);

namespace App\Common\Repository;

use App\Common\Entity\PlayerClaim;
use App\Common\Entity\User;
use App\Common\Enum\PlayerClaimStatus;
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
            ->leftJoin('p.user', 'pu')
            ->leftJoin('p.town', 'pt')
            ->leftJoin('pt.country', 'ptc')
            ->leftJoin('c.town', 't')
            ->leftJoin('t.country', 'tc')
            ->leftJoin('c.country', 'cc')
            ->addSelect('u', 'p', 'pu', 'pt', 'ptc', 't', 'tc', 'cc')
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
