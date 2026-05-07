<?php

declare(strict_types=1);

namespace App\Controller\PlayerClaim;

use App\DTO\Request\ClaimNewRequestDTO;
use App\Entity\PlayerClaim;
use App\Entity\User;
use App\Repository\PlayerClaimRepository;
use App\Repository\TownRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/player-claim/new', name: 'player_claim_new', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class ClaimNewController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] ClaimNewRequestDTO $dto,
        PlayerClaimRepository $claimRepository,
        TownRepository $townRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayer() !== null || $claimRepository->hasPendingClaim($user)) {
            return $this->json(['error' => 'common.error'], 422);
        }

        try {
            $claim = new PlayerClaim();
            $claim->setUser($user);
            $claim->setLastName($dto->lastName);
            $claim->setFirstName($dto->firstName);
            $claim->setPatronymic($dto->patronymic);

            if ($dto->townId !== null) {
                $claim->setTown($townRepository->find($dto->townId));
            }

            $em->persist($claim);
            $em->flush();
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true], 201);
    }
}
