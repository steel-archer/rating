<?php

declare(strict_types=1);

namespace App\Common\Controller\Moderator\Venue;

use App\Common\Attribute\RateLimited;
use App\Common\Entity\Venue;
use App\Common\Service\VenueManagementService;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/venues/{id}/approve', name: 'moderator_venue_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
class ClaimApproveController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
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
