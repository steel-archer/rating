<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Venue;
use App\Entity\VenueRepresentative;
use App\Repository\PlayerRepository;
use App\Repository\TownRepository;
use App\Repository\VenueRepository;
use App\Repository\VenueRepresentativeRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class VenueManagementService
{
    public function __construct(
        private EntityManagerInterface $em,
        private VenueRepository $venueRepository,
        private VenueRepresentativeRepository $representativeRepository,
        private TownRepository $townRepository,
        private PlayerRepository $playerRepository,
    ) {
    }

    public function create(string $name, int $townId, User $user): Venue
    {
        $player = $user->getPlayer()
            ?? throw new LogicException('venue.error.no_player');

        $town = $this->townRepository->find($townId)
            ?? throw new LogicException('venue.error.town_not_found');

        if ($this->venueRepository->existsByNameAndTown($name, $townId)) {
            throw new LogicException('venue.error.duplicate');
        }

        $venue = new Venue();
        $venue->setName($name);
        $venue->setTown($town);
        $venue->setCreatedBy($user);

        $this->em->persist($venue);

        $representative = new VenueRepresentative();
        $representative->setVenue($venue);
        $representative->setPlayer($player);

        $this->em->persist($representative);
        $this->em->flush();

        return $venue;
    }

    public function approve(Venue $venue): void
    {
        if ($venue->isApproved()) {
            throw new LogicException('Venue is already approved');
        }

        $venue->setIsApproved(true);
        $this->em->flush();
    }

    public function reject(Venue $venue): void
    {
        if ($venue->isApproved()) {
            throw new LogicException('Cannot reject approved venue');
        }

        $representatives = $this->representativeRepository->findByVenueWithPlayer($venue);
        foreach ($representatives as $representative) {
            $this->em->remove($representative);
        }

        $this->em->remove($venue);
        $this->em->flush();
    }

    /**
     * @param list<int> $playerIds
     */
    public function updateRepresentatives(Venue $venue, array $playerIds): void
    {
        if (!$venue->isApproved()) {
            throw new LogicException('Cannot edit unapproved venue');
        }

        $creatorPlayerId = $venue->getCreatedBy()?->getPlayer()?->getId();

        // Ensure creator is always among representatives
        if ($creatorPlayerId !== null && !in_array($creatorPlayerId, $playerIds, true)) {
            $playerIds[] = $creatorPlayerId;
        }

        $existing = $this->representativeRepository->findByVenueWithPlayer($venue);

        $existingPlayerIds = array_map(
            static fn(VenueRepresentative $representative) => $representative->getPlayer()->getId(),
            $existing,
        );

        // Remove representatives not in new list (never remove creator)
        foreach ($existing as $representative) {
            if ($representative->getPlayer()->getId() === $creatorPlayerId) {
                continue;
            }
            if (!in_array($representative->getPlayer()->getId(), $playerIds, true)) {
                $this->em->remove($representative);
            }
        }

        // Add new representatives
        $newPlayerIds = array_filter(
            array_unique($playerIds),
            static fn(int $playerId) => !in_array($playerId, $existingPlayerIds, true),
        );

        $players = $newPlayerIds !== []
            ? $this->playerRepository->findBy(['id' => array_values($newPlayerIds)])
            : [];
        $playerIndex = [];
        foreach ($players as $player) {
            $playerIndex[$player->getId()] = $player;
        }

        foreach ($newPlayerIds as $playerId) {
            $player = $playerIndex[$playerId] ?? null;
            if ($player === null) {
                continue;
            }
            $representative = new VenueRepresentative();
            $representative->setVenue($venue);
            $representative->setPlayer($player);
            $this->em->persist($representative);
        }

        $this->em->flush();
    }
}
