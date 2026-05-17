<?php

declare(strict_types=1);

namespace App\Controller\Venue;

use App\Service\VenueService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/venue/{id}', name: 'venue_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(int $id, VenueService $venueService): Response
    {
        $venue = $venueService->get($id);

        return $this->render('venue/show.html.twig', [
            'venue' => $venue,
        ]);
    }
}
