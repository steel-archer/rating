<?php

namespace App\Controller\Venue;

use App\Repository\TournamentSessionRepository;
use App\Repository\VenueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/venue/{id}/tournaments', name: 'venue_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
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

            $page = PageResolver::resolve($request);

            return $this->render('venue/_tournaments.html.twig', [
                'venue' => $venue,
                'tournaments' => $sessionRepository->findByVenuePaginated($venue, $page),
                'page' => $page,
                'lastPage' => $sessionRepository->getLastPageNumberByVenue($venue),
            ]);
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }
    }
}
