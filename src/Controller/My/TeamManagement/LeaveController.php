<?php

declare(strict_types=1);

namespace App\Controller\My\TeamManagement;

use App\Attribute\RateLimited;
use App\Entity\User;
use App\Service\TeamManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/team/leave', name: 'my_team_leave', methods: ['POST'])]
#[RateLimited('mutation')]
class LeaveController extends AbstractController
{
    public function __invoke(TeamManagementService $service): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->leaveTeam($user->getPlayer());
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
