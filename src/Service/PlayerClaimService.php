<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\PlayerClaim;
use App\Exception\PlayerClaimException;
use App\Repository\PlayerClaimRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PlayerClaimService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private PlayerClaimRepository $claimRepository,
    ) {
    }

    public function approve(int $id): void
    {
        $claim = $this->findPendingClaim($id);

        if ($claim->getUser()->getPlayer() !== null) {
            throw new PlayerClaimException('User already has a player.');
        }

        $player = $claim->isNew() ? $this->createPlayer($claim) : $this->resolveExistingPlayer($claim);

        $claim->getUser()->setPlayer($player);
        $claim->setStatus(PlayerClaim::STATUS_APPROVED);
        $this->em->flush();

        $this->claimRepository->rejectOtherPendingClaims($claim);
    }

    public function reject(int $id): void
    {
        $claim = $this->findPendingClaim($id);
        $claim->setStatus(PlayerClaim::STATUS_REJECTED);
        $this->em->flush();
    }

    private function findPendingClaim(int $id): PlayerClaim
    {
        $claim = $this->em->getRepository(PlayerClaim::class)->find($id);

        if ($claim === null || $claim->getStatus() !== PlayerClaim::STATUS_PENDING) {
            throw new PlayerClaimException('Claim not found or already processed.');
        }

        return $claim;
    }

    private function createPlayer(PlayerClaim $claim): Player
    {
        $player = new Player();
        $player->setLastName($claim->getLastName());
        $player->setFirstName($claim->getFirstName() ?? '');
        $player->setPatronymic($claim->getPatronymic());
        $player->setTown($claim->getTown());
        $this->em->persist($player);

        return $player;
    }

    private function resolveExistingPlayer(PlayerClaim $claim): Player
    {
        $player = $claim->getPlayer();

        if ($this->userRepository->findOneBy(['player' => $player]) !== null) {
            throw new PlayerClaimException('Player is already claimed.');
        }

        return $player;
    }
}
