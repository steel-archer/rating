<?php

declare(strict_types=1);

namespace App\Controller\My\TeamManagement;

use App\Attribute\RateLimited;
use App\DTO\Request\TeamManagement\UpdateTeamRequestDTO;
use App\Entity\User;
use App\Service\TeamManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/team/update', name: 'my_team_update', methods: ['POST'])]
#[RateLimited('mutation')]
class UpdateTeamController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] UpdateTeamRequestDTO $dto,
        TeamManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->updateTeam($user->getPlayer(), $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
