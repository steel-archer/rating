<?php

declare(strict_types=1);

namespace App\Controller\PlayerClaim;

use App\DTO\Request\ClaimExistingRequestDTO;
use App\Entity\PlayerClaim;
use App\Entity\User;
use App\Repository\PlayerClaimRepository;
use App\Repository\PlayerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/player-claim/existing', name: 'player_claim_existing', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class ClaimExistingController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] ClaimExistingRequestDTO $dto,
        PlayerRepository $playerRepository,
        UserRepository $userRepository,
        PlayerClaimRepository $claimRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayer() !== null || $claimRepository->hasPendingClaim($user)) {
            return $this->json(['error' => 'common.error'], 422);
        }

        $player = $playerRepository->find($dto->playerId);

        if ($player === null || $userRepository->findOneBy(['player' => $player]) !== null) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $claim = new PlayerClaim();
            $claim->setUser($user);
            $claim->setPlayer($player);
            $claim->setLastName($player->getLastName());

            $em->persist($claim);
            $em->flush();
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true], 201);
    }
}
