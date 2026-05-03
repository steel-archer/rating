<?php

namespace App\Controller\Venue;

use App\Repository\TournamentSessionRepository;
use App\Repository\VenueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/venue/{id}/tournaments', name: 'venue_tournaments', requirements: ['id' => '\d+'])]
final class TournamentsController extends AbstractController
{
    public function __invoke(
        int $id,
        Request $request,
        VenueRepository $venueRepository,
        TournamentSessionRepository $sessionRepository,
    ): Response {
        try {
            $venue = $venueRepository->find($id)
                ?? throw new NotFoundHttpException("Venue #$id not found");

            $page = max(1, $request->query->getInt('page', 1));

            return $this->render('venue/_tournaments.html.twig', [
                'venue' => $venue,
                'tournaments' => $sessionRepository->findByVenuePaginated($venue, $page),
                'page' => $page,
                'lastPage' => $sessionRepository->getLastPageNumberByVenue($venue),
            ]);
        } catch (NotFoundHttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
