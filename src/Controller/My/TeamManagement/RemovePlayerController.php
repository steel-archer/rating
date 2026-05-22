<?php

declare(strict_types=1);

namespace App\Controller\My\TeamManagement;

use App\Attribute\RateLimited;
use App\DTO\Request\TeamManagement\RemovePlayerRequestDTO;
use App\Entity\User;
use App\Service\TeamManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/team/remove-player', name: 'my_team_remove_player', methods: ['POST'])]
#[RateLimited('mutation')]
class RemovePlayerController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] RemovePlayerRequestDTO $dto,
        TeamManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->removePlayer($user->getPlayer(), $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
