<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\TeamManagement;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\TeamManagement\SetCaptainRequestDTO;
use App\Common\Entity\User;
use App\Classic\Service\TeamManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/team/set-captain', name: 'my_team_set_captain', methods: ['POST'])]
#[RateLimited('mutation')]
class SetCaptainController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] SetCaptainRequestDTO $dto,
        TeamManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $service->setCaptain($user->getPlayer(), $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
