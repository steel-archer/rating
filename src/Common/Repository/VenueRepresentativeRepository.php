<?php

declare(strict_types=1);

namespace App\Common\Repository;

use App\Common\Entity\Player;
use App\Common\Entity\Venue;
use App\Common\Entity\VenueRepresentative;
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
            ->leftJoin('player.user', 'playerUser')
            ->addSelect('player', 'playerUser')
            ->where('vr.venue = :venue')
            ->setParameter('venue', $venue)
            ->orderBy('player.lastName')
            ->getQuery()
            ->getResult();
    }

    public function isRepresentative(Player $player, Venue $venue): bool
    {
        return (bool) $this->createQueryBuilder('vr')
            ->select('1')
            ->where('vr.venue = :venue')
            ->andWhere('vr.player = :player')
            ->setParameter('venue', $venue)
            ->setParameter('player', $player)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasVenuesByPlayer(Player $player): bool
    {
        return (bool) $this->createQueryBuilder('vr')
            ->select('1')
            ->join('vr.venue', 'v')
            ->where('vr.player = :player')
            ->andWhere('v.isApproved = true')
            ->setParameter('player', $player)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Venue>
     */
    public function findVenuesByPlayer(Player $player): array
    {
        $representatives = $this->createQueryBuilder('vr')
            ->join('vr.venue', 'v')
            ->join('v.town', 'town')
            ->addSelect('v', 'town')
            ->where('vr.player = :player')
            ->andWhere('v.isApproved = true')
            ->setParameter('player', $player)
            ->orderBy('v.name')
            ->getQuery()
            ->getResult();

        return array_map(static fn(VenueRepresentative $vr) => $vr->getVenue(), $representatives);
    }
}
