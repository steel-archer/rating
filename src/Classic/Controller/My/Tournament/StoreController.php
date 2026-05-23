<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Tournament;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\Tournament\My\CreateRequestDTO;
use App\Common\Entity\User;
use App\Classic\Service\TournamentManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments', name: 'my_tournament_store', methods: ['POST'])]
#[RateLimited('mutation')]
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
