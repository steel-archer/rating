<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\ClaimExistingRequestDTO;
use App\DTO\Request\ClaimNewRequestDTO;
use App\Entity\Country;
use App\Entity\Player;
use App\Entity\PlayerClaim;
use App\Entity\Town;
use App\Enum\PlayerClaimStatus;
use App\Exception\PlayerClaimException;
use App\Repository\PlayerClaimRepository;
use App\Repository\PlayerRepository;
use App\Repository\TownRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PlayerClaimService
{
    private const string DEFAULT_COUNTRY = 'Україна';

    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private PlayerRepository $playerRepository,
        private PlayerClaimRepository $claimRepository,
        private TownRepository $townRepository,
    ) {
    }

    /**
     * @throws PlayerClaimException
     */
    public function approve(int $id, ?string $townName = null): void
    {
        $claim = $this->findPendingClaim($id);

        if ($claim->getUser()->getPlayer() !== null) {
            throw new PlayerClaimException('User already has a player.');
        }

        if ($claim->isNew()) {
            $townName = $townName ?? $claim->getTown()?->getName() ?? $claim->getTownName();
            $claim->setTown($this->resolveTown($townName));
        }

        $player = $claim->isNew() ? $this->createPlayer($claim) : $this->resolveExistingPlayer($claim);

        $claim->getUser()->setPlayer($player);
        $claim->setStatus(PlayerClaimStatus::Approved);
        $this->em->flush();

        $this->claimRepository->rejectOtherPendingClaims($claim);
    }

    public function reject(int $id): void
    {
        $claim = $this->findPendingClaim($id);
        $claim->setStatus(PlayerClaimStatus::Rejected);
        $this->em->flush();
    }

    /**
     * @throws PlayerClaimException
     */
    public function claimExisting(ClaimExistingRequestDTO $dto, User $user): void
    {
        if ($user->getPlayer() !== null || $this->claimRepository->hasPendingClaim($user)) {
            throw new PlayerClaimException('common.error');
        }

        $player = $this->playerRepository->find($dto->playerId);

        if ($player === null || $this->userRepository->findOneBy(['player' => $player]) !== null) {
            throw new PlayerClaimException('common.not_found');
        }

        $claim = new PlayerClaim();
        $claim->setUser($user);
        $claim->setPlayer($player);
        $claim->setLastName($player->getLastName());

        $this->em->persist($claim);
        $this->em->flush();
    }

    /**
     * @throws PlayerClaimException
     */
    public function claimNew(ClaimNewRequestDTO $dto, User $user): void
    {
        if ($user->getPlayer() !== null || $this->claimRepository->hasPendingClaim($user)) {
            throw new PlayerClaimException('common.error');
        }

        $claim = new PlayerClaim();
        $claim->setUser($user);
        $claim->setLastName($dto->lastName);
        $claim->setFirstName($dto->firstName);
        $claim->setPatronymic($dto->patronymic);

        if ($dto->townId !== null) {
            $town = $this->townRepository->find($dto->townId);
            if ($town === null) {
                throw new PlayerClaimException('venue.error.town_not_found');
            }
            $claim->setTown($town);
        } elseif ($dto->townName !== null && $dto->townName !== '') {
            $claim->setTownName($dto->townName);
        }

        $this->em->persist($claim);
        $this->em->flush();
    }

    private function findPendingClaim(int $id): PlayerClaim
    {
        $claim = $this->em->getRepository(PlayerClaim::class)->find($id);

        if ($claim === null || $claim->getStatus() !== PlayerClaimStatus::Pending) {
            throw new PlayerClaimException('Claim not found or already processed.');
        }

        return $claim;
    }

    private function resolveTown(?string $townName): ?Town
    {
        if ($townName === null || $townName === '') {
            return null;
        }

        $existing = $this->townRepository->findOneBy(['name' => $townName]);
        if ($existing !== null) {
            return $existing;
        }

        $country = $this->em->getRepository(Country::class)->findOneBy(['name' => self::DEFAULT_COUNTRY]);
        if ($country === null) {
            return null;
        }

        $town = new Town();
        $town->setName($townName);
        $town->setCountry($country);
        $this->em->persist($town);

        return $town;
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
