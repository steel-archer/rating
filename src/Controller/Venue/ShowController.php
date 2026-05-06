<?php

namespace App\Controller\Venue;

use App\Exception\EntityNotFoundException;
use App\Service\VenueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/venue/{id}', name: 'venue_show', requirements: ['id' => '\d+'], methods: ['GET'])]
final class ShowController extends AbstractController
{
    public function __invoke(int $id, VenueService $venueService): Response
    {
        try {
            $venue = $venueService->get($id);
        } catch (EntityNotFoundException $ex) {
            throw new NotFoundHttpException($ex->getMessage(), $ex);
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }

        return $this->render('venue/show.html.twig', [
            'venue' => $venue,
        ]);
    }
}
