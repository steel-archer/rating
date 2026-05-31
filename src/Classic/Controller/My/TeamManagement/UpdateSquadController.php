<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\TeamManagement;

use App\Classic\DTO\Request\TeamManagement\UpdateSquadRequestDTO;
use App\Classic\Service\TeamManagementService;
use App\Common\Attribute\RateLimited;
use App\Common\Entity\User;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/team/update-squad', name: 'my_team_update_squad', methods: ['POST'])]
#[RateLimited('mutation')]
class UpdateSquadController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] UpdateSquadRequestDTO $dto,
        TeamManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->updateSquad($user->getPlayer(), $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
