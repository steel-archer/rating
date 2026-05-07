<?php

declare(strict_types=1);

namespace App\Controller\My\Venue;

use App\DTO\Request\Venue\CreateRequestDTO;
use App\Entity\User;
use App\Service\VenueManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/my/venues', name: 'my_venue_store', methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
class StoreController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] CreateRequestDTO $dto,
        VenueManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $venue = $service->create($dto->name, $dto->townId, $user);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true, 'id' => $venue->getId()], 201);
    }
}
