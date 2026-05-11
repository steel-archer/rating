<?php

declare(strict_types=1);

namespace App\Controller\My\Venue;

use App\Attribute\RateLimited;
use App\DTO\Request\Venue\CreateRequestDTO;
use App\Entity\User;
use App\Service\VenueManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/venues', name: 'my_venue_store', methods: ['POST'])]
#[RateLimited('mutation')]
class StoreController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] CreateRequestDTO $dto,
        VenueManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $venue = $service->create($dto, $user->getPlayer());
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true, 'id' => $venue->getId()], 201);
    }
}
