<?php

declare(strict_types=1);

namespace App\Controller\My\Venue;

use App\DTO\Response\My\VenueEditDTO;
use App\DTO\Response\My\VenueRepresentativeDTO;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\VenueRepository;
use App\Repository\VenueRepresentativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/venues/{id}/edit', name: 'my_venue_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class EditController extends AbstractController
{
    public function __invoke(
        int $id,
        VenueRepository $venueRepository,
        VenueRepresentativeRepository $representativeRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $venue = $venueRepository->find($id);

        if ($venue === null || $venue->getCreatedBy() !== $user) {
            throw $this->createNotFoundException();
        }

        if (!$venue->isApproved()) {
            return $this->redirectToRoute('my_venues');
        }

        $venueDto = $mapper->map($venue, VenueEditDTO::class);

        $representatives = $mapper->mapMultiple(
            $representativeRepository->findByVenueWithPlayer($venue),
            VenueRepresentativeDTO::class,
        );

        return $this->render('my/venue/edit.html.twig', [
            'venue' => $venueDto,
            'representatives' => $representatives,
        ]);
    }
}
