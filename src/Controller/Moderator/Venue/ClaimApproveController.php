<?php

declare(strict_types=1);

namespace App\Controller\Moderator\Venue;

use App\Attribute\RateLimited;
use App\Entity\Venue;
use App\Service\VenueManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/venues/{id}/approve', name: 'moderator_venue_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
class ClaimApproveController extends AbstractController
{
    public function __invoke(
        Venue $venue,
        VenueManagementService $service,
    ): JsonResponse {
        try {
            $service->approve($venue);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
