<?php

declare(strict_types=1);

namespace App\Controller\Moderator\Venue;

use App\Repository\VenueRepository;
use App\Service\VenueManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/moderator/venues/{id}/approve', name: 'moderator_venue_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
class ClaimApproveController extends AbstractController
{
    public function __invoke(
        int $id,
        VenueRepository $venueRepository,
        VenueManagementService $service,
    ): JsonResponse {
        $venue = $venueRepository->find($id);
        if ($venue === null) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->approve($venue);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
