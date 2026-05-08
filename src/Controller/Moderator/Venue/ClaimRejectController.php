<?php

declare(strict_types=1);

namespace App\Controller\Moderator\Venue;

use App\Entity\Venue;
use App\Service\VenueManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/moderator/venues/{id}/reject', name: 'moderator_venue_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
class ClaimRejectController extends AbstractController
{
    public function __invoke(
        Venue $venue,
        VenueManagementService $service,
    ): JsonResponse {
        try {
            $service->reject($venue);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (Throwable) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
