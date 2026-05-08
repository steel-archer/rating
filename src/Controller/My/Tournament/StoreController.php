<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament;

use App\DTO\Request\Tournament\My\CreateRequestDTO;
use App\Entity\User;
use App\Service\TournamentManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments', name: 'my_tournament_store', methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
class StoreController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] CreateRequestDTO $dto,
        TournamentManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $tournament = $service->create($dto, $user->getPlayer());

        return $this->json(['success' => true, 'id' => $tournament->getId()], 201);
    }
}
