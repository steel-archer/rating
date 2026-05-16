<?php

declare(strict_types=1);

namespace App\Controller\Venue;

use App\DTO\Request\PageRequestDTO;
use App\Entity\Venue;
use App\Repository\TournamentSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/venue/{id}/tournaments', name: 'venue_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentsController extends AbstractController
{
    public function __invoke(
        Venue $venue,
        TournamentSessionRepository $sessionRepository,
        #[MapQueryString] PageRequestDTO $dto = new PageRequestDTO(),
    ): Response {
        return $this->render('venue/_tournaments.html.twig', [
            'venueId' => $venue->getId(),
            'tournaments' => $sessionRepository->findByVenuePaginated($venue, $dto->page),
            'page' => $dto->page,
            'lastPage' => $sessionRepository->getLastPageNumberByVenue($venue),
        ]);
    }
}
