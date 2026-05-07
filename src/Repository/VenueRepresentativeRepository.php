<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Venue;
use App\Entity\VenueRepresentative;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<VenueRepresentative> */
class VenueRepresentativeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VenueRepresentative::class);
    }

    /**
     * @return list<VenueRepresentative>
     */
    public function findByVenueWithPlayer(Venue $venue): array
    {
        return $this->createQueryBuilder('vr')
            ->join('vr.player', 'player')
            ->addSelect('player')
            ->where('vr.venue = :venue')
            ->setParameter('venue', $venue)
            ->orderBy('player.lastName')
            ->getQuery()
            ->getResult();
    }
}
