<?php

declare(strict_types=1);

namespace App\Controller\Moderator\Venue;

use App\DTO\Response\Moderator\VenueClaimDTO;
use App\Mapping\Mapper;
use App\Repository\VenueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderator/venues', name: 'moderator_venues', methods: ['GET'])]
#[IsGranted('ROLE_MODERATOR')]
class ClaimListController extends AbstractController
{
    public function __invoke(VenueRepository $venueRepository, Mapper $mapper): Response
    {
        return $this->render('moderator/venues.html.twig', [
            'venues' => $mapper->mapMultiple($venueRepository->findPendingApproval(), VenueClaimDTO::class),
        ]);
    }
}
