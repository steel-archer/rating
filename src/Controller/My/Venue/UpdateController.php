<?php

declare(strict_types=1);

namespace App\Controller\My\Venue;

use App\DTO\Request\Venue\UpdateRequestDTO;
use App\Entity\User;
use App\Entity\Venue;
use App\Service\VenueManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/venues/{id}', name: 'my_venue_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
class UpdateController extends AbstractController
{
    public function __invoke(
        Venue $venue,
        #[MapRequestPayload] UpdateRequestDTO $dto,
        VenueManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($venue->getCreatedBy() !== $user->getPlayer()) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->updateRepresentatives($venue, $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        $this->addFlash('success', 'venue.my.saved');

        return $this->json(['success' => true]);
    }
}
