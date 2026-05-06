<?php

namespace App\Controller\Moderator\Venue;

use App\Repository\VenueRepository;
use App\Service\VenueManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/moderator/venues/{id}/reject', name: 'moderator_venue_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
#[IsCsrfTokenValid(new Expression("'venue_moderate_' ~ args['id']"))]
final class ClaimRejectController extends AbstractController
{
    public function __invoke(
        int $id,
        VenueRepository $venueRepository,
        VenueManagementService $service,
    ): Response {
        $venue = $venueRepository->find($id);
        if ($venue === null) {
            throw $this->createNotFoundException();
        }

        try {
            $service->reject($venue);
        } catch (LogicException $ex) {
            $this->addFlash('error', $ex->getMessage());
        } catch (Throwable) {
            $this->addFlash('error', 'common.error');
        }

        return $this->redirectToRoute('moderator_venues');
    }
}
