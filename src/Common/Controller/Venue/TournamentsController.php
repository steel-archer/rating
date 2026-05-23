<?php

declare(strict_types=1);

namespace App\Common\Controller\Venue;

use App\Common\Contract\VenueTournamentProviderInterface;
use App\Common\DTO\Request\PageRequestDTO;
use App\Common\Entity\Venue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/venue/{id}/tournaments', name: 'venue_tournaments', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentsController extends AbstractController
{
    public function __invoke(
        Venue $venue,
        VenueTournamentProviderInterface $tournamentProvider,
        #[MapQueryString] PageRequestDTO $dto = new PageRequestDTO(),
    ): Response {
        return $this->render('venue/_tournaments.html.twig', [
            'venueId' => $venue->getId(),
            'tournaments' => $tournamentProvider->findByVenuePaginated($venue, $dto->page),
            'page' => $dto->page,
            'lastPage' => $tournamentProvider->getLastPageNumberByVenue($venue),
        ]);
    }
}
