<?php

namespace App\Controller\Venue;

use App\Repository\TournamentSessionRepository;
use App\Repository\VenueRepository;
use App\Repository\VenueRepresentativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/venue/{id}', name: 'venue_show', requirements: ['id' => '\d+'], methods: ['GET'])]
final class ShowController extends AbstractController
{
    public function __invoke(
        int $id,
        VenueRepository $venueRepository,
        VenueRepresentativeRepository $representativeRepository,
        TournamentSessionRepository $sessionRepository,
    ): Response {
        try {
            $venue = $venueRepository->findWithTown($id)
                ?? throw new NotFoundHttpException("Venue #$id not found");

            return $this->render('venue/show.html.twig', [
                'venue' => $venue,
                'representatives' => $representativeRepository->findByVenueWithPlayer($venue),
                'tournamentCount' => $sessionRepository->countByVenue($venue),
            ]);
        } catch (NotFoundHttpException $exception) { // @codeCoverageIgnoreStart
            throw $exception; // @codeCoverageIgnoreEnd
        } catch (Throwable $exception) { // @codeCoverageIgnoreStart
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception); // @codeCoverageIgnoreEnd
        }
    }
}
