<?php

namespace App\Controller\Claim;

use App\DTO\Request\ClaimExistingRequestDTO;
use App\Entity\PlayerClaim;
use App\Entity\User;
use App\Repository\PlayerClaimRepository;
use App\Repository\PlayerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/claim/existing', name: 'claim_existing', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[IsCsrfTokenValid('claim_existing')]
final class ClaimExistingController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] ClaimExistingRequestDTO $dto,
        PlayerRepository $playerRepository,
        UserRepository $userRepository,
        PlayerClaimRepository $claimRepository,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayer() !== null || $claimRepository->hasPendingClaim($user)) {
            return $this->redirectToRoute('home');
        }

        $player = $playerRepository->find($dto->playerId);

        if ($player === null) {
            throw new NotFoundHttpException();
        }

        if ($userRepository->findOneBy(['player' => $player]) !== null) {
            throw new NotFoundHttpException();
        }

        $claim = new PlayerClaim();
        $claim->setUser($user);
        $claim->setPlayer($player);
        $claim->setLastName($player->getLastName());

        $em->persist($claim);
        $em->flush();

        return $this->redirectToRoute('claim_submitted');
    }
}
