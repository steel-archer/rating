<?php

declare(strict_types=1);

namespace App\Controller\Venue;

use App\Entity\Venue;
use App\Repository\TournamentSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/venue/{id}/tournaments', name: 'venue_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentsController extends AbstractController
{
    public function __invoke(
        Venue $venue,
        Request $request,
        TournamentSessionRepository $sessionRepository,
    ): Response {
        try {
            $page = PageResolver::resolve($request);

            return $this->render('venue/_tournaments.html.twig', [
                'venueId' => $venue->getId(),
                'tournaments' => $sessionRepository->findByVenuePaginated($venue, $page),
                'page' => $page,
                'lastPage' => $sessionRepository->getLastPageNumberByVenue($venue),
            ]);
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }
    }
}
